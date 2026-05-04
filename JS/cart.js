/**
 * ================================================================
 *  cart.js — Logic giỏ hàng phía client (v3 — Sửa lỗi Logout & Sync DB)
 *  ----------------------------------------------------------------
 *  Các lỗi đã sửa:
 *    ✓ Đăng xuất → xóa sessionStorage (nhờ flag ?clear_cart=1 từ logout.php)
 *    ✓ API_URL & Checkout_URL dùng đường dẫn chính xác (tự động phát hiện gốc project)
 *    ✓ Sync DB: Luôn ưu tiên dữ liệu từ DB khi người dùng đăng nhập lại
 *    ✓ Thêm vào giỏ: Gửi request tới server ngay lập tức nếu đã đăng nhập
 * ================================================================
 */

'use strict';

const CART_KEY = 'dreambook_cart';

/**
 * Tự động xác định đường dẫn gốc của Website để các link API và Checkout luôn đúng.
 */
(function initPaths() {
    // Lấy link hiện tại (VD: http://localhost/PHP_Website_Ban_Sach/index.php)
    let href = window.location.href;
    
    // Tìm vị trí của thư mục gốc project (giả sử tên project là PHP_Website_Ban_Sach)
    // Hoặc đơn giản là lấy phần trước thư mục Customer/ hoặc Admin/
    let base = href.split('/Customer/')[0].split('/Admin/')[0];
    
    // Nếu kết thúc bằng .php (trang chủ index.php), cắt bỏ tên file
    if (base.endsWith('.php')) {
        base = base.substring(0, base.lastIndexOf('/'));
    }
    
    // Đảm bảo base kết thúc bằng dấu /
    if (!base.endsWith('/')) base += '/';

    window._CART_API_URL = base + 'Customer/cart_api.php';
    window._CHECKOUT_URL = base + 'Customer/checkout.php';
})();

/* ══ KIỂM TRA TRẠNG THÁI ════════════════════════════════════════ */

function isLoggedIn() {
    const meta = document.querySelector('meta[name="user-logged"]');
    return meta && meta.content === '1';
}

/* ══ XỬ LÝ LOGOUT (XÓA GIỎ CLIENT) ══════════════════════════════ */

(function checkClearCart() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('clear_cart') === '1') {
        // Xóa giỏ hàng trong sessionStorage khi vừa đăng xuất
        sessionStorage.removeItem(CART_KEY);
        
        // Làm sạch URL (xóa param clear_cart) để không bị xóa lặp khi F5
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]clear_cart=1/, '').replace(/^&/, '?');
        window.history.replaceState({}, document.title, newUrl);
    }
})();

/* ══ ĐỌC/GHI DỮ LIỆU ═══════════════════════════════════════════ */

function cartLoad() {
    try {
        return JSON.parse(sessionStorage.getItem(CART_KEY)) || [];
    } catch (e) {
        return [];
    }
}

function cartSave(items) {
    sessionStorage.setItem(CART_KEY, JSON.stringify(items));
}

async function apiCall(body) {
    try {
        const response = await fetch(window._CART_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await response.json();
    } catch (e) {
        console.error("Cart API Error:", e);
        return { status: 'error' };
    }
}

/* ══ THÊM VÀO GIỎ ══════════════════════════════════════════════ */

async function addToCart(card, triggerEl) {
    const d = card.dataset;
    const price = parseInt((d.price || '0').replace(/\./g, ''), 10) || 0;

    const items = cartLoad();
    const existing = items.find(i => i.id === d.id);

    if (existing) {
        existing.qty++;
    } else {
        items.push({
            id: d.id,
            name: d.name,
            price: price,
            img: d.img || '',
            qty: 1
        });
    }

    // Cập nhật giao diện ngay lập tức
    cartSave(items);
    cartUpdateBadge();
    cartRenderItems();
    flyToCart(triggerEl || card);

    // Đồng bộ lên database nếu đã đăng nhập
    if (isLoggedIn()) {
        await apiCall({ action: 'add', prod_id: d.id, qty: 1 });
    }
}

async function addToCartFromPreview() {
    if (!window._previewCard) return;
    const btn = document.querySelector('.preview-btn-cart');
    await addToCart(window._previewCard, btn);
}

/* ══ ĐỒNG BỘ HÓA DATABASE ══════════════════════════════════════ */

async function syncGuestCartToDB(guestItems) {
    if (guestItems && guestItems.length > 0) {
        // Đẩy giỏ hàng tạm lên DB
        await apiCall({ action: 'sync', items: guestItems });
    }
    // Sau đó luôn lấy lại giỏ hàng đầy đủ từ DB về máy khách
    await loadCartFromDB();
}

async function loadCartFromDB() {
    const result = await apiCall({ action: 'get' });
    if (result && result.items) {
        cartSave(result.items);
        cartUpdateBadge();
        cartRenderItems();
    }
}

/* ══ HIỆU ỨNG GIAO DIỆN ════════════════════════════════════════ */

function flyToCart(fromEl) {
    const cartIcon = document.querySelector('.cart-btn');
    if (!cartIcon || !fromEl) return;

    const from = fromEl.getBoundingClientRect();
    const to = cartIcon.getBoundingClientRect();

    const bubble = document.createElement('div');
    bubble.className = 'fly-bubble';
    bubble.innerHTML = '<i class="fa-solid fa-cart-plus"></i>';
    bubble.style.cssText = `
        top: ${from.top + from.height / 2 - 18}px;
        left: ${from.left + from.width / 2 - 18}px;
        opacity: 1;
    `;
    document.body.appendChild(bubble);

    bubble.getBoundingClientRect(); // trigger reflow

    bubble.style.top = `${to.top + to.height / 2 - 8}px`;
    bubble.style.left = `${to.left + to.width / 2 - 8}px`;
    bubble.style.width = '16px';
    bubble.style.height = '16px';
    bubble.style.opacity = '0';
    bubble.style.fontSize = '8px';

    setTimeout(() => bubble.remove(), 700);
}

function cartUpdateBadge() {
    const badge = document.querySelector('.cart-badge');
    if (!badge) return;
    const total = cartLoad().reduce((s, i) => s + i.qty, 0);
    badge.textContent = total > 99 ? '99+' : total;
    if (total > 0) {
        badge.classList.add('visible');
        badge.classList.remove('pop');
        void badge.offsetWidth;
        badge.classList.add('pop');
    } else {
        badge.classList.remove('visible');
    }
}

function cartRenderItems() {
    const wrap = document.getElementById('cartItemsWrap');
    const empty = document.getElementById('cartEmpty');
    const total = document.getElementById('cartTotal');
    if (!wrap) return;

    const items = cartLoad();
    Array.from(wrap.children).forEach(el => {
        if (!el.id || el.id !== 'cartEmpty') el.remove();
    });

    if (items.length === 0) {
        if (empty) empty.style.display = 'flex';
        if (total) total.textContent = '0 ₫';
        return;
    }
    if (empty) empty.style.display = 'none';

    let totalPrice = 0;
    items.forEach(item => {
        totalPrice += item.price * item.qty;
        const imgHtml = item.img
            ? `<img class="cart-item-img" src="${item.img}" alt="">`
            : `<div class="cart-item-img no-img"><i class="fa-solid fa-book"></i></div>`;
        const linePrice = (item.price * item.qty).toLocaleString('vi-VN') + ' ₫';

        const el = document.createElement('div');
        el.className = 'cart-item';
        el.dataset.id = item.id;
        el.innerHTML = `
            ${imgHtml}
            <div class="cart-item-info">
                <div class="cart-item-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
                <div class="cart-item-price">${linePrice}</div>
                <div class="cart-qty-ctrl">
                    <button class="cart-qty-btn" onclick="changeQty('${item.id}',-1)">−</button>
                    <span class="cart-qty-num">${item.qty}</span>
                    <button class="cart-qty-btn" onclick="changeQty('${item.id}',+1)">+</button>
                </div>
            </div>
            <button class="cart-item-del" onclick="removeItem('${item.id}')" title="Xóa">
                <i class="fa-solid fa-xmark"></i>
            </button>`;
        wrap.appendChild(el);
    });

    if (total) total.textContent = totalPrice.toLocaleString('vi-VN') + ' ₫';
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* ══ ĐIỀU KHIỂN GIỎ HÀNG ══════════════════════════════════════ */

async function changeQty(id, delta) {
    const items = cartLoad();
    const item = items.find(i => i.id === id);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        items.splice(items.indexOf(item), 1);
        if (isLoggedIn()) await apiCall({ action: 'remove', prod_id: id });
    } else {
        if (isLoggedIn()) await apiCall({ action: 'update', prod_id: id, qty: item.qty });
    }

    cartSave(items);
    cartUpdateBadge();
    cartRenderItems();
}

async function removeItem(id) {
    const items = cartLoad().filter(i => i.id !== id);
    cartSave(items);
    cartUpdateBadge();
    cartRenderItems();
    if (isLoggedIn()) await apiCall({ action: 'remove', prod_id: id });
}

async function clearCart() {
    cartSave([]);
    cartUpdateBadge();
    cartRenderItems();
    if (isLoggedIn()) await apiCall({ action: 'clear' });
}

function openCart() {
    document.getElementById('cartDrawer').classList.add('open');
    document.getElementById('cartOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    cartRenderItems();
}

function closeCart() {
    document.getElementById('cartDrawer').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

function goCheckout() {
    const items = cartLoad();
    if (items.length === 0) {
        alert('Giỏ hàng của bạn đang trống!');
        return;
    }
    closeCart();
    window.location.href = window._CHECKOUT_URL;
}

/* ══ KHỞI CHẠY ════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', async () => {
    // Nếu đã login, tải giỏ hàng từ máy chủ
    if (isLoggedIn()) {
        await loadCartFromDB();
    } else {
        // Nếu chưa, chỉ cần cập nhật badge từ sessionStorage hiện có
        cartUpdateBadge();
    }

    // Bắt sự kiện click cho các nút "Thêm vào giỏ"
    document.body.addEventListener('click', async e => {
        const btnCart = e.target.closest('.btn-add-cart');
        if (btnCart) {
            const card = btnCart.closest('.product-card');
            if (card) await addToCart(card, btnCart);
        }
        const btnPreviewCart = e.target.closest('.preview-btn-cart');
        if (btnPreviewCart) await addToCartFromPreview();
    });
});
