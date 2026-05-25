/* ============================================
   Shared Layout HTML (injected by each page)
   ============================================ */

function getNavbarHTML(basePath = '') {
  return `
<nav class="navbar">
  <div class="container">
    <div class="navbar-inner">
      <a href="${basePath}index.html" class="navbar-logo">
        <img src="${basePath}uploads/images.png" class="navbar-logo-icon-img" alt="AgriFresh Logo">
<span>AgriFresh</span>
      </a>
      <div class="navbar-links">
        <a href="${basePath}index.html">Home</a>
        <a href="${basePath}pages/products.html">Products</a>
        <a href="${basePath}pages/orders.html">Orders</a>
        <a href="${basePath}pages/about.html">About</a>
        <a href="${basePath}pages/contact.html">Contact</a>
      </div>
      <div class="navbar-actions">
        <a href="${basePath}pages/wishlist.html" class="navbar-icon-btn" aria-label="Wishlist">
          🤍
          <span id="wish-badge" class="nav-badge nav-badge-wish" style="display:none">0</span>
        </a>
        <a href="${basePath}pages/cart.html" class="navbar-icon-btn" aria-label="Cart">
          🛒
          <span id="cart-badge" class="nav-badge" style="display:none">0</span>
        </a>
        <div id="user-section" class="navbar-desktop-auth"></div>
        <button class="hamburger" id="hamburger" aria-label="Menu">☰</button>
      </div>
    </div>
    <div class="mobile-nav" id="mobile-nav">
      <a href="${basePath}index.html">Home</a>
      <a href="${basePath}pages/products.html">Products</a>
      <a href="${basePath}pages/orders.html">Orders</a>
      <a href="${basePath}pages/about.html">About</a>
      <a href="${basePath}pages/contact.html">Contact</a>
      <a href="${basePath}pages/wishlist.html">Wishlist</a>
      <div id="mobile-auth-section"></div>
    </div>
  </div>
</nav>`;
}

function getFooterHTML(basePath = '') {
  return `
<footer>
  <div class="container footer-inner">
    <div class="footer-grid">
      <div>
        <h3><span class="footer-logo-icon">A</span> AgriFresh</h3>
        <p>Connecting customers across Nepal with fresh produce delivered directly from farm to table.</p>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><a href="${basePath}index.html">Home</a></li>
          <li><a href="${basePath}pages/products.html">Products</a></li>
          <li><a href="${basePath}pages/about.html">About Us</a></li>
        </ul>
      </div>
      <div>
        <h4>Support</h4>
        <ul>
          <li><a href="${basePath}pages/contact.html">Contact Us</a></li>
          <li><a href="${basePath}pages/about.html">FAQs</a></li>
          <li><a href="${basePath}pages/about.html">Delivery Info</a></li>
          <li><a href="${basePath}pages/about.html">Returns</a></li>
        </ul>
      </div>
      <div>
        <h4>Legal</h4>
        <ul>
          <li><a href="${basePath}pages/about.html">Privacy Policy</a></li>
          <li><a href="${basePath}pages/about.html">Terms of Service</a></li>
          <li><a href="${basePath}pages/about.html">Cookie Policy</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 AgriFresh Nepal. All rights reserved.</p>
    </div>
  </div>
</footer>`;
}

function renderUserMenu(container) {
  const mobileAuth = document.getElementById('mobile-auth-section');
  if (State.user) {
    container.innerHTML = `
      <div class="user-menu" style="display:none;" id="user-menu-wrapper">
        <button class="user-menu-btn" id="user-menu-btn">
          👤 <span>${State.user.name.split(' ')[0]}</span>
        </button>
        <div class="user-dropdown" id="user-dropdown">
          <div class="user-dropdown-header">
            <strong style="font-size:.85rem">${State.user.name}</strong>
            <p>${State.user.email}</p>
          </div>
          <a href="../pages/orders.html">📦 Order History</a>
          <button class="sign-out" data-logout>🚪 Sign Out</button>
        </div>
      </div>`;
    // show it
    const wrapper = document.getElementById('user-menu-wrapper');
    if (wrapper) { wrapper.style.display = ''; }
    if (mobileAuth) {
      mobileAuth.innerHTML = `
        <div style="padding:.5rem 1rem; border-top:1px solid var(--border); font-size:.85rem;">
          <strong>${State.user.name}</strong><br>
          <span style="color:var(--muted-fg)">${State.user.email}</span>
        </div>
        <button class="sign-out" data-logout style="color:var(--destructive)">🚪 Sign Out</button>`;
    }
    document.querySelectorAll('[data-logout]').forEach(btn => {
      btn.addEventListener('click', () => { State.logout(); window.location.reload(); });
    });
    // dropdown toggle
    const btn = document.getElementById('user-menu-btn');
    const dd = document.getElementById('user-dropdown');
    if (btn && dd) {
      btn.addEventListener('click', e => { e.stopPropagation(); dd.classList.toggle('open'); });
      document.addEventListener('click', () => dd.classList.remove('open'));
    }
  } else {
    container.innerHTML = `<a href="../pages/login.html" class="btn btn-outline btn-sm">Sign In</a>`;
    if (mobileAuth) {
      mobileAuth.innerHTML = `<a href="../pages/login.html" style="color:var(--fg)">Sign In</a>`;
    }
  }
}

function injectLayout(basePath = '') {
  const navEl = document.getElementById('navbar-placeholder');
  const footEl = document.getElementById('footer-placeholder');
  if (navEl) navEl.outerHTML = getNavbarHTML(basePath);
  if (footEl) footEl.outerHTML = getFooterHTML(basePath);

  // render user section
  const userSection = document.getElementById('user-section');
  if (userSection) renderUserMenu(userSection);
  initNavbar();
}
