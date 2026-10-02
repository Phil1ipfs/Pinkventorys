# Pinkventory

Inventory management and retail checkout system built with plain PHP and MySQL.

## Project layout

```
pinkventory/            ← web root (upload the contents of this folder)
  bootstrap.php         shared setup: config, DB, session, CSRF, auth helpers
  config.example.php    configuration template → copy to config.php
  .htaccess             blocks config/internal files, disables directory listing
  index.php             public landing page
  login.php             sign in (user + admin) / sign up
  logout.php
  homepage.php          dashboard            (signed-in users and admins)
  inventory.php         product list         (signed-in users and admins)
  add_product.php / update_product.php / delete_product.php
  Checkout.php          barcode checkout     (signed-in users and admins)
  search_api.php        JSON product search (navbar suggestions + checkout lookup)
  admin_dashboard.php   user management      (admins only)
  add_user.php / update_user.php / delete_user.php
  Admin.php             create admin accounts (first admin, then admins only)
  layout.php            shared app shell: sidebar, top bar, search, toasts, dialogs
  product_form.php      product form shared by add/update product
  app.css               design system for every signed-in page
  app.js                shared behaviour: navigation, search, toasts, dialogs, idle sign-out
  inventory.js, product.js, checkout.js, users.js   page-specific behaviour
  landing.css/.js, auth.css/.js   landing page and sign-in/sign-up/admin-setup
  icons.svg             icon sprite (Lucide icons, ISC licence) - only the icons used
  pinkventory-logo.png  logo
database/
  schema.sql            tables, keys and indexes (import into a new database)
  sample_data.sql       optional demo products
  migrate_existing.sql  one-off clean-up for databases created before schema.sql
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

- There is no sales table yet, so the dashboard shows inventory figures only (its "Sales & revenue" card says so).
- Checkout looks up real products and builds a cart, but card/cash payments aren't connected and completing a sale doesn't change stock.
- The admin login has no failed-attempt lockout; only the user login does. Use a strong admin password.
