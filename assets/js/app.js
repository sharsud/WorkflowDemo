(function () {
  const api = (path) => path.replace(/^\//, '');

  const spinner = document.getElementById('loading-spinner');
  const toastEl = document.getElementById('toast');

  function showLoading(on) {
    if (!spinner) return;
    spinner.classList.toggle('hidden', !on);
    spinner.setAttribute('aria-busy', on ? 'true' : 'false');
  }

  function toast(message, ok) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.classList.remove('hidden', 'toast-ok', 'toast-error');
    toastEl.classList.add(ok ? 'toast-ok' : 'toast-error');
    toastEl.setAttribute('data-testid', 'toast');
    setTimeout(() => toastEl.classList.add('hidden'), 2800);
  }

  async function request(path, options) {
    const opts = Object.assign({ headers: { 'Content-Type': 'application/json' } }, options || {});
    const res = await fetch(api(path), Object.assign({ credentials: 'same-origin' }, opts));
    let data = {};
    try {
      data = await res.json();
    } catch (e) {
      data = { ok: false, error: 'Invalid JSON response' };
    }
    data._status = res.status;
    return data;
  }

  async function refreshCartCount() {
    const data = await request('api/cart.php');
    const el = document.getElementById('cart-count');
    if (el && data.cart) el.textContent = String(data.cart.count || 0);
    return data.cart;
  }

  async function addToCart(productId, quantity) {
    showLoading(true);
    try {
      const data = await request('api/cart/items.php', {
        method: 'POST',
        body: JSON.stringify({ product_id: Number(productId), quantity: Number(quantity || 1) }),
      });
      if (!data.ok) {
        toast(data.error || 'Unable to add to cart', false);
        return;
      }
      const el = document.getElementById('cart-count');
      if (el) el.textContent = String(data.cart.count);
      toast('Added to cart', true);
    } finally {
      showLoading(false);
    }
  }

  document.addEventListener('click', (ev) => {
    const fill = ev.target.closest('[data-testid="btn-fill-standard"], [data-testid="btn-fill-locked"], [data-testid="btn-fill-admin"]');
    if (fill) {
      document.getElementById('input-username').value = fill.getAttribute('data-username');
      document.getElementById('input-password').value = fill.getAttribute('data-password');
    }
  });

  const logoutBtn = document.getElementById('btn-logout');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      showLoading(true);
      await request('api/auth/logout.php', { method: 'POST', body: '{}' });
      showLoading(false);
      window.location.href = api('index.php');
    });
  }

  const resetBtn = document.getElementById('btn-reset-state');
  if (resetBtn) {
    resetBtn.addEventListener('click', async () => {
      showLoading(true);
      const data = await request('api/reset.php', { method: 'POST', body: '{}' });
      showLoading(false);
      toast(data.message || 'State reset', !!data.ok);
      setTimeout(() => window.location.reload(), 600);
    });
  }

  const page = document.body.getAttribute('data-page');

  if (page === 'catalog') {
    const grid = document.getElementById('product-grid');
    const empty = document.getElementById('catalog-empty');
    const search = document.getElementById('input-search');
    const category = document.getElementById('select-category');

    function filterCards() {
      const q = (search.value || '').toLowerCase();
      const cat = category.value;
      let visible = 0;
      grid.querySelectorAll('[data-testid^="product-card-"]').forEach((card) => {
        const matchCat = !cat || card.getAttribute('data-category') === cat;
        const matchQ = !q || (card.getAttribute('data-name') || '').includes(q);
        const show = matchCat && matchQ;
        card.classList.toggle('hidden', !show);
        if (show) visible += 1;
      });
      empty.classList.toggle('hidden', visible !== 0);
    }

    search.addEventListener('input', filterCards);
    category.addEventListener('change', filterCards);

    grid.addEventListener('click', (ev) => {
      const btn = ev.target.closest('[data-testid="btn-add-to-cart"]');
      if (btn && !btn.disabled) addToCart(btn.getAttribute('data-product-id'), 1);
    });
  }

  if (page === 'product') {
    const btn = document.querySelector('[data-testid="btn-add-to-cart"]');
    if (btn) {
      btn.addEventListener('click', () => {
        const qty = document.getElementById('input-qty');
        addToCart(btn.getAttribute('data-product-id'), qty ? qty.value : 1);
      });
    }
  }

  if (page === 'cart') {
    async function mutate(body, method) {
      showLoading(true);
      const headers = { 'Content-Type': 'application/json' };
      if ((method || 'POST') === 'DELETE') headers['X-HTTP-Method-Override'] = 'DELETE';
      const data = await request('api/cart/items.php', {
        method: 'POST',
        headers,
        body: JSON.stringify(body),
      });
      showLoading(false);
      if (!data.ok) {
        toast(data.error || 'Cart update failed', false);
        return;
      }
      window.location.reload();
    }

    document.getElementById('btn-clear-cart')?.addEventListener('click', () => mutate({ clear: true }, 'DELETE'));

    document.getElementById('cart-items')?.addEventListener('click', (ev) => {
      const dec = ev.target.closest('[data-testid="btn-qty-decrease"]');
      const inc = ev.target.closest('[data-testid="btn-qty-increase"]');
      const remove = ev.target.closest('[data-testid="btn-remove-item"]');
      if (remove) {
        mutate({ product_id: Number(remove.getAttribute('data-product-id')) }, 'DELETE');
      }
      if (dec || inc) {
        const btn = dec || inc;
        const row = btn.closest('[data-testid^="cart-item-"]');
        const input = row.querySelector('[data-testid="input-qty"]');
        let qty = Number(input.value || 1);
        qty = inc ? qty + 1 : Math.max(1, qty - 1);
        mutate({ product_id: Number(btn.getAttribute('data-product-id')), quantity: qty, set: true });
      }
    });

    document.getElementById('cart-items')?.addEventListener('change', (ev) => {
      const input = ev.target.closest('[data-testid="input-qty"]');
      if (!input) return;
      mutate({ product_id: Number(input.getAttribute('data-product-id')), quantity: Number(input.value), set: true });
    });
  }

  if (page === 'login') {
    const form = document.getElementById('form-login');
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const error = document.getElementById('login-error');
      const success = document.getElementById('login-success');
      error.classList.add('hidden');
      success.classList.add('hidden');
      showLoading(true);
      const data = await request('api/auth/login.php', {
        method: 'POST',
        body: JSON.stringify({
          username: document.getElementById('input-username').value,
          password: document.getElementById('input-password').value,
        }),
      });
      showLoading(false);
      if (!data.ok) {
        error.textContent = data.error || 'Login failed';
        error.classList.remove('hidden');
        return;
      }
      success.textContent = 'Welcome, ' + data.user.username;
      success.classList.remove('hidden');
      const redirect = document.getElementById('input-redirect').value || (data.user.role === 'admin' ? 'admin.php' : 'index.php');
      window.location.href = api(redirect);
    });
  }

  if (page === 'checkout') {
    const card = document.getElementById('input-card');
    const expiry = document.getElementById('input-expiry');
    function formatCard(value) {
      return value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
    }
    card?.addEventListener('input', () => {
      card.value = formatCard(card.value);
    });
    expiry?.addEventListener('input', () => {
      let v = expiry.value.replace(/\D/g, '').slice(0, 4);
      if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
      expiry.value = v;
    });
    document.getElementById('btn-fill-test-card')?.addEventListener('click', () => {
      card.value = '4242 4242 4242 4242';
      expiry.value = '12/28';
      document.getElementById('input-cvv').value = '123';
      document.getElementById('input-address').value = '101 Market Street';
      document.getElementById('input-city').value = 'Austin';
      document.getElementById('input-zip').value = '78701';
    });

    document.getElementById('form-checkout')?.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const errBox = document.getElementById('checkout-error');
      errBox.classList.add('hidden');
      showLoading(true);
      await new Promise((resolve) => setTimeout(resolve, 1200));
      const payload = {
        name: document.getElementById('input-fullname').value,
        email: document.getElementById('input-email').value,
        address: document.getElementById('input-address').value,
        city: document.getElementById('input-city').value,
        zip: document.getElementById('input-zip').value,
        card: document.getElementById('input-card').value,
        expiry: document.getElementById('input-expiry').value,
        cvv: document.getElementById('input-cvv').value,
      };
      const data = await request('api/orders.php', { method: 'POST', body: JSON.stringify(payload) });
      showLoading(false);
      if (!data.ok) {
        errBox.textContent = data.error || 'Checkout failed';
        errBox.classList.remove('hidden');
        if (data.fields) {
          Object.keys(data.fields).forEach((key) => {
            const map = { name: 'fullname', email: 'email', address: 'address', city: 'city', zip: 'zip', card: 'card', expiry: 'expiry', cvv: 'cvv' };
            const el = document.querySelector('[data-testid="error-' + (map[key] || key) + '"]');
            if (el) {
              el.textContent = data.fields[key];
              el.classList.remove('hidden');
            }
          });
        }
        return;
      }
      const success = document.getElementById('checkout-success');
      success.textContent = 'Order #' + data.order.id + ' placed. Status: ' + data.order.status;
      success.classList.remove('hidden');
      toast('Order placed', true);
      setTimeout(() => { window.location.href = api('orders.php'); }, 900);
    });
  }

  if (page === 'admin') {
    const form = document.getElementById('form-product');

    document.getElementById('btn-add-product')?.addEventListener('click', () => {
      form.classList.remove('hidden');
      document.getElementById('input-product-id').value = '';
      form.reset();
    });
    document.getElementById('btn-cancel-product')?.addEventListener('click', () => form.classList.add('hidden'));

    form?.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const id = document.getElementById('input-product-id').value;
      const payload = {
        id: id ? Number(id) : undefined,
        name: document.getElementById('input-product-name').value,
        category: document.getElementById('input-product-category').value,
        price: Number(document.getElementById('input-product-price').value),
        stock: Number(document.getElementById('input-product-stock').value),
        sku: document.getElementById('input-product-sku').value,
        description: document.getElementById('input-product-description').value,
      };
      showLoading(true);
      const path = 'api/admin/products.php';
      const data = id
        ? await request(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PATCH' },
            body: JSON.stringify(payload),
          })
        : await request(path, { method: 'POST', body: JSON.stringify(payload) });
      showLoading(false);
      if (!data.ok) {
        toast(data.error || 'Save failed', false);
        return;
      }
      window.location.reload();
    });

    document.getElementById('tbody-products')?.addEventListener('click', async (ev) => {
      const edit = ev.target.closest('[data-testid="btn-edit-product"]');
      const del = ev.target.closest('[data-testid="btn-delete-product"]');
      const stockBtn = ev.target.closest('[data-testid="btn-update-stock"]');
      if (stockBtn) {
        const id = stockBtn.getAttribute('data-product-id');
        const input = document.querySelector('[data-testid="input-stock-' + id + '"]');
        showLoading(true);
        const data = await request('api/admin/products.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PATCH' },
          body: JSON.stringify({ id: Number(id), stock: Number(input.value) }),
        });
        showLoading(false);
        toast(data.ok ? 'Stock updated' : data.error, !!data.ok);
        if (data.ok) window.location.reload();
      }
      if (del) {
        showLoading(true);
        await request('api/admin/products.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'DELETE' },
          body: JSON.stringify({ id: Number(del.getAttribute('data-product-id')) }),
        });
        showLoading(false);
        window.location.reload();
      }
      if (edit) {
        const row = edit.closest('tr');
        form.classList.remove('hidden');
        document.getElementById('input-product-id').value = edit.getAttribute('data-product-id');
        document.getElementById('input-product-name').value = row.querySelector('[data-testid="admin-product-name"]').textContent.trim();
        document.getElementById('input-product-price').value = row.querySelector('[data-testid="admin-product-price"]').textContent.replace('$', '');
        document.getElementById('input-product-stock').value = row.querySelector('[data-testid^="input-stock-"]').value;
      }
    });

    document.querySelectorAll('[data-testid^="btn-update-order"]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-order-id');
        const select = document.querySelector('[data-order-id="' + id + '"][data-testid^="select-order-status"]');
        showLoading(true);
        const data = await request('api/admin/orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PATCH' },
          body: JSON.stringify({ id: Number(id), status: select.value }),
        });
        showLoading(false);
        toast(data.ok ? 'Order updated' : data.error, !!data.ok);
        if (data.ok) window.location.reload();
      });
    });
  }

  window.QADemo = { request, addToCart, refreshCartCount, showLoading, api };
})();
