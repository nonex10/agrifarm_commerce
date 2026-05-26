const API = 'http://localhost/agrifarm/api';

/* ── Login redirect helper ────────────────────────────────── */
function getLoginRedirect() {
  const inPages = window.location.pathname.includes('/pages/');
  return inPages ? '../pages/login.html' : 'pages/login.html';
}

/* ── Generic fetch wrapper ────────────────────────────────── */
async function apiFetch(path, method = 'GET', body = null) {
  const opts = {
    method,
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
  };
  if (body !== null) opts.body = JSON.stringify(body);
  try {
    const res  = await fetch(API + path, opts);
    const text = await res.text();
    const clean = text.trim().replace(/^\uFEFF/, '');
    try {
      return JSON.parse(clean);
    } catch (parseErr) {
      console.error('[AgriFresh] Non-JSON response from', path, ':', clean);
      return { error: `Server error (HTTP ${res.status})` };
    }
  } catch (err) {
    console.warn('[AgriFresh] API request failed:', path, err.message);
    return { error: 'Network error – is XAMPP running?' };
  }
}

/* ── Image helper ─────────────────────────────────────────── */
function resolveImage(img) {
  if (!img) return '';
  if (/^(https?:)?\/\//.test(img)) return img;
  const clean = img.replace(/^(\.\.\/|\/)+/, '');
  return 'http://localhost/agrifarm/' + clean;
}

/* ============================================================
   STATE MANAGEMENT
   ============================================================ */
const State = {
  cart:     JSON.parse(localStorage.getItem('agri_cart')     || '[]'),
  wishlist: JSON.parse(localStorage.getItem('agri_wishlist') || '[]'),
  user:     JSON.parse(localStorage.getItem('agri_user')     || 'null'),

  saveCart()     { localStorage.setItem('agri_cart',     JSON.stringify(this.cart));     },
  saveWishlist() { localStorage.setItem('agri_wishlist', JSON.stringify(this.wishlist)); },
  saveUser()     { localStorage.setItem('agri_user',     JSON.stringify(this.user));     },

  cartCount()    { return this.cart.reduce((s, i) => s + i.quantity, 0); },
  wishlistCount(){ return this.wishlist.length; },
  cartTotal()    { return this.cart.reduce((s, i) => s + i.price * i.quantity, 0); },

  addToCart(product, qty = 1) {
    if (!this.user) {
      showToast('Please sign in to add items to your cart.', 'error');
      setTimeout(() => { window.location.href = getLoginRedirect(); }, 1200);
      return false;
    }
    const existing = this.cart.find(i => i.id === product.id);
    if (existing) { existing.quantity += qty; }
    else          { this.cart.push({ ...product, quantity: qty }); }
    this.saveCart();
    updateNavBadges();
    return true;
  },

  removeFromCart(id) {
    this.cart = this.cart.filter(i => i.id !== id);
    this.saveCart();
    updateNavBadges();
  },

  updateQty(id, qty) {
    if (qty <= 0) return this.removeFromCart(id);
    const item = this.cart.find(i => i.id === id);
    if (item) { item.quantity = qty; this.saveCart(); updateNavBadges(); }
  },

  toggleWishlist(product) {
    if (!this.user) {
      showToast('Please sign in to save items to your wishlist.', 'error');
      setTimeout(() => { window.location.href = getLoginRedirect(); }, 1200);
      return false;
    }
    const idx = this.wishlist.findIndex(i => i.id === product.id);
    if (idx > -1) { this.wishlist.splice(idx, 1); }
    else          { this.wishlist.push(product); }
    this.saveWishlist();
    updateNavBadges();
    return idx === -1;
  },

  isWishlisted(id) { return this.wishlist.some(i => i.id === id); },

  logout() {
    this.user     = null;
    this.cart     = [];
    this.wishlist = [];
    this.saveUser();
    this.saveCart();
    this.saveWishlist();
    apiFetch('/auth/logout.php', 'POST').catch(() => {});
    updateNavBadges();
  },
};

/* ============================================================
   NAV BADGES
   ============================================================ */
function updateNavBadges() {
  const cartBadge = document.getElementById('cart-badge');
  const wishBadge = document.getElementById('wish-badge');
  if (cartBadge) {
    const n = State.cartCount();
    cartBadge.textContent   = n;
    cartBadge.style.display = n > 0 ? 'flex' : 'none';
  }
  if (wishBadge) {
    const n = State.wishlistCount();
    wishBadge.textContent   = n;
    wishBadge.style.display = n > 0 ? 'flex' : 'none';
  }
  const userSection = document.getElementById('user-section');
  if (userSection) renderUserMenu(userSection);
}

/* ============================================================
   NAVBAR INIT
   ============================================================ */
function initNavbar() {
  updateNavBadges();
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobile-nav');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => mobileNav.classList.toggle('open'));
  }
  document.querySelectorAll('[data-logout]').forEach(btn => {
    btn.addEventListener('click', () => {
      State.logout();
      window.location.href = 'http://localhost/agrifarm/index.html';
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

/* ============================================================
   TOAST NOTIFICATIONS
   ============================================================ */
function showToast(msg, type = 'success') {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.cssText = [
      'position:fixed', 'bottom:1.5rem', 'right:1.5rem', 'z-index:9999',
      'color:#fff', 'padding:0.7rem 1.2rem', 'border-radius:0.5rem',
      'font-size:0.875rem', 'box-shadow:0 4px 16px rgba(0,0,0,.2)',
      'transition:opacity 0.3s', 'opacity:0', 'pointer-events:none',
      "font-family:'DM Sans',sans-serif", 'max-width:320px',
    ].join(';');
    document.body.appendChild(toast);
  }
  toast.style.background = type === 'error' ? '#c0392b' : '#2e6b31';
  toast.textContent      = msg;
  toast.style.opacity    = '1';
  clearTimeout(toast._t);
  toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 2800);
}

/* ============================================================
   UI HELPERS
   ============================================================ */
function starsHTML(rating) {
  return [...Array(5)].map((_, i) =>
    `<span class="star ${i < Math.floor(rating) ? 'filled' : ''}">★</span>`
  ).join('');
}

function productCardHTML(p, basePath = '') {
  const wishlisted  = State.isWishlisted(p.id);
  const imgSrc      = resolveImage(p.image);
  const placeholder = `https://placehold.co/400x300/d4e9d4/2e6b31?text=${encodeURIComponent(p.name)}`;

  return `
    <div class="product-card" data-id="${p.id}">
      <div class="product-card-img">
        <img src="${imgSrc || placeholder}"
             alt="${p.name}" loading="lazy"
             onerror="this.src='${placeholder}'">
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
          <button class="btn btn-outline btn-icon add-cart-btn" data-id="${p.id}">🛒</button>
        </div>
      </div>
    </div>`;
}

function bindProductCards(products, container) {
  container.querySelectorAll('.add-cart-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const p = products.find(x => x.id == btn.dataset.id);
      if (p) {
        const added = State.addToCart(p, 1);
        if (added) {
          btn.textContent = '✓';
          btn.classList.add('btn-success');
          setTimeout(() => { btn.textContent = '🛒'; btn.classList.remove('btn-success'); }, 1800);
          showToast(`${p.name} added to cart`);
        }
      }
    });
  });
  container.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const p = products.find(x => x.id == btn.dataset.wish);
      if (p) {
        const added = State.toggleWishlist(p);
        if (added !== false) {
          btn.textContent = added ? '❤️' : '🤍';
          btn.classList.toggle('active', added);
          showToast(added ? 'Added to wishlist' : 'Removed from wishlist');
        }
      }
    });
  });
}

/* ============================================================
   AUTH API LAYER
   ============================================================ */
async function loginUser(email, password) {
  const data = await apiFetch('/auth/login.php', 'POST', { email, password });
  if (data.user) { State.user = data.user; State.saveUser(); }
  return data;
}

async function signupUser(name, email, password) {
  const data = await apiFetch('/auth/signup.php', 'POST', { name, email, password });
  if (data.user) { State.user = data.user; State.saveUser(); }
  return data;
}

/* ============================================================
   PRODUCTS
   ============================================================ */
const PRODUCTS = [
  { id:1,  name:'Organic Tomatoes', category:'Vegetables', price:120,  farmer:'Ram Bahadur Farm, Chitwan',     description:'Freshly picked organic tomatoes grown without pesticides.', rating:4.5, reviews:128, image:'uploads/tomato.jpg'   },
  { id:2,  name:'Fresh Spinach',    category:'Vegetables', price:80,   farmer:'Sita Devi Organics, Bhaktapur', description:'Tender baby spinach leaves packed with nutrients.',          rating:4.2, reviews:87,  image:'uploads/spinach.jpg'  },
  { id:3,  name:'Potatoes',         category:'Vegetables', price:60,   farmer:'Himalaya Agro Farm',            description:'Fresh hill potatoes rich in taste.',                        rating:4.1, reviews:54,  image:'uploads/potato.jpg'   },
  { id:4,  name:'Carrots',          category:'Vegetables', price:90,   farmer:'Organic Valley Nepal',          description:'Crunchy sweet carrots freshly harvested.',                  rating:4.3, reviews:66,  image:'uploads/carrot.jpg'   },
  { id:5,  name:'Cabbage',          category:'Vegetables', price:50,   farmer:'Green Hill Farmers',            description:'Fresh green cabbage grown in hills.',                       rating:4.0, reviews:40,  image:'uploads/cabbage.jpg'  },
  { id:6,  name:'Onions',           category:'Vegetables', price:70,   farmer:'Terai Fresh Farm',              description:'Red onions with strong flavor.',                            rating:4.2, reviews:52,  image:'uploads/onion.jpg'    },
  { id:7,  name:'Apples',           category:'Fruits',     price:200,  farmer:'Mustang Apple Farm',            description:'Sweet and juicy apples from Mustang.',                      rating:4.7, reviews:190, image:'uploads/apple.jpg'    },
  { id:8,  name:'Bananas',          category:'Fruits',     price:100,  farmer:'Terai Fruit Valley',            description:'Fresh ripe bananas full of energy.',                        rating:4.3, reviews:112, image:'uploads/banana.jpg'   },
  { id:9,  name:'Mangoes',          category:'Fruits',     price:250,  farmer:'Nepal Mango Garden',            description:'Sweet juicy mangoes seasonal harvest.',                     rating:4.8, reviews:210, image:'uploads/mango.jpg'    },
  { id:10, name:'Milk',             category:'Dairy',      price:70,   farmer:'Happy Cow Dairy',               description:'Pure fresh cow milk delivered daily.',                      rating:4.4, reviews:95,  image:'uploads/milk.jpg'     },
  { id:11, name:'Eggs',             category:'Dairy',      price:150,  farmer:'Nepal Poultry Farm',            description:'Farm fresh white eggs rich in protein.',                    rating:4.3, reviews:88,  image:'uploads/eggs.jpg'     },
  { id:12, name:'Garlic',           category:'Vegetables', price:110,  farmer:'Himalayan Spice Farm',          description:'Strong aromatic garlic for cooking.',                       rating:4.2, reviews:61,  image:'uploads/garlic.jpg'   },
];

async function loadProducts(filters = {}) {
  const params = new URLSearchParams(filters).toString();
  const data   = await apiFetch(`/products/list.php${params ? '?' + params : ''}`);
  if (data.products && Array.isArray(data.products) && data.products.length > 0) {
    return data.products;
  }
  console.warn('[AgriFresh] Using hardcoded PRODUCTS fallback – check DB connection.');
  let products = [...PRODUCTS];
  if (filters.category) {
    products = products.filter(p => p.category.toLowerCase() === filters.category.toLowerCase());
  }
  if (filters.search) {
    const q = filters.search.toLowerCase();
    products = products.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.description.toLowerCase().includes(q) ||
      p.farmer.toLowerCase().includes(q)
    );
  }
  return products;
}

/* ============================================================
   ORDERS
   ============================================================ */
async function placeOrder(orderPayload) {
  const apiPayload = {
    items:         orderPayload.items,
    total:         orderPayload.total,
    address:       orderPayload.address,
    payment:       orderPayload.payment,
    customer_name: orderPayload.customer_name || 'Guest',
  };

  const result = await apiFetch('/cart/checkout.php', 'POST', apiPayload);

  if (result.success || result.order_id) {
    const localOrder = {
      ...orderPayload,
      id:      result.order_id || orderPayload.id,
      db_id:   result.order_id ? parseInt(result.order_id.replace('ORD-', ''), 10) : null,
      user_id: State.user ? State.user.id : null,   // tag with current user
    };
    const orders = JSON.parse(localStorage.getItem('agri_orders') || '[]');
    orders.unshift(localOrder);
    localStorage.setItem('agri_orders', JSON.stringify(orders));
  }

  return result;
}

async function loadOrders() {
  const allLocal = JSON.parse(localStorage.getItem('agri_orders') || '[]');

  // Filter localStorage orders to only those belonging to the current user
  const currentUserId  = State.user ? State.user.id : null;
  const localOrders    = allLocal.filter(o => o.user_id === currentUserId);

  if (!State.user) return localOrders;

  const data = await apiFetch('/orders/list.php');
  if (data.error) {
    console.warn('[AgriFresh] Could not fetch orders from DB:', data.error);
    return localOrders;
  }

  // Merge DB orders with any localStorage-only entries for this user
  const dbOrders  = data.orders || [];
  const dbIds     = new Set(dbOrders.map(o => String(o.id)));
  const localOnly = localOrders.filter(o => !dbIds.has(String(o.db_id)));
  return [...dbOrders, ...localOnly];
}

async function deleteOrder(orderId, dbId) {
  const allLocal = JSON.parse(localStorage.getItem('agri_orders') || '[]');
  const updated  = allLocal.filter(o => o.id !== orderId);
  localStorage.setItem('agri_orders', JSON.stringify(updated));

  if (dbId && State.user) {
    const result = await apiFetch('/orders/delete.php', 'POST', { order_id: dbId });
    if (result.error) {
      console.warn('[AgriFresh] Server delete failed:', result.error);
      const revert   = JSON.parse(localStorage.getItem('agri_orders') || '[]');
      const original = allLocal.find(o => o.id === orderId);
      if (original) { revert.unshift(original); localStorage.setItem('agri_orders', JSON.stringify(revert)); }
      return false;
    }
  }
  return true;
}