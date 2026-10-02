# Pinkventory

Inventory management and online storefront built with plain PHP and MySQL.

## Roles

- **Customers** (`users` table) sign up and use the storefront: Home, Shop, product pages, Cart, Checkout, Profile.
- **Admins** (`admin` table) manage the store: Dashboard, Inventory (add/edit/delete products), Users, Add admin, Profile.

Customers cannot open admin pages or call admin actions. Admins are sent to their dashboard if they open customer pages.

## Project layout

```
pinkventory/                ← web root (upload the contents of this folder)
  bootstrap.php             shared setup: config, DB, session, CSRF, auth + helpers
  config.example.php        configuration template → copy to config.php
  .htaccess                 blocks config/internal files, caches assets
  index.php                 public landing page
  login.php                 log in (customer + admin) / sign up
  logout.php
  profile.php               account details + change password (both roles)

  Customer storefront
  homepage.php              home: welcome, new arrivals, categories
  shop.php                  product grid with search, filters, sorting, pages
  product.php               product details + add to cart
  cart.php                  cart (re-priced from the database on every visit)
  Checkout.php              checkout: review, payment method (recorded only), place order
  place_order.php           places an order (transaction: re-check prices + stock, save, reduce stock)
  orders.php / order.php    the customer's own order history and order details

  Admin
  admin_dashboard.php       overview: stock stats, value by category, alerts, activity
  inventory.php             product table with search, filters, sorting, pages
  add_product.php / update_product.php / delete_product.php
  admin_orders.php          all orders: search, status tabs, revenue
  admin_order.php           one order: change status, cancel (returns stock)
  users.php                 customer accounts
  add_user.php / update_user.php / delete_user.php
  Admin.php                 create admin accounts (first admin, then admins only)

  search_api.php            JSON: search suggestions, product lookup, cart re-pricing

  includes/                 server-side only (blocked from the web)
    layout.php              app shell: sidebar, top bar, search, toasts, dialogs
    store.php               storefront components (product cards, cart buttons)
    orders.php              order logic: place_order(), change_order_status(), lookups
    order_view.php          order details shared by customer and admin pages
    product_form.php        product form shared by add/update product
  assets/
    css/pinkventory.css     design system for every signed-in page
    css/auth.css            log in / sign up / first-admin pages
    css/landing.css         landing page
    js/app.js               shared UI: navigation, search, toasts, dialogs, cart, idle sign-out
    js/store.js             storefront: filters, quantity steppers, cart + checkout pages
    js/inventory.js, product.js, users.js, auth.js, landing.js
    img/icons.svg           icon sprite (Lucide icons, ISC licence) - only the icons used
    img/logo.png            logo
    img/illus-signin.svg, illus-signup.svg   sign-in / sign-up illustrations
database/
  schema.sql                tables, keys and indexes (import into a new database)
  sample_data.sql           optional demo products
  migrate_existing.sql      one-off clean-up for databases created before schema.sql
  migrations/001_add_orders.sql   orders + order_items tables (run once on existing databases)
```

## Requirements

- PHP 8.0 or newer with the `mysqli` and `mbstring` extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `.htaccess` enabled (or the equivalent rules in nginx, see below)
- HTTPS is strongly recommended. Session cookies are marked `Secure` automatically when the site is served over HTTPS.

## Configuration

Settings are read from **environment variables first**, then from `pinkventory/config.php`.

| Key           | Meaning                                                     |
|---------------|-------------------------------------------------------------|
| `APP_ENV`     | `production` (default, hides error details) or `development` |
| `DB_HOST`     | database host                                               |
| `DB_PORT`     | database port (default 3306)                                |
| `DB_NAME`     | database name                                               |
| `DB_USER`     | database user                                               |
| `DB_PASS`     | database password                                           |
| `FORCE_HTTPS` | `true` to redirect all HTTP traffic to HTTPS                |

`config.php` is git-ignored and must never be committed. `.htaccess` also denies web access to it. Set `PINKVENTORY_CONFIG=/absolute/path/config.php` to keep the file outside the web root.

### Local development (XAMPP)

```php
// pinkventory/config.php
return [
    'APP_ENV' => 'development',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'literexia',   // your local database
    'DB_USER' => 'root',
    'DB_PASS' => '',
];
```

If your local database predates `schema.sql`, run `database/migrate_existing.sql` once.

## Deploying to production

1. **Create a database** and a dedicated database user in your host's control panel. Give that user only `SELECT, INSERT, UPDATE, DELETE` on that database. Do not use `root`.
2. **Import the schema:** phpMyAdmin → Import → `database/schema.sql`, or
   `mysql -h HOST -u ADMIN_USER -p DB_NAME < database/schema.sql`.
   Optionally import `database/sample_data.sql` for demo products.
3. **Upload** the *contents* of `pinkventory/` to the web root (`public_html/`, `htdocs/`, …). Don't upload the `database/` folder or `.git`.
4. **Configure:** copy `config.example.php` to `config.php` on the server and fill in the production values (`APP_ENV` = `production`). You can set environment variables instead if your host supports them.
5. **Enable HTTPS** (most hosts offer free Let's Encrypt), then set `FORCE_HTTPS` to `true`.
6. **Create the first admin:** visit `https://your-site/Admin.php` right away. Anyone can create the first admin; after that the page only works for signed-in admins.
7. **Smoke test:** open the landing page, sign up a user, sign in, add/edit/delete a product, use the search box, then sign in as admin and add/edit/delete a user.

### nginx

nginx ignores `.htaccess`. Add the equivalent rules:

```nginx
location ~ /(config|config\.example|bootstrap)\.php$ { deny all; }
location ~ /\.                                        { deny all; }
location ~ \.(sql|md|log)$                            { deny all; }
autoindex off;
```

## Security notes

- All queries use prepared statements.
- All output is HTML-escaped with `e()`.
- Every POST is checked for a CSRF token in `bootstrap.php`.
- Logout links carry the token too.
- The session ID is regenerated on login.
- Sessions use HttpOnly, SameSite=Lax and strict mode, and expire after 30 minutes idle on the server.
- User sign-in locks for 60 seconds after 5 failed attempts.
- No inline JavaScript: the CSP allows scripts from this site only (`script-src 'self'`).
- Security headers are sent on every page: CSP, X-Frame-Options, nosniff, Referrer-Policy and HSTS (HSTS only on HTTPS).

## Known limitations

- Ordering needs `database/migrations/001_add_orders.sql`. Until it is applied the site still works, but checkout explains that ordering is off and the order pages show an empty state.
- No real payment processing: the payment method is recorded with the order only.
- Customers cannot cancel their own orders; an admin cancels (which returns stock).
- Products have no photo column, so each product is shown with a category icon.
- No "remember me", password reset or social login.
- The admin login has no failed-attempt lockout; only the user login does. Use a strong admin password.

## Orders

Placing an order (`place_order.php` → `place_order()` in `includes/orders.php`) runs in one database transaction:

1. Lock the ordered product rows (`SELECT … FOR UPDATE`).
2. For every line: the product must exist and not be out of stock, the quantity must not exceed stock, and the price the customer saw must equal the current price. Any problem rolls back everything and the customer is sent to the cart with an explanation.
3. Save the order (status `pending`) and its items with the current price (`unit_price`), so later price changes never alter past orders.
4. Reduce stock (`UPDATE … SET stock = stock - qty WHERE stock >= qty`), then commit.

A one-time checkout token stops a refresh or double click from creating a second order.

Statuses (changed by admins on `admin_order.php`):

```
pending ──► processing ──► completed
   │             │
   └─────────────┴──► cancelled   (items go back into stock)
```

`completed` and `cancelled` are final. A status change locks the order row inside a transaction, so stock is returned exactly once even if two cancel requests arrive together. Revenue counts every order that is not cancelled.
