-- =============================================================================
-- Pinkventory migration 001: customer orders
--
-- PROPOSED - review before running. Adds two NEW tables only.
-- Does not modify or delete any existing table or row.
--
-- Run once in phpMyAdmin (select your database → SQL tab → paste → Go),
-- or: mysql -h HOST -u USER -p DB_NAME < database/migrations/001_add_orders.sql
--
-- Requires users.id and inventory.id to be INT UNSIGNED on InnoDB tables
-- (true for databases created from database/schema.sql). Check with:
--   SHOW CREATE TABLE users;  SHOW CREATE TABLE inventory;
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- orders: one row per placed order
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED  NULL COMMENT 'customer; NULL if the account is later deleted',
    customer_name   VARCHAR(100)  NOT NULL COMMENT 'snapshot of users.fullname when ordered',
    customer_email  VARCHAR(255)  NOT NULL COMMENT 'snapshot of users.email when ordered',
    status          ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_method  ENUM('cash', 'card') NOT NULL COMMENT 'recorded only - no payment is processed',
    subtotal        DECIMAL(10,2) NOT NULL,
    tax             DECIMAL(10,2) NOT NULL,
    total           DECIMAL(10,2) NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_user_created (user_id, created_at),       -- a customer's order history
    KEY idx_orders_status_created (status, created_at),      -- admin lists + revenue by period
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_orders_amounts
        CHECK (subtotal >= 0 AND tax >= 0 AND total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- order_items: the products in each order, priced at the time of ordering
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED  NOT NULL,
    product_id      INT UNSIGNED  NULL COMMENT 'NULL if the product is later deleted',
    product_name    VARCHAR(150)  NOT NULL COMMENT 'snapshot of inventory.product_name',
    unit_price      DECIMAL(10,2) NOT NULL COMMENT 'price when ordered; later price changes do not affect it',
    quantity        INT UNSIGNED  NOT NULL,
    line_total      DECIMAL(10,2) NOT NULL COMMENT 'unit_price × quantity',
    PRIMARY KEY (id),
    KEY idx_items_order (order_id),
    KEY idx_items_product (product_id),                       -- best sellers, per-product sales
    CONSTRAINT fk_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_items_product
        FOREIGN KEY (product_id) REFERENCES inventory (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_items_amounts CHECK (unit_price >= 0 AND line_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- To undo this migration (deletes all orders!):
--   DROP TABLE order_items;
--   DROP TABLE orders;
-- -----------------------------------------------------------------------------
