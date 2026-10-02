-- =============================================================================
-- Pinkventory — production schema (MySQL 5.7+ / MariaDB 10.3+)
--
-- Import into an EMPTY database that your host created for you, e.g.
--   mysql -h HOST -u USER -p DATABASE_NAME < database/schema.sql
-- or through phpMyAdmin → Import.
--
-- No user or admin accounts are seeded. After deploying, open /Admin.php once
-- to create the first admin; from then on only signed-in admins can add admins.
--
-- The three tables are independent (no table references another), so there
-- are no foreign keys.
-- =============================================================================

SET NAMES utf8mb4;

-- Shared-hosting databases are usually created in the control panel. If you
-- manage your own server, uncomment these two lines instead:
-- CREATE DATABASE IF NOT EXISTS pinkventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE pinkventory;

-- -----------------------------------------------------------------------------
-- admin: administrator accounts (index.php admin login, Admin.php, admin_dashboard.php)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)  NOT NULL,
    password    VARCHAR(255) NOT NULL COMMENT 'password_hash() output',
    fullname    VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- users: store users / customers (sign up, sign in with lockout, admin user CRUD)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email                VARCHAR(255) NOT NULL,
    fullname             VARCHAR(100) NOT NULL,
    password             VARCHAR(255) NOT NULL COMMENT 'password_hash() output',
    dob                  DATE         NULL     COMMENT 'set on self sign-up; admin-created users may omit it',
    gender               VARCHAR(10)  NULL     COMMENT 'Male | Female | Other',
    failed_attempts      INT UNSIGNED NOT NULL DEFAULT 0,
    last_failed_attempt  DATETIME     NULL,
    created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- inventory: products (inventory.php CRUD, dashboard stats, navbar search)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    product_name  VARCHAR(150)  NOT NULL,
    category      VARCHAR(50)   NOT NULL,
    stock         INT UNSIGNED  NOT NULL DEFAULT 0,
    price         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status        VARCHAR(20)   NOT NULL DEFAULT 'in-stock' COMMENT 'in-stock | low-stock | out-of-stock',
    last_updated  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventory_product_name (product_name),  -- inventory list ordering + search suggestions
    KEY idx_inventory_stock (stock),                -- low-stock alerts
    KEY idx_inventory_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Orders (same as database/migrations/001_add_orders.sql)
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
