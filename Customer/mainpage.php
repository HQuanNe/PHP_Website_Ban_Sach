<?php
/**
 * mainpage.php — Trang chủ hiển thị sản phẩm
 * --------------------------------------------------
 * Gồm 2 phần chính:
 *   - Cột trái : Danh sách sách mới nhất (top 8)
 *   - Cột phải: Sản phẩm chia theo danh mục
 *
 * URL filter: ?cat=<category_id>  → chỉ hiển 1 danh mục
 *             (không có tham số)  → hiển tất cả danh mục
 * --------------------------------------------------
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'Connect/connect.php';

// ── 1. ĐỌC FILTER DANH MỤC TỪ URL ─────────────────────────────────────
// ?cat=ID → chỉ lấy danh mục đó; không có → hiển thị tất cả
$filter_cat_id = isset($_GET['cat']) && is_numeric($_GET['cat']) ? intval($_GET['cat']) : 0;

// ── 2. QUERY: SÁCH MỚI NHẤT (cột trái) ────────────────────────────────
// Lấy 8 sản phẩm cập nhật gần nhất, join category để lấy tên danh mục
$new_books = $conn->query("SELECT p.*, c.Decription as CatName 
    FROM products p 
    LEFT JOIN category c ON p.Category_ID = c.ID 
    ORDER BY p.Update_at DESC 
    LIMIT 8");

// ── 3. QUERY: SẢN PHẨM THEO DANH MỤC (cột phải) ──────────────────────
$products_by_cat = [];
if ($filter_cat_id > 0) {
    // Chỉ lấy 1 danh mục được chọn qua ?cat=ID
    $cat_res = $conn->query("SELECT * FROM category WHERE ID = $filter_cat_id");
    if ($cat_res && $cat = $cat_res->fetch_assoc()) {
        $cid = $cat['ID'];
        $res = $conn->query("SELECT * FROM products WHERE Category_ID = $cid ORDER BY Update_at DESC");
        $products_by_cat[$cid] = ['name' => $cat['Decription'], 'products' => []];
        if ($res) while ($p = $res->fetch_assoc()) $products_by_cat[$cid]['products'][] = $p;
    }
} else {
    // Không có filter → lấy toàn bộ danh mục và sản phẩm từng danh mục
    $cat_list = $conn->query("SELECT * FROM category ORDER BY ID");
    while ($cat = $cat_list->fetch_assoc()) {
        $cid = $cat['ID'];
        $res = $conn->query("SELECT * FROM products WHERE Category_ID = $cid ORDER BY Update_at DESC");
        $products_by_cat[$cid] = ['name' => $cat['Decription'], 'products' => []];
        if ($res) while ($p = $res->fetch_assoc()) $products_by_cat[$cid]['products'][] = $p;
    }
}
?>

<?php /* ── LOAD STYLESHEET RIÊNG CHO MAINPAGE ── */ ?>
<link rel="stylesheet" href="CSS/mainpage.css">

<?php /* ── BỐ CỤC CHÍNH: 2 CỘT TRÁI / PHẢI ── */ ?>
<div class="container-body">

    <?php /* ── CỘT TRÁI: SÁCH MỚI LÊN KỆ ─────────────────────────────────
     * Hiển thị danh sách dạng list với thumb ảnh nhỏ + tên + giá.
     * Nếu không có ảnh thì dùng placeholder icon fa-book.
     * ─────────────────────────────────────────────────────────────── */ ?>
    <div class="container-body-left">
        <div class="new-books-container">
            <h3>Sách mới lên kệ</h3>
            <ul class="new-books-list">
                <?php if ($new_books && $new_books->num_rows > 0): ?>
                    <?php while ($book = $new_books->fetch_assoc()): ?>
                        <li>
                            <?php if (!empty($book['Image_URL']) && file_exists($book['Image_URL'])): ?>
                                <img src="<?= htmlspecialchars($book['Image_URL']) ?>" alt="<?= htmlspecialchars($book['Name']) ?>">
                            <?php else: ?>
                                <div class="book-img-placeholder"><i class="fa-solid fa-book"></i></div>
                            <?php endif; ?>
                            <div class="book-info">
                                <h4><?= htmlspecialchars($book['Name']) ?></h4>
                                <p class="price"><?= number_format($book['Price'], 0, ',', '.') ?> ₫</p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li style="justify-content: center; color: #999; font-style: italic;">Chưa có sản phẩm</li>
                <?php endif; ?>
            </ul>
        </div>
    </div><!-- /.container-body-left -->

    <?php /* ── CỘT PHẢI: SẢN PHẨM THEO DANH MỤC ──────────────────────────
     * Mỗi danh mục hiển thị thành 1 section riêng với header + product-grid.
     * Mỗi product-card lưu toàn bộ thông tin qua data-* attribute để Preview.
     * Overlay xuất hiện khi hover: nút "Thêm giỏ" + nút "Preview".
     * ─────────────────────────────────────────────────────────────── */ ?>
    <div class="container-body-right">
        <?php foreach ($products_by_cat as $cid => $cat): ?>
            <div class="category-section">
                <div class="category-section-header">
                    <h2 class="category-section-title">
                        <span class="cat-badge"></span>
                        <?= htmlspecialchars($cat['name']) ?>
                    </h2>
                    <a href="#" class="cat-view-all">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <?php if (empty($cat['products'])): ?>
                    <div class="cat-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <p>Chưa có sản phẩm trong danh mục này.</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($cat['products'] as $p): ?>
                            <div class="product-card"
                                data-id="<?= $p['ID'] ?>"
                                data-name="<?= htmlspecialchars($p['Name'], ENT_QUOTES) ?>"
                                data-cat="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                data-author="<?= htmlspecialchars($p['TacGia'] ?? '', ENT_QUOTES) ?>"
                                data-publisher="<?= htmlspecialchars($p['NhaXuatBan'] ?? '', ENT_QUOTES) ?>"
                                data-year="<?= htmlspecialchars($p['NamXuatBan'] ?? '', ENT_QUOTES) ?>"
                                data-pages="<?= intval($p['SoTrang']) ?>"
                                data-price="<?= number_format($p['Price'], 0, ',', '.') ?>"
                                data-qty="<?= intval($p['Quantity']) ?>"
                                data-desc="<?= htmlspecialchars($p['MoTa'] ?? '', ENT_QUOTES) ?>"
                                data-img="<?= htmlspecialchars($p['Image_URL'] ?? '', ENT_QUOTES) ?>">
                                <div class="product-card-img">
                                    <?php if (!empty($p['Image_URL']) && file_exists($p['Image_URL'])): ?>
                                        <img src="<?= htmlspecialchars($p['Image_URL']) ?>" alt="<?= htmlspecialchars($p['Name']) ?>">
                                    <?php else: ?>
                                        <div class="no-img-placeholder">
                                            <i class="fa-solid fa-book"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-card-overlay">
                                        <button class="btn-add-cart"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                                        <button class="btn-preview" onclick="showPreview(this.closest('.product-card'))"><i class="fa-solid fa-eye"></i> Preview</button>
                                    </div>
                                </div>
                                <div class="product-card-body">
                                    <h4 class="product-name" title="<?= htmlspecialchars($p['Name']) ?>">
                                        <?= htmlspecialchars($p['Name']) ?>
                                    </h4>
                                    <?php if (!empty($p['TacGia'])): ?>
                                        <p class="product-author"><i class="fa-solid fa-pen-nib"></i> <?= htmlspecialchars($p['TacGia']) ?></p>
                                    <?php endif; ?>
                                    <div class="product-card-footer">
                                        <span class="product-price"><?= number_format($p['Price'], 0, ',', '.') ?> ₫</span>
                                        <span class="product-qty <?= $p['Quantity'] <= 0 ? 'out-of-stock' : '' ?>">
                                            <?= $p['Quantity'] > 0 ? 'Còn ' . $p['Quantity'] : 'Hết hàng' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div><!-- /.container-body-right -->

</div><!-- /.container-body -->


<?php /* ── MODAL PREVIEW SẢN PHẨM ─────────────────────────────────────────
 * Hiển thị chi tiết sản phẩm khi người dùng bấm nút Preview trên card.
 * HTML này luôn tồn tại trong DOM (hidden bằng CSS).
 * JS showPreview() điền data vào rồi thêm class .open để hiện.
 * Ảnh bìa hỗ trợ hiệu ứng 3D tilt (Pokemon card) qua JS bên dưới.
 * Đóng modal: nút ×, click backdrop ngoài, hoặc phím ESC.
 * ─────────────────────────────────────────────────────────────────────── */ ?>
<div id="previewModal" class="preview-modal-backdrop" onclick="closePreview(event)">
    <div class="preview-modal">
        <?php /* Nút đóng (×) góc trên phải — xoay 90° khi hover */ ?>
        <button class="preview-close" onclick="closePreviewBtn()"><i class="fa-solid fa-xmark"></i></button>

        <?php /* Bố cục 2 cột bên trong modal */ ?>
        <div class="preview-body">

            <?php /* ─── CỘT TRÁI: Ảnh bìa + badge tồn kho ─────────────────
             * #previewImgWrap nhận mousemove → JS tính góc 3D tilt.
             * #previewNoImg = icon sách placeholder khi không có ảnh URL.
             * ───────────────────────────────────────────────────────── */ ?>
            <div class="preview-img-col">
                <div class="preview-img-wrap" id="previewImgWrap">
                    <div class="preview-no-img" id="previewNoImg"><i class="fa-solid fa-book"></i></div>
                    <img id="previewImg" src="" alt="" style="display:none;">
                </div>
                <div class="preview-stock" id="previewStock"></div>
            </div>

            <?php /* ─── CỘT PHẢI: Thông tin chi tiết sản phẩm ─────────────
             * Tag danh mục → Tên → Giá → Bảng 4 dòng → Mô tả → Nút
             * ───────────────────────────────────────────────────────── */ ?>
            <div class="preview-info-col">
                <?php /* Tag danh mục (ẩn nếu không có category) */ ?>
                <span class="preview-cat-tag" id="previewCat"></span>
                <h2 class="preview-title" id="previewName"></h2>
                <p class="preview-price-big" id="previewPrice"></p>
                <?php /* Bảng 4 dòng: Tác giả, NXB, Năm XB, Số trang
                 * JS luôn show tất cả dòng; dùng — khi field rỗng */ ?>
                <div class="preview-details">
                    <div class="preview-detail-row" id="rowAuthor">
                        <span class="detail-label"><i class="fa-solid fa-pen-nib"></i> Tác giả</span>
                        <span class="detail-value" id="previewAuthor"></span>
                    </div>
                    <div class="preview-detail-row" id="rowPublisher">
                        <span class="detail-label"><i class="fa-solid fa-building"></i> NXB</span>
                        <span class="detail-value" id="previewPublisher"></span>
                    </div>
                    <div class="preview-detail-row" id="rowYear">
                        <span class="detail-label"><i class="fa-solid fa-calendar"></i> Năm XB</span>
                        <span class="detail-value" id="previewYear"></span>
                    </div>
                    <div class="preview-detail-row" id="rowPages">
                        <span class="detail-label"><i class="fa-solid fa-book-open"></i> Số trang</span>
                        <span class="detail-value" id="previewPages"></span>
                    </div>
                </div><!-- /.preview-details -->

                <?php /* Khối mô tả — ẩn hoàn toàn khi MoTa rỗng
                 * Mô tả > 180 ký tự → cắt + nút Xem thêm/Thu gọn */ ?>
                <div class="preview-desc-wrap" id="rowDesc">
                    <p class="desc-section-label"><i class="fa-solid fa-align-left"></i> Mô tả</p>
                    <p class="preview-desc" id="previewDesc"></p>
                    <button class="desc-toggle" id="descToggle" style="display:none;"
                        onclick="toggleDesc(this)">Xem thêm <i class="fa-solid fa-chevron-down"></i></button>
                </div><!-- /.preview-desc-wrap -->

                <?php /* Nút hành động: Thêm giỏ hàng + Mua ngay */ ?>
                <div class="preview-actions">
                    <button class="preview-btn-cart"><i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng</button>
                    <button class="preview-btn-buy"><i class="fa-solid fa-money-bill-wave"></i> Mua ngay</button>
                </div>
            </div><!-- /.preview-info-col -->
        </div><!-- /.preview-body -->
    </div><!-- /.preview-modal -->
</div><!-- /.preview-modal-backdrop -->

<script>
/**
 * ================================================================
 *  JavaScript — mainpage.php
 *  Bao gồm 3 phần:
 *    A. showPreview()   : Điền dữ liệu vào modal và mở modal
 *    B. closePreview()  : Đóng modal (nút ×, click ngoài, ESC)
 *    C. Tilt Effect     : Hiệu ứng 3D tilt kiểu thẻ Pokémon khi hover ảnh
 * ================================================================
 */

// ── A. CẤU HÌNH MÔ TẢ ──────────────────────────────────────────────────
const DESC_LIMIT = 180;   // Số ký tự hiển thị trước khi cắt (Xem thêm)
let _fullDesc = '';        // Lưu toàn bộ nội dung mô tả hiện tại

/**
 * showPreview(card)
 * Được gọi khi người dùng bấm nút "Preview" trên card sản phẩm.
 * @param {HTMLElement} card - Phần tử .product-card chứa data-* attributes
 */
function showPreview(card) {
    const d = card.dataset;
    const modal = document.getElementById('previewModal');

    // ── Thông tin cơ bản ────────────────────────────────────────────────
    document.getElementById('previewName').textContent  = d.name  || '';
    document.getElementById('previewCat').textContent   = d.cat   || '';
    document.getElementById('previewPrice').textContent = (d.price || '0') + ' ₫';

    // Ẩn tag danh mục nếu sản phẩm không thuộc danh mục nào
    const catEl = document.getElementById('previewCat');
    catEl.style.display = d.cat ? 'inline-block' : 'none';

    // ── Ảnh bìa ─────────────────────────────────────────────────────────
    // Nếu có URL ảnh → hiện ảnh, ẩn icon placeholder; ngược lại thì đổi chỗ
    const img   = document.getElementById('previewImg');
    const noImg = document.getElementById('previewNoImg');
    if (d.img) {
        img.src = d.img; img.style.display = 'block';
        noImg.style.display = 'none';
    } else {
        img.style.display = 'none'; noImg.style.display = 'flex';
    }

    // ── Badge tồn kho ───────────────────────────────────────────────────
    // .out style → màu xám (hết hàng); mặc định → màu kem (còn hàng)
    const stockEl = document.getElementById('previewStock');
    const qty = parseInt(d.qty) || 0;
    stockEl.textContent = qty > 0 ? 'Còn ' + qty + ' cuốn' : 'Hết hàng';
    stockEl.className   = qty > 0 ? 'preview-stock' : 'preview-stock out';

    // ── 4 dòng chi tiết ─────────────────────────────────────────────────
    // Luôn set display:'flex' để đảm bảo dòng hiện dù trước đó bị ẩn.
    // Hiện '—' mờ nếu field rỗng / bằng 0, hiện giá trị thật nếu có.
    function setDetail(rowId, valId, raw, suffix) {
        document.getElementById(rowId).style.display = 'flex';
        const el = document.getElementById(valId);
        const ok = raw && raw.trim() !== '' && raw !== '0' && raw !== '0000';
        el.textContent = ok ? raw.trim() + (suffix || '') : '—';
        el.style.opacity = ok ? '1' : '0.35';
    }
    setDetail('rowAuthor',    'previewAuthor',    d.author,    '');
    setDetail('rowPublisher', 'previewPublisher', d.publisher, '');
    setDetail('rowYear',      'previewYear',      d.year,      '');
    setDetail('rowPages',     'previewPages',     d.pages,     ' trang');

    // ── Mô tả ────────────────────────────────────────────────────────────
    // Ẩn toàn bộ block nếu MoTa rỗng.
    // Nếu có và vượt DESC_LIMIT → hiện bản rút gọn + nút "Xem thêm ▼".
    _fullDesc = (d.desc || '').trim();
    const descWrap  = document.getElementById('rowDesc');
    const descEl    = document.getElementById('previewDesc');
    const toggleBtn = document.getElementById('descToggle');

    if (_fullDesc) {
        descWrap.style.display = 'block';
        if (_fullDesc.length > DESC_LIMIT) {
            // Cắt tại DESC_LIMIT ký tự + dấu …
            descEl.textContent      = _fullDesc.slice(0, DESC_LIMIT).trimEnd() + '…';
            toggleBtn.style.display = 'inline-flex';
            toggleBtn.dataset.expanded = 'no';
            toggleBtn.innerHTML     = 'Xem thêm <i class="fa-solid fa-chevron-down"></i>';
        } else {
            // Mô tả ngắn → hiện đủ, ẩn nút toggle
            descEl.textContent      = _fullDesc;
            toggleBtn.style.display = 'none';
        }
    } else {
        descWrap.style.display = 'none';
    }

    // Mở modal
    modal.classList.add('open');
    document.body.style.overflow = 'hidden'; // chặn scroll trang phía sau
}

/**
 * toggleDesc(btn)
 * Xem thêm / Thu gọn phần mô tả dài.
 * @param {HTMLElement} btn - Nút .desc-toggle được bấm
 */
function toggleDesc(btn) {
    const descEl = document.getElementById('previewDesc');
    if (btn.dataset.expanded === 'no') {
        // Mở rộng: hiện toàn bộ mô tả
        descEl.textContent   = _fullDesc;
        btn.dataset.expanded = 'yes';
        btn.innerHTML        = 'Thu gọn <i class="fa-solid fa-chevron-up"></i>';
    } else {
        // Thu gọn: cắt về DESC_LIMIT ký tự
        descEl.textContent   = _fullDesc.slice(0, DESC_LIMIT).trimEnd() + '…';
        btn.dataset.expanded = 'no';
        btn.innerHTML        = 'Xem thêm <i class="fa-solid fa-chevron-down"></i>';
    }
}

// ── B. ĐÓNG MODAL ────────────────────────────────────────────────────────
/** Xóa class .open để ẩn modal, trả lại scroll cho trang */
function closePreviewBtn() {
    document.getElementById('previewModal').classList.remove('open');
    document.body.style.overflow = '';
}
/** Chỉ đóng khi click đúng vào backdrop (nền tối), không phải nội dung modal */
function closePreview(e) {
    if (e.target === document.getElementById('previewModal')) closePreviewBtn();
}
/** Bấm ESC cũng đóng được modal */
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreviewBtn(); });


// ── C. HIỆU ỨNG 3D TILT (Pokémon Card) ──────────────────────────────────
/**
 * Khi hover vào #previewImgWrap:
 *   - Di chuột → tính góc lệch từ tâm ảnh → áp dụng rotateX/rotateY
 *   - Bóng đổ cũng dịch chuyển theo hướng nghiêng
 *   - Rời chuột → animate trở về vị trí ban đầu (easing mềm)
 */
(function() {
    const MAX_ANGLE = 22;   // độ nghiêng tối đa (mỗi trục)
    const SCALE     = 1.05; // tỷ lệ phóng to nhẹ khi hover

    /**
     * Tính góc xoay dựa trên vị trí chuột trong wrap, rồi áp dụng transform.
     * nx, ny ∈ [-1, 1]: chuẩn hóa từ góc trái/trên (-1) đến phải/dưới (+1).
     */
    function applyTilt(wrap, e) {
        const rect = wrap.getBoundingClientRect();
        const nx = ((e.clientX - rect.left)  / rect.width  - 0.5) * 2;
        const ny = ((e.clientY - rect.top)   / rect.height - 0.5) * 2;
        const rotY =  nx * MAX_ANGLE;  // lệch phải → xoay Y dương
        const rotX = -ny * MAX_ANGLE;  // lệch xuống → xoay X âm
        // Bóng đổ dịch ngược chiều nghiêng để tạo hiệu ứng ánh sáng
        const shadowX = -nx * 14;
        const shadowY = -ny * 14;
        wrap.style.transform = `scale(${SCALE}) rotateX(${rotX}deg) rotateY(${rotY}deg)`;
        wrap.style.boxShadow = `${shadowX}px ${shadowY}px 30px rgba(0,0,0,0.3)`;
    }

    /** Reset về trạng thái ban đầu với animation trơn tru */
    function resetTilt(wrap) {
        // Transition chậm lúc reset để có cảm giác "đặt thẻ xuống"
        wrap.style.transition = 'transform 0.45s cubic-bezier(0.23,1,0.32,1), box-shadow 0.45s ease';
        wrap.style.transform  = 'scale(1) rotateX(0deg) rotateY(0deg)';
        wrap.style.boxShadow  = '0 8px 24px rgba(0,0,0,0.15)';
        // Sau khi reset xong → trả về transition nhanh cho lần hover tiếp
        setTimeout(() => { wrap.style.transition = 'transform 0.08s linear, box-shadow 0.08s linear'; }, 450);
    }

    // Gắn event sau khi DOM sẵn sàng
    document.addEventListener('DOMContentLoaded', () => {
        const wrap = document.getElementById('previewImgWrap');
        if (!wrap) return;
        wrap.addEventListener('mousemove',  e  => { wrap.style.transition = 'transform 0.08s linear, box-shadow 0.08s linear'; applyTilt(wrap, e); });
        wrap.addEventListener('mouseleave', () => resetTilt(wrap));
        wrap.addEventListener('mouseenter', e  => applyTilt(wrap, e));
    });
})();
</script>
