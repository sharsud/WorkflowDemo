# QADemo Shop (WorkflowDemo) — Instructions

Native PHP 8 e-commerce sandbox for UI and API test automation. No Node.js, npm, or build step. Styling is Tailwind CSS via CDN; behavior is vanilla JavaScript.

**Local URL:** [http://localhost/WorkflowDemo](http://localhost/WorkflowDemo)

---

## Requirements

- PHP 8+ (XAMPP includes this)
- Apache with `mod_rewrite` (optional; `.php` API files work without it)
- Write access to the `data/` folder (JSON storage)

Do **not** run `npm install`. Open the site in a browser after Apache is running.

---

## Run locally (XAMPP)

1. Confirm the project is at `D:\xampp\htdocs\WorkflowDemo`.
2. Start **Apache** in the XAMPP Control Panel.
3. Open [http://localhost/WorkflowDemo](http://localhost/WorkflowDemo) (or `index.php`).
4. JSON files are created automatically on first request if they are missing.

If PHP is not on your PATH, the XAMPP binary is typically `D:\xampp\php\php.exe`.

---

## Deploy to shared PHP hosting (e.g. InfinityFree)

1. Upload the whole `WorkflowDemo` folder (or its contents if the account document root is the site).
2. Keep relative paths as-is; do not hard-code `localhost`.
3. Ensure `data/` is writable by PHP.
4. `data/.htaccess` blocks public HTTP access to JSON files (Apache).
5. Pretty URLs such as `/api/products` need `AllowOverride` + `mod_rewrite`. If rewrite is off, call the physical files instead, for example `/api/products.php`.

---

## Demo accounts

| Username | Password | Role | Behavior |
|---|---|---|---|
| `standard_user` | `standard123` | User | Catalog, cart, checkout, own orders. Also accepts `password123`. |
| `locked_user` | `locked123` | User (locked) | Login fails with HTTP **403** and message that the account is locked. |
| `admin_user` | `admin123` | Admin | Everything above plus `admin.php` (metrics, product CRUD, order status). |

On [login.php](login.php) use **Fill demo credentials** (`data-testid`: `btn-fill-standard`, `btn-fill-locked`, `btn-fill-admin`) then **Login** (`btn-login`).

### Basic Auth (API)

Send `Authorization: Basic <base64(username:password)>` on API calls. Session cookies from a UI login also work.

| Account | Username:password | Base64 token |
|---|---|---|
| Standard (primary) | `standard_user:standard123` | `c3RhbmRhcmRfdXNlcjpzdGFuZGFyZDEyMw==` |
| Standard (alias) | `standard_user:password123` | `c3RhbmRhcmRfdXNlcjpwYXNzd29yZDEyMw==` |
| Admin | `admin_user:admin123` | `YWRtaW5fdXNlcjphZG1pbjEyMw==` |

Example:

```http
GET /WorkflowDemo/api/auth/me.php
Authorization: Basic c3RhbmRhcmRfdXNlcjpzdGFuZGFyZDEyMw==
```

---

## Using the UI

### Catalog — `index.php`

- Search: `data-testid="input-search"`
- Category: `data-testid="select-category"`
- Product card: `data-testid="product-card-{id}"` (ids 1–10 in seed data)
- Stock: `data-testid="badge-stock"` (in stock / low stock / out of stock)
- Add to cart: `data-testid="btn-add-to-cart"` (disabled when stock is 0)
- Product 7 (Northwind Parka) is **out of stock** on purpose

Guest carts are stored by session. After login, a guest cart is merged into that user.

### Product — `product.php?id={id}`

Quantity (`input-qty`) then **Add to Cart**. Invalid ids return a not-found page (`product-not-found`).

### Cart — `cart.php`

- Change qty: `btn-qty-increase`, `btn-qty-decrease`, `input-qty`
- Remove line: `btn-remove-item`
- Empty cart: `btn-clear-cart`
- Checkout: `btn-checkout`

### Checkout — `checkout.php`

Requires login. Empty cart shows `checkout-empty`.

1. Fill shipping fields (`input-fullname`, `input-email`, `input-address`, `input-city`, `input-zip`).
2. Card auto-formats in groups of 4. Test card: **4242 4242 4242 4242** (Luhn-valid). Button: `btn-fill-test-card` also fills address, expiry `12/28`, CVV `123`.
3. **Place order** (`btn-place-order`) shows the global spinner for **1.2 seconds** (WebDriverWait target), then `POST /api/orders.php`.
4. Success redirects to order history.

Spinner selectors (all pages during API work, and checkout delay):

- `id="loading-spinner"`
- `data-testid="loading-indicator"`

### Orders — `orders.php`

Requires login. Lists that user’s orders (`order-card-{id}`). Admins see all orders.

### Admin — `admin.php`

Requires `admin_user`. Metrics (`metric-orders`, `metric-revenue`, …), product create/edit/delete/stock, order status (`processing`, `shipped`, `delivered`, `cancelled`).

### Reset application state

Footer button **Reset Application State** (`data-testid="btn-reset-state"`) calls `POST api/reset.php`. It restores seed JSON (users, products, orders, empty carts/logs) and clears the PHP session. Use this in test **teardown**.

---

## REST API

Base (local): `http://localhost/WorkflowDemo/`

JSON body, `Content-Type: application/json`. Status codes used: **200**, **400**, **401**, **403**, **404**.

Hosts that block `PATCH`/`DELETE` can send `POST` plus header `X-HTTP-Method-Override: PATCH` (or `DELETE`). The UI already does this.

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `api/products.php` | No | Optional `?id=`, `?q=`, `?category=` |
| GET | `api/products.php?id={id}` | No | Single product |
| POST | `api/auth/login.php` | No | `{ "username", "password" }` |
| POST | `api/auth/logout.php` | Session | Clears session |
| GET | `api/auth/me.php` | Session or Basic | Current user |
| GET | `api/cart.php` | Session (guest ok) | Cart + totals |
| POST | `api/cart/items.php` | Session | `{ "product_id", "quantity" }`; `"set": true` replaces qty |
| DELETE | `api/cart/items.php` | Session | `{ "product_id" }` or `{ "clear": true }` |
| GET | `api/orders.php` | User | Own orders (admin: all) |
| POST | `api/orders.php` | User | Place order from cart |
| GET | `api/admin/stats.php` | Admin | Totals and revenue |
| GET | `api/admin/products.php` | Admin | Inventory |
| POST | `api/admin/products.php` | Admin | Create product |
| PATCH | `api/admin/products.php` | Admin | `{ "id", ...fields }` including `stock` |
| DELETE | `api/admin/products.php` | Admin | `{ "id" }` |
| GET | `api/admin/orders.php` | Admin | All orders |
| PATCH | `api/admin/orders.php` | Admin | `{ "id", "status" }` |
| POST | `api/reset.php` | No | Seed restore (also GET) |

Unified router: `api.php?route=products` (and the same routes as above). With rewrite enabled, `/api/products` maps to `api/products.php`.

### curl examples

Login (session cookie):

```bash
curl -c cookies.txt -X POST http://localhost/WorkflowDemo/api/auth/login.php ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"standard_user\",\"password\":\"standard123\"}"
```

Products with Basic Auth:

```bash
curl http://localhost/WorkflowDemo/api/products.php ^
  -H "Authorization: Basic c3RhbmRhcmRfdXNlcjpzdGFuZGFyZDEyMw=="
```

Add to cart:

```bash
curl -b cookies.txt -X POST http://localhost/WorkflowDemo/api/cart/items.php ^
  -H "Content-Type: application/json" ^
  -d "{\"product_id\":1,\"quantity\":1}"
```

Reset:

```bash
curl -X POST http://localhost/WorkflowDemo/api/reset.php
```

---

## Automation notes

- Prefer **`data-testid`** over CSS classes or text (Tailwind class names are not stable contracts).
- Page marker: `body[data-page]` / `data-testid="page-{catalog|product|cart|checkout|orders|login|admin}"`.
- Cart badge: `data-testid="cart-count"`.
- Toasts: `data-testid="toast"`.
- Locked login: wait for `alert-error` (not a successful redirect).
- Checkout: wait until `loading-indicator` is gone **or** `checkout-success` / navigation to `orders.php`.
- Seed product ids: **1–10**. Seed orders: **1001**, **1002** (owned by `standard_user`).
- After reset, log in again; the session is cleared.

Suggested teardown (every test class or each test):

```http
POST /WorkflowDemo/api/reset.php
```

---

## Storage

| File | Purpose |
|---|---|
| `data/users.json` | Accounts (password hashes; `standard_user` also has alias `password123`) |
| `data/products.json` | Catalog and stock |
| `data/orders.json` | Placed orders |
| `data/carts.json` | Per-user and guest carts |
| `data/logs.json` | Login attempts, orders, resets |

Do not edit these by hand during a test run unless you intend to. Use **Reset Application State** or `POST api/reset.php`.

---

## Project layout (short)

```
WorkflowDemo/
  index.php, product.php, cart.php, checkout.php, orders.php, login.php, admin.php
  api.php
  api/products.php, auth/, cart.php, cart/items.php, orders.php, admin/, reset.php
  includes/          PHP bootstrap, auth, JSON I/O, seed, API handlers, layout
  assets/js/app.js   UI + API calls
  assets/css/app.css
  assets/img/        local SVG product images
  data/              JSON “database”
  INSTRUCTIONS.md    this file
```

---

## Typical happy-path (manual or scripted)

1. Open catalog → search/filter → add in-stock item (`product-card-1`).
2. Open cart → change quantity → checkout.
3. If prompted, log in as `standard_user`.
4. Fill test card → place order → confirm spinner then order history.
5. Log out; log in as `admin_user` → change stock / order status.
6. Reset state before the next suite.
