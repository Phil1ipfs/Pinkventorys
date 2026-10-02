<?php
require_once 'bootstrap.php';
require_once 'includes/orders.php';
require_user();

// CSRF is verified in bootstrap.php for every POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Checkout.php');
}
if (!orders_enabled($conn)) {
    flash('error', 'Online ordering isn’t switched on yet.');
    redirect('Checkout.php');
}

// One-time token: a refreshed or double-submitted checkout can't create a second order.
$nonce = (string) ($_POST['checkout_nonce'] ?? '');
$expected = $_SESSION['checkout_nonce'] ?? '';
unset($_SESSION['checkout_nonce']);
if ($expected === '' || !hash_equals($expected, $nonce)) {
    flash('error', 'This checkout was already submitted. Check your orders before trying again.');
    redirect('orders.php');
}

$customer = current_user_row($conn);
if (!$customer) {
    redirect(logout_url());
}

// items[<product id>][qty] and items[<product id>][price] (the price the customer saw)
$lines = [];
foreach ((array) ($_POST['items'] ?? []) as $id => $item) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
    $price = filter_var($item['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if (!$id || $id < 1 || $qty === false || $price === false) {
        flash('error', 'Your cart contained an invalid item. Please review it and try again.');
        redirect('cart.php');
    }
    $lines[$id] = [$qty, round($price, 2)];
}

[$orderId, $error] = place_order($conn, $customer, $lines, (string) ($_POST['payment_method'] ?? ''));

if ($error) {
    flash('error', $error);
    redirect('cart.php');
}

flash('success', 'Thank you! Order ' . order_number($orderId) . ' was placed.');
redirect('order.php?id=' . $orderId . '&placed=1');
