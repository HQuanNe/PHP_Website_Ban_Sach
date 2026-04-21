/**
 * ================================================================
 *  cart.js — Logic giỏ hàng phía client (v2 — Tích hợp DB)
 *  ----------------------------------------------------------------
 *  Chiến lược 2 lớp:
 *    • Guest  (chưa đăng nhập) → lưu sessionStorage (key: CART_KEY)
 *    • Logged (đã đăng nhập)   → lưu DB qua cart_api.php + giữ
 *                                 sessionStorage làm cache
 *
 *  Khi login thành công → gọi syncGuestCartToDB() để đẩy giỏ
 *  guest lên DB, sau đó tải lại giỏ từ DB.
 *
 *  Cấu trúc mỗi item:
 *  { id: string, name: string, price: number, img: string, qty: number }
 *
 *  Hàm public (dùng được từ HTML inline onclick):
 *    addToCart(card, btn)       — Thêm từ product-card
 *    addToCartFromPreview()     — Thêm từ modal Preview
 *    openCart()                 — Mở drawer
 *    closeCart()                — Đóng drawer
 *    changeQty(id, delta)       — Tăng/giảm số lượng
 *    removeItem(id)             — Xóa 1 sản phẩm
 *    clearCart()                — Xóa toàn bộ
 *    syncGuestCartToDB(items)   — Đồng bộ guest→DB sau login
 * ================================================================
 */

'use strict';

/* ══ CẤU HÌNH ════════════════════════════════════════════════════ */
const CART_KEY  = 'dreambook_cart';
// Tự detect đường dẫn: nếu đang ở /Customer/ → cùng thư mục; nếu ở root → Customer/
const _inCustomer = window.location.pathname.includes('/Customer/');
const API_URL = _inCustomer ? 'cart_api.php' : 'Customer/cart_api.php';

/* ══ PHÁT HIỆN TRẠNG THÁI ĐĂNG NHẬP ════════════════════════════ */
/**
 * isLoggedIn()
 * Kiểm tra bằng cách đọc meta tag do PHP render vào <head>:
 *   <meta name="user-logged" content="1">
 * Nếu không có meta → coi là guest.
 */
function isLoggedIn() {
    const meta = document.querySelector('meta[name="user-logged"]');
    return meta && meta.content === '1';
}

/* ══ ĐỌC / GHI sessionStorage ══════════════════════════════════ */
function cartLoad() {
    try { return JSON.parse(sessionStorage.getItem(CART_KEY)) || []; }
    catch { return []; }
}
function cartSave(items) {
    sessionStorage.setItem(CART_KEY, JSON.stringify(items));
}

/* ══ GỌI API (chỉ khi đã login) ════════════════════════════════ */
/**
 * apiCall(body)
 * Gọi cart_api.php bằng fetch POST JSON.
 * @param {Object} body — payload (phải có trường action)
 * @returns {Promise<Object>} kết quả JSON
 */
async function apiCall(body) {
    try {
        const res = await fetch(API_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify(body),
        });
        return await res.json();
    } catch (e) {
        console.error('[Cart API]', e);
        return { error: e.message };
    }
}

/* ══ THÊM VÀO GIỎ ══════════════════════════════════════════════ */

/**
 * addToCart(card, triggerEl)
 * Thêm sản phẩm từ .product-card.
 *   - Guest  → cập nhật sessionStorage
 *   - Login  → gọi API add, cập nhật sessionStorage cache
 */
async function addToCart(card, triggerEl) {
    const d = card.dataset;
    const price = parseInt((d.price || '0').replace(/\./g, ''), 10) || 0;

    /* Cập nhật sessionStorage trước (UI phản hồi ngay) */
    const items    = cartLoad();
    const existing = items.find(i => i.id === d.id);
    if (existing) { existing.qty++; }
    else { items.push({ id: d.id, name: d.name, price, img: d.img || '', qty: 1 }); }
    cartSave(items);
    cartUpdateBadge();
    cartRenderItems();
    flyToCart(triggerEl || card);

    /* Nếu đã login → đồng bộ lên DB */
    if (isLoggedIn()) {
        await apiCall({ action: 'add', prod_id: d.id, qty: 1 });
    }
}

/**
 * addToCartFromPreview()
 * Thêm từ modal Preview — dùng window._previewCard lưu lúc showPreview().
 */
async function addToCartFromPreview() {
    if (!window._previewCard) return;
    const btn = document.querySelector('.preview-btn-cart');
    await addToCart(window._previewCard, btn);
}

/* ══ ĐỒNG BỘ GUEST → DB SAU KHI ĐĂNG NHẬP ════════════════════ */
/**
 * syncGuestCartToDB(guestItems)
 * Gọi sau khi login thành công (từ header.php AJAX login).
 * Đẩy toàn bộ giỏ guest lên DB rồi tải lại giỏ từ DB.
 * @param {Array} guestItems — mảng items từ sessionStorage
 */
async function syncGuestCartToDB(guestItems) {
    if (!guestItems || guestItems.length === 0) {
        await loadCartFromDB(); // vẫn load giỏ DB (user có thể đã có hàng từ trước)
        return;
    }
    /* Gửi toàn bộ giỏ guest lên API để merge */
    await apiCall({ action: 'sync', items: guestItems });
    /* Tải lại từ DB → cập nhật sessionStorage và UI */
    await loadCartFromDB();
}

/**
 * loadCartFromDB()
 * Lấy giỏ hàng từ DB về, lưu vào sessionStorage, render UI.
 */
async function loadCartFromDB() {
    const data = await apiCall({ action: 'get' });
    if (data.items) {
        cartSave(data.items);
        cartUpdateBadge();
        cartRenderItems();
    }
}

/* ══ HIỆU ỨNG BAY ĐẾN GIỎ ══════════════════════════════════════ */
/**
 * flyToCart(fromEl)
 * Tạo bong bóng icon tại fromEl, animate bay đến .cart-btn.
 */
function flyToCart(fromEl) {
    const cartIcon = document.querySelector('.cart-btn');
    if (!cartIcon || !fromEl) return;

    const from = fromEl.getBoundingClientRect();
    const to   = cartIcon.getBoundingClientRect();

    const bubble = document.createElement('div');
    bubble.className = 'fly-bubble';
    bubble.innerHTML = '<i class="fa-solid fa-cart-plus"></i>';
    bubble.style.cssText = `
        top:  ${from.top  + from.height / 2 - 18}px;
        left: ${from.left + from.width  / 2 - 18}px;
        opacity: 1;
    `;
    document.body.appendChild(bubble);

    bubble.getBoundingClientRect(); // force reflow

    bubble.style.top     = `${to.top  + to.height / 2 - 8}px`;
    bubble.style.left    = `${to.left + to.width  / 2 - 8}px`;
    bubble.style.width   = '16px';
    bubble.style.height  = '16px';
    bubble.style.opacity = '0';
    bubble.style.fontSize = '8px';

    setTimeout(() => bubble.remove(), 700);
}

/* ══ BADGE SỐ LƯỢNG TRÊN HEADER ════════════════════════════════ */
function cartUpdateBadge() {
    const badge = document.querySelector('.cart-badge');
    if (!badge) return;
    const total = cartLoad().reduce((s, i) => s + i.qty, 0);
    badge.textContent = total > 99 ? '99+' : total;
    if (total > 0) {
        badge.classList.add('visible');
        badge.classList.remove('pop');
        void badge.offsetWidth; // reflow
        badge.classList.add('pop');
    } else {
        badge.classList.remove('visible');
    }
}

/* ══ RENDER ITEMS TRONG DRAWER ══════════════════════════════════ */
/**
 * cartRenderItems()
 * Vẽ lại danh sách sản phẩm trong drawer + cập nhật tổng tiền.
 */
function cartRenderItems() {
    const wrap  = document.getElementById('cartItemsWrap');
    const empty = document.getElementById('cartEmpty');
    const total = document.getElementById('cartTotal');
    if (!wrap) return;

    const items = cartLoad();
    /* Xóa hết (trừ phần tử #cartEmpty) */
    Array.from(wrap.children).forEach(el => {
        if (!el.id || el.id !== 'cartEmpty') el.remove();
    });

    if (items.length === 0) {
        if (empty) empty.style.display = 'flex';
        if (total) total.textContent   = '0 ₫';
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
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══ TĂNG / GIẢM SỐ LƯỢNG ══════════════════════════════════════ */
/**
 * changeQty(id, delta)
 * Thay đổi qty ±1. qty→0 sẽ xóa item.
 * Nếu login → gọi API update/remove.
 */
async function changeQty(id, delta) {
    const items = cartLoad();
    const item  = items.find(i => i.id === id);
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

/* ══ XÓA 1 SẢN PHẨM ════════════════════════════════════════════ */
async function removeItem(id) {
    const items = cartLoad().filter(i => i.id !== id);
    cartSave(items);
    cartUpdateBadge();
    cartRenderItems();
    if (isLoggedIn()) await apiCall({ action: 'remove', prod_id: id });
}

/* ══ XÓA TOÀN BỘ ════════════════════════════════════════════════ */
async function clearCart() {
    cartSave([]);
    cartUpdateBadge();
    cartRenderItems();
    if (isLoggedIn()) await apiCall({ action: 'clear' });
}

/* ══ MỞ / ĐÓNG DRAWER ══════════════════════════════════════════ */
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

/* ══ NÚT THANH TOÁN TRONG DRAWER ════════════════════════════════ */
/**
 * Khi bấm "Thanh toán" trong drawer:
 *   - Nếu giỏ trống → hiện thông báo
 *   - Nếu có hàng → chuyển tới trang checkout
 */
function goCheckout() {
    const items = cartLoad();
    if (items.length === 0) {
        alert('Giỏ hàng của bạn đang trống!');
        return;
    }
    closeCart();
    window.location.href = 'Customer/checkout.php';
}

/* ══ KHỞI TẠO ══════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {

    /* Nếu đã login → tải giỏ từ DB (ưu tiên hơn sessionStorage) */
    if (isLoggedIn()) {
        await loadCartFromDB();
    } else {
        cartUpdateBadge(); // hiển thị giỏ guest từ sessionStorage
    }

    /* Event delegation — bắt nút "Thêm giỏ" dù card render sau */
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
