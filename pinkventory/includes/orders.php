<?php
/**
 * Orders: placing, reading and changing the status of customer orders.
 *
 * Tables come from database/migrations/001_add_orders.sql. Until that
 * migration is applied, orders_enabled() is false and the UI explains that
 * ordering isn't switched on - nothing breaks.
 */

if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/store.php';

const ORDER_STATUSES = [
    'pending'    => ['Pending', 'badge-low'],
    'processing' => ['Processing', 'badge-info'],
    'completed'  => ['Completed', 'badge-ok'],
    'cancelled'  => ['Cancelled', 'badge-neutral'],
];

/**
 * Allowed status changes. Cancelled and completed are final, so an order can
 * be cancelled - and its stock restored - at most once.
 */
const ORDER_TRANSITIONS = [
    'pending'    => ['processing', 'completed', 'cancelled'],
    'processing' => ['completed', 'cancelled'],
    'completed'  => [],
    'cancelled'  => [],
];

const PAYMENT_METHODS = ['cash' => 'Cash', 'card' => 'Card'];

function orders_enabled(mysqli $conn): bool
{
    static $enabled = null;
    if ($enabled === null) {
        $result = $conn->query("SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name IN ('orders', 'order_items')");
        $enabled = (int) $result->fetch_row()[0] === 2;
    }
    return $enabled;
}

function order_status_badge(string $status): string
{
    [$label, $class] = ORDER_STATUSES[$status] ?? [ucfirst($status), 'badge-neutral'];
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function order_number(int $id): string
{
    return 'PV-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

/** One-time token that stops a refreshed or double-submitted checkout from creating two orders. */
function checkout_nonce(): string
{
    if (empty($_SESSION['checkout_nonce'])) {
        $_SESSION['checkout_nonce'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['checkout_nonce'];
}

/**
 * Places an order for $customer (a users row) from $lines = [productId => [qty, priceSeenByCustomer]].
 *
 * Everything happens in one transaction: the product rows are locked, every line
 * is checked against the current price and stock, the order and its items are
 * saved with today's prices, and stock is reduced. Any problem rolls the whole
 * thing back.
 *
 * Returns [int|null $orderId, string|null $error].
 */
function place_order(mysqli $conn, array $customer, array $lines, string $paymentMethod): array
{
    if (!isset(PAYMENT_METHODS[$paymentMethod])) {
        return [null, 'Please choose a payment method.'];
    }
    if (!$lines) {
        return [null, 'Your cart is empty.'];
    }
    if (count($lines) > 100) {
        return [null, 'Too many different products in one order.'];
    }

    $ids = array_keys($lines);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $conn->begin_transaction();
    try {
        // Lock these products so nobody else can buy the same stock meanwhile.
        $stmt = $conn->prepare("SELECT id, product_name, stock, price, status FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $products = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $products[(int) $row['id']] = $row;
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($lines as $id => [$qty, $seenPrice]) {
            $product = $products[$id] ?? null;
            if (!$product) {
                throw new OrderProblem('A product in your cart is no longer available.');
            }
            $name = $product['product_name'];
            $stock = (int) $product['stock'];
            if (stock_level($stock, $product['status']) === 'out') {
                throw new OrderProblem($name . ' is out of stock.');
            }
            if ($qty > $stock) {
                throw new OrderProblem('Only ' . $stock . ' × ' . $name . ' left in stock. Please update your cart.');
            }
            $price = round((float) $product['price'], 2);
            if (abs($price - $seenPrice) > 0.004) {
                throw new OrderProblem('The price of ' . $name . ' changed to ' . money($price) . '. Please review your cart.');
            }
            $lineTotal = round($price * $qty, 2);
            $subtotal += $lineTotal;
            $items[] = [$id, $name, $price, $qty, $lineTotal];
        }

        $subtotal = round($subtotal, 2);
        $tax = round($subtotal * STORE_TAX_RATE, 2);
        $total = round($subtotal + $tax, 2);

        $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_email, status, payment_method, subtotal, tax, total)
            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)");
        $stmt->bind_param("isssddd", $customer['id'], $customer['fullname'], $customer['email'], $paymentMethod, $subtotal, $tax, $total);
        $stmt->execute();
        $orderId = (int) $conn->insert_id;

        $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
            VALUES (?, ?, ?, ?, ?, ?)");
        $reduceStock = $conn->prepare("UPDATE inventory SET stock = stock - ?, last_updated = NOW() WHERE id = ? AND stock >= ?");
        foreach ($items as [$id, $name, $price, $qty, $lineTotal]) {
            $insertItem->bind_param("iisdid", $orderId, $id, $name, $price, $qty, $lineTotal);
            $insertItem->execute();
            $reduceStock->bind_param("iii", $qty, $id, $qty);
            $reduceStock->execute();
            if ($reduceStock->affected_rows !== 1) {
                throw new OrderProblem('Not enough stock for ' . $name . '.');
            }
        }

        $conn->commit();
        return [$orderId, null];
    } catch (OrderProblem $problem) {
        $conn->rollback();
        return [null, $problem->getMessage()];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Changes an order's status (admin). Cancelling puts the items back in stock.
 * The order row is locked for the whole change, and cancelled is a final status,
 * so stock is restored exactly once even if two cancel requests arrive together.
 *
 * Returns null on success or an error message.
 */
function change_order_status(mysqli $conn, int $orderId, string $newStatus): ?string
{
    if (!isset(ORDER_STATUSES[$newStatus])) {
        return 'Unknown status.';
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            throw new OrderProblem('That order no longer exists.');
        }
        $current = $row['status'];
        if (!in_array($newStatus, ORDER_TRANSITIONS[$current] ?? [], true)) {
            throw new OrderProblem('A ' . strtolower(ORDER_STATUSES[$current][0]) . ' order can’t be changed to ' . strtolower(ORDER_STATUSES[$newStatus][0]) . '.');
        }

        if ($newStatus === 'cancelled') {
            // Products deleted since the order was placed have product_id NULL and are skipped.
            $stmt = $conn->prepare("UPDATE inventory i
                JOIN (SELECT product_id, SUM(quantity) AS qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL GROUP BY product_id) oi
                  ON oi.product_id = i.id
                SET i.stock = i.stock + oi.qty, i.last_updated = NOW()");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();

        $conn->commit();
        return null;
    } catch (OrderProblem $problem) {
        $conn->rollback();
        return $problem->getMessage();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function fetch_order(mysqli $conn, int $orderId, ?int $userId = null): ?array
{
    if ($userId === null) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
    } else {
        // Customers only ever see their own orders.
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $orderId, $userId);
    }
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) {
        return null;
    }
    $stmt = $conn->prepare("SELECT oi.*, i.category FROM order_items oi LEFT JOIN inventory i ON i.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return $order;
}

/** Revenue counts every order that wasn't cancelled. */
function revenue_sql(): string
{
    return "status <> 'cancelled'";
}

final class OrderProblem extends RuntimeException
{
}
