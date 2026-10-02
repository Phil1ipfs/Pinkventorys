-- Run once against an EXISTING Pinkventory database (e.g. the local development
-- copy) that was created before schema.sql existed. Safe to re-run.

-- add_product.php used to store 'in-stock' while update_product.php stored
-- 'In Stock'. Normalise everything to the slug form the app now uses.
UPDATE inventory SET status = LOWER(REPLACE(TRIM(status), ' ', '-'))
WHERE status <> LOWER(REPLACE(TRIM(status), ' ', '-'));

-- Admin.php used to insert every new admin twice. Keep the oldest row per username.
DELETE a1 FROM admin a1
JOIN admin a2 ON a1.username = a2.username AND a1.id > a2.id;

-- Afterwards, compare your tables with schema.sql and add any missing unique
-- keys, for example:
--   ALTER TABLE admin ADD UNIQUE KEY uq_admin_username (username);
--   ALTER TABLE users ADD UNIQUE KEY uq_users_email (email);
