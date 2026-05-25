/* ============================================
   AgriFresh – Shared App Logic
   ============================================ */

// ---- State ----
const State = {
  cart: JSON.parse(localStorage.getItem('agri_cart') || '[]'),
  wishlist: JSON.parse(localStorage.getItem('agri_wishlist') || '[]'),
  user: JSON.parse(localStorage.getItem('agri_user') || 'null'),

  saveCart()    { localStorage.setItem('agri_cart', JSON.stringify(this.cart)); },
  saveWishlist(){ localStorage.setItem('agri_wishlist', JSON.stringify(this.wishlist)); },
  saveUser()    { localStorage.setItem('agri_user', JSON.stringify(this.user)); },

  cartCount()     { return this.cart.reduce((s, i) => s + i.quantity, 0); },
  wishlistCount() { return this.wishlist.length; },
  cartTotal()     { return this.cart.reduce((s, i) => s + i.price * i.quantity, 0); },

  addToCart(product, qty = 1) {
    const existing = this.cart.find(i => i.id === product.id);
    if (existing) { existing.quantity += qty; }
    else { this.cart.push({ ...product, quantity: qty }); }
    this.saveCart();
    updateNavBadges();
  },
  removeFromCart(id) {
    this.cart = this.cart.filter(i => i.id !== id);
    this.saveCart();
    updateNavBadges();
  },
  updateQty(id, qty) {
    if (qty <= 0) { this.removeFromCart(id); return; }
    const item = this.cart.find(i => i.id === id);
    if (item) { item.quantity = qty; this.saveCart(); updateNavBadges(); }
  },
  toggleWishlist(product) {
    const idx = this.wishlist.findIndex(i => i.id === product.id);
    if (idx > -1) { this.wishlist.splice(idx, 1); }
    else { this.wishlist.push(product); }
    this.saveWishlist();
    updateNavBadges();
    return idx === -1; // true = added
  },
  isWishlisted(id) { return this.wishlist.some(i => i.id === id); },
  logout() {
    this.user = null;
    this.saveUser();
    updateNavBadges();
  }
};

// ---- Nav badge update ----
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
  // Update user menu
  const userSection = document.getElementById('user-section');
  if (userSection) renderUserMenu(userSection);
}

// ---- Navbar setup ----
function initNavbar() {
  updateNavBadges();

  // Hamburger
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobile-nav');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      mobileNav.classList.toggle('open');
    });
  }

  // User dropdown
  const userMenuBtn = document.getElementById('user-menu-btn');
  const userDropdown = document.getElementById('user-dropdown');
  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
  }

  // Logout buttons
  document.querySelectorAll('[data-logout]').forEach(btn => {
    btn.addEventListener('click', () => {
      State.logout();
      window.location.href = '../index.html';
    });
  });

  // Active link
  const path = window.location.pathname;
  document.querySelectorAll('.navbar-links a, .mobile-nav a').forEach(a => {
    if (a.getAttribute('href') && path.endsWith(a.getAttribute('href').replace('../', '').replace('./', ''))) {
      a.classList.add('active');
    }
  });
}

// ---- Toast notification ----
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
  toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 2500);
}

// ---- Stars HTML ----
function starsHTML(rating) {
  return [...Array(5)].map((_, i) =>
    `<span class="star ${i < Math.floor(rating) ? 'filled' : ''}">★</span>`
  ).join('');
}

// ---- Product card HTML ----
function productCardHTML(p, basePath = '') {
  const wishlisted = State.isWishlisted(p.id);
  return `
    <div class="product-card" data-id="${p.id}">
      <div class="product-card-img">
        <img src="${p.image || `https://placehold.co/400x300/d4e9d4/2e6b31?text=${encodeURIComponent(p.name)}`}" alt="${p.name}" loading="lazy" onerror="this.src='https://placehold.co/400x300/d4e9d4/2e6b31?text=Product'">
        <span class="badge product-category-badge">${p.category}</span>
        <button class="wishlist-btn ${wishlisted ? 'active' : ''}" data-wish="${p.id}" aria-label="Toggle wishlist">
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
          <button class="btn btn-outline btn-icon add-cart-btn" data-id="${p.id}" aria-label="Add to cart" title="Add to cart">🛒</button>
        </div>
      </div>
    </div>
  `;
}

// ---- Bind product card events ----
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
        btn.classList.remove('btn-outline');
        setTimeout(() => { btn.textContent = '🛒'; btn.classList.remove('btn-success'); btn.classList.add('btn-outline'); }, 1800);
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
        showToast(added ? `Added to wishlist` : `Removed from wishlist`);
      }
    });
  });
}

// ---- Mock Products Data ----
const PRODUCTS = [
  { id: 1, name: 'Organic Tomatoes', category: 'Vegetables', price: 120, farmer: 'Ram Bahadur Farm, Chitwan', description: 'Freshly picked organic tomatoes grown without pesticides.', rating: 4.5, reviews: 128, image: 'https://images.unsplash.com/photo-1546470427-e26264be0b0d?w=400&q=80' },
  { id: 2, name: 'Fresh Spinach', category: 'Vegetables', price: 80, farmer: 'Sita Devi Organics, Bhaktapur', description: 'Tender baby spinach leaves packed with nutrients.', rating: 4.2, reviews: 87, image: 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=400&q=80' },
  { id: 3, name: 'Himalayan Honey', category: 'Dairy & Honey', price: 650, farmer: 'High Himalaya Apiaries, Mustang', description: 'Pure raw honey collected from high-altitude wildflowers.', rating: 5, reviews: 204, image: 'https://images.unsplash.com/photo-1558642452-9d2a7deb7f62?w=400&q=80' },
  { id: 4, name: 'Brown Rice (5kg)', category: 'Grains', price: 480, farmer: 'Terai Harvest Co., Rupandehi', description: 'Unprocessed whole grain brown rice from the fertile Terai.', rating: 4.3, reviews: 56, image: 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=400&q=80' },
  { id: 5, name: 'Farm Eggs (12pcs)', category: 'Dairy & Honey', price: 200, farmer: 'Happy Hen Farm, Lalitpur', description: 'Free-range eggs from hens raised on natural feed.', rating: 4.7, reviews: 312, image: 'https://images.unsplash.com/photo-1518569656558-1f25e69d2049?w=400&q=80' },
  { id: 6, name: 'Purple Carrots', category: 'Vegetables', price: 95, farmer: 'Rainbow Roots, Kavre', description: 'Heirloom purple carrots rich in antioxidants and flavor.', rating: 4.1, reviews: 43, image: 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400&q=80' },
  { id: 7, name: 'Green Lentils (2kg)', category: 'Grains', price: 320, farmer: 'Dal House, Dhading', description: 'Premium green lentils high in protein and fiber.', rating: 4.4, reviews: 98, image: 'https://images.unsplash.com/photo-1614961908067-3c9cbcaa5d42?w=400&q=80' },
  { id: 8, name: 'Fresh Ginger', category: 'Spices', price: 150, farmer: 'Spice Valley, Ilam', description: 'Aromatic fresh ginger root with intense flavor.', rating: 4.6, reviews: 74, image: 'https://images.unsplash.com/photo-1615485500704-8e990f9900f7?w=400&q=80' },
  { id: 9, name: 'Organic Milk (1L)', category: 'Dairy & Honey', price: 110, farmer: 'Green Pastures Dairy, Pokhara', description: 'Fresh whole milk from grass-fed cows with no additives.', rating: 4.8, reviews: 445, image: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=400&q=80' },
  { id: 10, name: 'Turmeric Powder', category: 'Spices', price: 280, farmer: 'Golden Root Farm, Sindhuli', description: 'Stone-ground turmeric with high curcumin content.', rating: 4.9, reviews: 167, image: 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=400&q=80' },
  { id: 11, name: 'Baby Potatoes', category: 'Vegetables', price: 130, farmer: 'Hill Side Farm, Solukhumbu', description: 'Tender baby potatoes with thin edible skin.', rating: 4.3, reviews: 61, image: 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?w=400&q=80' },
  { id: 12, name: 'Apple Cider Vinegar', category: 'Others', price: 390, farmer: 'Himalayan Orchard, Jumla', description: 'Raw unfiltered apple cider vinegar with the mother.', rating: 4.5, reviews: 88, image: 'https://images.unsplash.com/photo-1589642380614-4a8c2776b4d6?w=400&q=80' },
];
