-- Optional demo products so the dashboard, inventory list and search have data.
-- Import AFTER schema.sql. Skip this file for a clean production start.
INSERT INTO inventory (product_name, category, stock, price, status) VALUES
    ('Wireless Earbuds',      'electronics', 42, 59.99, 'in-stock'),
    ('Phone Charger 20W',     'electronics',  6, 19.99, 'low-stock'),
    ('Pink Hoodie',           'clothing',    25, 34.50, 'in-stock'),
    ('Denim Jacket',          'clothing',     0, 79.00, 'out-of-stock'),
    ('Leather Wallet',        'accessories', 15, 24.99, 'in-stock'),
    ('Sunglasses',            'accessories',  8, 14.99, 'low-stock');
