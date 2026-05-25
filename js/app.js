/* ============================================
   AgriFresh – Shared App Logic (API + Local State)
   ============================================ */

const API = 'http://localhost/agrifresh/api';

async function apiFetch(path, method = 'GET', body = null) {
  const opts = {
    method,
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' }
  };

  if (body) opts.body = JSON.stringify(body);

  const res = await fetch(API + path, opts);
  return res.json();
}

/* =========================
   STATE MANAGEMENT
========================= */

const State = {
  cart: JSON.parse(localStorage.getItem('agri_cart') || '[]'),
  wishlist: JSON.parse(localStorage.getItem('agri_wishlist') || '[]'),
  user: JSON.parse(localStorage.getItem('agri_user') || 'null'),

  saveCart() {
    localStorage.setItem('agri_cart', JSON.stringify(this.cart));
  },

  saveWishlist() {
    localStorage.setItem('agri_wishlist', JSON.stringify(this.wishlist));
  },

  saveUser() {
    localStorage.setItem('agri_user', JSON.stringify(this.user));
  },

  cartCount() {
    return this.cart.reduce((s, i) => s + i.quantity, 0);
  },

  wishlistCount() {
    return this.wishlist.length;
  },

  cartTotal() {
    return this.cart.reduce((s, i) => s + i.price * i.quantity, 0);
  },

  addToCart(product, qty = 1) {
    const existing = this.cart.find(i => i.id === product.id);
    if (existing) existing.quantity += qty;
    else this.cart.push({ ...product, quantity: qty });

    this.saveCart();
    updateNavBadges();
  },

  removeFromCart(id) {
    this.cart = this.cart.filter(i => i.id !== id);
    this.saveCart();
    updateNavBadges();
  },

  updateQty(id, qty) {
    if (qty <= 0) return this.removeFromCart(id);

    const item = this.cart.find(i => i.id === id);
    if (item) {
      item.quantity = qty;
      this.saveCart();
      updateNavBadges();
    }
  },

  toggleWishlist(product) {
    const idx = this.wishlist.findIndex(i => i.id === product.id);

    if (idx > -1) this.wishlist.splice(idx, 1);
    else this.wishlist.push(product);

    this.saveWishlist();
    updateNavBadges();

    return idx === -1;
  },

  isWishlisted(id) {
    return this.wishlist.some(i => i.id === id);
  },

  logout() {
    this.user = null;
    this.saveUser();
    updateNavBadges();
  }
};

/* =========================
   NAV BADGES
========================= */

function updateNavBadges() {
  const cartBadge = document.getElementById('cart-badge');
  const wishBadge = document.getElementById('wish-badge');

  if (cartBadge) {
    const n = State.cartCount();
    cartBadge.textContent = n;
    cartBadge.style.display = n > 0 ? 'flex' : 'none';
  }

  if (wishBadge) {
    const n = State.wishlistCount();
    wishBadge.textContent = n;
    wishBadge.style.display = n > 0 ? 'flex' : 'none';
  }

  const userSection = document.getElementById('user-section');
  if (userSection) renderUserMenu(userSection);
}

/* =========================
   NAVBAR INIT
========================= */

function initNavbar() {
  updateNavBadges();

  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobile-nav');

  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      mobileNav.classList.toggle('open');
    });
  }

  const userMenuBtn = document.getElementById('user-menu-btn');
  const userDropdown = document.getElementById('user-dropdown');

  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', () => {
      userDropdown.classList.remove('open');
    });
  }

  document.querySelectorAll('[data-logout]').forEach(btn => {
    btn.addEventListener('click', () => {
      State.logout();
      window.location.href = '../index.html';
    });
  });

  const path = window.location.pathname;

  document.querySelectorAll('.navbar-links a, .mobile-nav a').forEach(a => {
    const href = a.getAttribute('href');
    if (href && path.endsWith(href.replace('../', '').replace('./', ''))) {
      a.classList.add('active');
    }
  });
}

/* =========================
   TOAST
========================= */

function showToast(msg, type = 'success') {
  let toast = document.getElementById('toast');

  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.cssText = `
      position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
      background: ${type === 'error' ? '#c0392b' : '#2e6b31'};
      color:#fff; padding:0.7rem 1.2rem; border-radius:0.5rem;
      font-size:0.875rem; box-shadow:0 4px 16px rgba(0,0,0,.2);
      transition: opacity 0.3s; opacity:0; pointer-events:none;
      font-family: 'DM Sans', sans-serif;
    `;
    document.body.appendChild(toast);
  }

  toast.textContent = msg;
  toast.style.opacity = '1';

  clearTimeout(toast._t);
  toast._t = setTimeout(() => {
    toast.style.opacity = '0';
  }, 2500);
}

/* =========================
   UI HELPERS
========================= */

function starsHTML(rating) {
  return [...Array(5)].map((_, i) =>
    `<span class="star ${i < Math.floor(rating) ? 'filled' : ''}">★</span>`
  ).join('');
}

function productCardHTML(p, basePath = '') {
  const wishlisted = State.isWishlisted(p.id);

  return `
    <div class="product-card" data-id="${p.id}">
      <div class="product-card-img">
        <img src="${p.image || `https://placehold.co/400x300/d4e9d4/2e6b31?text=${encodeURIComponent(p.name)}`}"
             alt="${p.name}" loading="lazy">
        <span class="badge product-category-badge">${p.category}</span>

        <button class="wishlist-btn ${wishlisted ? 'active' : ''}" data-wish="${p.id}">
          ${wishlisted ? '❤️' : '🤍'}
        </button>
      </div>

      <div class="product-card-body">
        <a href="${basePath}pages/product.html?id=${p.id}">
          <h3>${p.name}</h3>
        </a>

        <p class="product-farmer">🌾 ${p.farmer}</p>
        <p class="product-desc">${p.description}</p>

        <div class="stars">
          ${starsHTML(p.rating)}
          <span class="review-count">(${p.reviews})</span>
        </div>

        <div class="product-footer">
          <span class="product-price">Rs. ${p.price.toLocaleString('en-NP')}</span>
          <button class="btn btn-outline btn-icon add-cart-btn" data-id="${p.id}">
            🛒
          </button>
        </div>
      </div>
    </div>
  `;
}

function bindProductCards(products, container) {
  container.querySelectorAll('.add-cart-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();

      const id = btn.dataset.id;
      const p = products.find(x => x.id == id);

      if (p) {
        State.addToCart(p, 1);

        btn.textContent = '✓';
        btn.classList.add('btn-success');

        setTimeout(() => {
          btn.textContent = '🛒';
          btn.classList.remove('btn-success');
        }, 1800);

        showToast(`${p.name} added to cart`);
      }
    });
  });

  container.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();

      const id = btn.dataset.wish;
      const p = products.find(x => x.id == id);

      if (p) {
        const added = State.toggleWishlist(p);

        btn.textContent = added ? '❤️' : '🤍';
        btn.classList.toggle('active', added);

        showToast(added ? 'Added to wishlist' : 'Removed from wishlist');
      }
    });
  });
}

/* =========================
   API LAYER
========================= */

async function loginUser(email, password) {
  const data = await apiFetch('/auth/login.php', 'POST', { email, password });

  if (data.user) {
    State.user = data.user;
    State.saveUser();
  }

  return data;
}

async function signupUser(name, email, password) {
  const data = await apiFetch('/auth/signup.php', 'POST', {
    name, email, password
  });

  if (data.user) {
    State.user = data.user;
    State.saveUser();
  }

  return data;
}

const PRODUCTS = [
  { id: 1, name: 'Organic Tomatoes', category: 'Vegetables', price: 120, farmer: 'Ram Bahadur Farm, Chitwan', description: 'Freshly picked organic tomatoes grown without pesticides.', rating: 4.5, reviews: 128, image: 'https://images.unsplash.com/photo-1546470427-e26264be0b0d?w=400&q=80' },
  { id: 2, name: 'Fresh Spinach', category: 'Vegetables', price: 80, farmer: 'Sita Devi Organics, Bhaktapur', description: 'Tender baby spinach leaves packed with nutrients.', rating: 4.2, reviews: 87, image: 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=400&q=80' }
];

async function loadProducts(filters = {}) {
  const params = new URLSearchParams(filters).toString();
  const data = await apiFetch(`/products/list.php?${params}`);

  return data.products || PRODUCTS;
}

async function placeOrder(orderData) {
  return apiFetch('/cart/checkout.php', 'POST', orderData);
}