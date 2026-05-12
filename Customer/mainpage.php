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

$new_books = $conn->query("SELECT p.*, c.Decription as CatName, AVG(cmt.Rating) as avg_rating 
    FROM products AS p 
    LEFT JOIN category AS c ON p.Category_ID = c.ID 
    LEFT JOIN Comment AS cmt ON p.ID = cmt.ID_product 
    GROUP BY p.ID 
    ORDER BY p.Update_at DESC 
    LIMIT 8");

// ── 3. QUERY: SẢN PHẨM THEO DANH MỤC (cột phải) ──────────────────────
$products_by_cat = [];
if ($filter_cat_id > 0) {
    // Chỉ lấy 1 danh mục được chọn qua ?cat=ID
    $cat_res = $conn->query("SELECT * FROM category WHERE ID = $filter_cat_id");
    if ($cat_res && $cat = $cat_res->fetch_assoc()) {
        $cid = $cat['ID'];
        $res = $conn->query("SELECT p.*, AVG(cmt.Rating) as avg_rating 
                             FROM products p 
                             LEFT JOIN Comment cmt ON p.ID = cmt.ID_product 
                             WHERE p.Category_ID = $cid 
                             GROUP BY p.ID 
                             ORDER BY p.Update_at DESC");
        $products_by_cat[$cid] = ['name' => $cat['Decription'], 'products' => []];
        if ($res) while ($p = $res->fetch_assoc()) $products_by_cat[$cid]['products'][] = $p;
    }
} else {
    // Không có filter → lấy toàn bộ danh mục và sản phẩm từng danh mục
    $cat_list = $conn->query("SELECT * FROM category ORDER BY ID");
    while ($cat = $cat_list->fetch_assoc()) {
        $cid = $cat['ID'];
        $res = $conn->query("SELECT p.*, AVG(cmt.Rating) as avg_rating 
                             FROM products p 
                             LEFT JOIN Comment cmt ON p.ID = cmt.ID_product 
                             WHERE p.Category_ID = $cid 
                             GROUP BY p.ID 
                             ORDER BY p.Update_at DESC");
        $products_by_cat[$cid] = ['name' => $cat['Decription'], 'products' => []];
        if ($res) while ($p = $res->fetch_assoc()) $products_by_cat[$cid]['products'][] = $p;
    }
}
?>

<?php /* ── LOAD STYLESHEET RIÊNG CHO MAINPAGE ── */ ?>
<link rel="stylesheet" href="CSS/mainpage.css">

<?php
/* ── QUERY VOUCHER BANNER ── */
$banner_vouchers = $conn->query("SELECT * FROM vouchers WHERE Is_banner = 1 AND Status = 'active' AND Start_date <= CURDATE() AND End_date >= CURDATE() AND (Quantity < 0 OR Used < Quantity) ORDER BY Created_at DESC LIMIT 4");
$has_banners = $banner_vouchers && $banner_vouchers->num_rows > 0;
?>

<?php if ($has_banners): ?>
<div class="voucher-banner-section">
    <div class="voucher-banner-header">
        <i class="fa-solid fa-gift"></i>
        <span>NHẬN VOUCHER NGAY</span>
    </div>
    <div class="voucher-banner-grid">
        <?php while ($bv = $banner_vouchers->fetch_assoc()):
            $is_percent = $bv['Type'] === 'percent';
            $value_label = $is_percent ? $bv['Value'].'%' : number_format($bv['Value'],0,',','.').'₫';
            $remaining = $bv['Quantity'] < 0 ? null : ($bv['Quantity'] - $bv['Used']);
            $end_fmt = date('d/m/Y', strtotime($bv['End_date']));
        ?>
        <div class="voucher-card <?= $is_percent ? 'vc-gradient-warm' : 'vc-gradient-cool' ?>">
            <div class="voucher-card-left">
                <div class="voucher-card-value"><?= $value_label ?></div>
                <div class="voucher-card-type"><?= $is_percent ? 'GIẢM' : 'GIẢM NGAY' ?></div>
            </div>
            <div class="voucher-card-right">
                <div class="voucher-card-title"><?= htmlspecialchars($bv['Banner_title'] ?? 'Ưu đãi đặc biệt') ?></div>
                <div class="voucher-card-desc"><?= htmlspecialchars($bv['Banner_subtitle'] ?? '') ?></div>
                <?php if ($bv['Min_order'] > 0): ?>
                    <div class="voucher-card-min">Đơn tối thiểu <?= number_format($bv['Min_order'],0,',','.') ?>₫</div>
                <?php endif; ?>
                <div class="voucher-card-footer">
                    <span class="voucher-card-exp"><i class="fa-regular fa-clock"></i> HSD: <?= $end_fmt ?></span>
                    <?php if ($remaining !== null): ?>
                        <span class="voucher-card-remain">Còn <?= $remaining ?> lượt</span>
                    <?php endif; ?>
                </div>
                <div class="voucher-card-code-row">
                    <span class="voucher-card-code" id="vc_<?= $bv['ID'] ?>"><?= htmlspecialchars($bv['Code']) ?></span>
                    <button class="voucher-copy-btn" onclick="copyVoucher('vc_<?= $bv['ID'] ?>', this)"><i class="fa-regular fa-copy"></i> Sao chép</button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<script>
function copyVoucher(id, btn) {
    const code = document.getElementById(id).textContent;
    navigator.clipboard.writeText(code).then(() => {
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép!';
        btn.style.background = '#2e7d32';
        setTimeout(() => { btn.innerHTML = '<i class="fa-regular fa-copy"></i> Sao chép'; btn.style.background = ''; }, 2000);
    });
}
</script>
<?php endif; ?>

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
                        <li class="new-book-item"
                            data-id="<?= $book['ID'] ?>"
                            data-name="<?= htmlspecialchars($book['Name'], ENT_QUOTES) ?>"
                            data-cat="<?= htmlspecialchars($book['CatName'] ?? '', ENT_QUOTES) ?>"
                            data-author="<?= htmlspecialchars($book['TacGia'] ?? '', ENT_QUOTES) ?>"
                            data-publisher="<?= htmlspecialchars($book['NhaXuatBan'] ?? '', ENT_QUOTES) ?>"
                            data-year="<?= htmlspecialchars($book['NamXuatBan'] ?? '', ENT_QUOTES) ?>"
                            data-pages="<?= intval($book['SoTrang']) ?>"
                            data-price="<?= number_format($book['Price'], 0, ',', '.') ?>"
                            data-qty="<?= intval($book['Quantity']) ?>"
                            data-desc="<?= htmlspecialchars($book['MoTa'] ?? '', ENT_QUOTES) ?>"
                            data-img="<?= htmlspecialchars($book['Image_URL'] ?? '', ENT_QUOTES) ?>"
                            onclick="showPreview(this)"
                            style="cursor: pointer;">
                            <?php if (!empty($book['Image_URL']) && file_exists($book['Image_URL'])): ?>
                                <img src="<?= htmlspecialchars($book['Image_URL']) ?>" alt="<?= htmlspecialchars($book['Name']) ?>">
                            <?php else: ?>
                                <div class="book-img-placeholder"><i class="fa-solid fa-book"></i></div>
                            <?php endif; ?>
                            <div class="book-info">
                                <h4><?= htmlspecialchars($book['Name']) ?></h4>
                                <?php if (isset($book['avg_rating']) && $book['avg_rating'] > 0): ?>
                                    <div style="font-size: 11px; color: #ffc107; margin-bottom: 2px;">
                                        <i class="fa-solid fa-star"></i> <?= number_format($book['avg_rating'], 1) ?>
                                    </div>
                                <?php endif; ?>
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
                    <?php
                    /*
                     * Nút "Xem tất cả":
                     * - Ẩn khi danh mục có ≤ 6 sản phẩm (không cần mở rộng).
                     * - Hiện + đếm số còn ẩn khi có > 6 sản phẩm.
                     * - onclick gọi toggleCat(this) trong mainpage.js.
                     */
                    $total = count($cat['products']);
                    $hidden = max(0, $total - 5);
                    ?>
                    <?php if ($hidden > 0): ?>
                        <button class="cat-view-all"
                                onclick="toggleCat(this)">
                                Xem tất cả
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    <?php else: ?>
                        <?php /* Không hiện nút nếu ≤ 6 sản phẩm */ ?>
                    <?php endif; ?>
                </div>

                <?php if (empty($cat['products'])): ?>
                    <div class="cat-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <p>Chưa có sản phẩm trong danh mục này.</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php
                        /*
                         * Vòng lặp in từng card sản phẩm.
                         * $idx bắt đầu từ 0.
                         * Card với $idx >= 6 (tức từ cuốn thứ 7 trở đi) sẽ được gắn
                         * class "card-hidden" — CSS sẽ ẩn chúng ban đầu.
                         */
                        $idx = 0;
                        foreach ($cat['products'] as $p):
                            $extraClass = $idx >= 5 ? 'card-hidden' : '';
                        ?>
                            <div class="product-card <?= $extraClass ?>"
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
                                        <button class="btn-preview" onclick="showPreview(this.closest('.product-card'))"><i class="fa-solid fa-eye"></i> Xem chi tiết</button>
                                    </div>
                                </div>
                                <div class="product-card-body">
                                    <h4 class="product-name" title="<?= htmlspecialchars($p['Name']) ?>">
                                        <?= htmlspecialchars($p['Name']) ?>
                                    </h4>
                                    <?php if (!empty($p['TacGia'])): ?>
                                        <p class="product-author"><i class="fa-solid fa-pen-nib"></i> <?= htmlspecialchars($p['TacGia']) ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($p['avg_rating']) && $p['avg_rating'] > 0): ?>
                                        <div style="font-size: 13px; color: #ffc107; margin-bottom: 5px;">
                                            <i class="fa-solid fa-star"></i> <?= number_format($p['avg_rating'], 1) ?>/5
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-card-footer">
                                        <span class="product-price"><?= number_format($p['Price'], 0, ',', '.') ?> ₫</span>
                                        <span class="product-qty <?= $p['Quantity'] <= 0 ? 'out-of-stock' : '' ?>">
                                            <?= $p['Quantity'] > 0 ? 'Còn ' . $p['Quantity'] : 'Hết hàng' ?>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- /.product-card -->
                        <?php $idx++; endforeach; ?>
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
                <div class="preview-tabs-container">
                    <div class="preview-tabs">
                        <button class="preview-tab active" onclick="switchPreviewTab('details', this)">Chi tiết</button>
                        <button class="preview-tab" onclick="switchPreviewTab('comments', this)">Bình luận</button>
                    </div>

                    <!-- TAB CHI TIẾT -->
                    <div id="previewTabDetails" class="preview-tab-content active">
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

                        <div class="preview-desc-wrap" id="rowDesc">
                            <p class="desc-section-label"><i class="fa-solid fa-align-left"></i> Mô tả</p>
                            <p class="preview-desc" id="previewDesc"></p>
                            <button class="desc-toggle" id="descToggle" style="display:none;"
                                onclick="toggleDesc(this)">Xem thêm <i class="fa-solid fa-chevron-down"></i></button>
                        </div><!-- /.preview-desc-wrap -->
                    </div>

                    <!-- TAB BÌNH LUẬN -->
                    <div id="previewTabComments" class="preview-tab-content">
                        <div id="previewCommentsList" class="comments-list">
                            <div style="text-align:center; padding:10px; color:#999;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải bình luận...</div>
                        </div>
                        
                        <div class="comment-form-wrap" id="commentFormWrap" style="display: none;">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form id="commentForm" onsubmit="submitComment(event)">
                                    <input type="hidden" id="commentProductId" value="">
                                    <input type="hidden" id="commentRating" value="5">
                                    <div class="rating-stars" id="ratingStars">
                                        Đánh giá: 
                                        <i class="fa-solid fa-star active" data-val="1"></i>
                                        <i class="fa-solid fa-star active" data-val="2"></i>
                                        <i class="fa-solid fa-star active" data-val="3"></i>
                                        <i class="fa-solid fa-star active" data-val="4"></i>
                                        <i class="fa-solid fa-star active" data-val="5"></i>
                                    </div>
                                    <textarea id="commentContent" rows="3" placeholder="Nhập đánh giá và bình luận của bạn..." required></textarea>
                                    <button type="submit" class="btn-submit-comment" id="btnSubmitComment">Gửi đánh giá</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div id="commentNoticeWrap" style="text-align:center; padding: 10px; background:#f8f9fa; border-radius:4px; font-size:14px;">
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                Vui lòng <a href="login.php" style="color:#007bff; font-weight:bold;">Đăng nhập</a> để bình luận.
                            <?php else: ?>
                                <i class="fa-solid fa-spinner fa-spin"></i> Đang kiểm tra điều kiện đánh giá...
                            <?php endif; ?>
                        </div>
                    </div>
                </div><!-- /.preview-tabs-container -->

                <?php /* Nút hành động: Thêm giỏ hàng + Mua ngay */ ?>
                <div class="preview-actions">
                    <button class="preview-btn-cart"><i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng</button>
                </div>
            </div><!-- /.preview-info-col -->
        </div><!-- /.preview-body -->
    </div><!-- /.preview-modal -->
</div><!-- /.preview-modal-backdrop -->

<script>
/**
 * ================================================================
 *  JavaScript — mainpage.php
 *  Bao gồm 4 phần:
 *    A. toggleCat()     : Mở/đóng cards ẩn trong một danh mục
 *    B. showPreview()   : Điền dữ liệu vào modal và mở modal
 *    C. closePreview()  : Đóng modal (nút ×, click ngoài, ESC)
 *    D. Tilt Effect     : Hiệu ứng 3D tilt kiểu thẻ Pokémon khi hover ảnh
 * ================================================================
 */

// ── A. TOGGLE XEM TẤT CẢ / THU GỌN DANH MỤC ────────────────────────────
/**
 * toggleCat(btn)
 * Gọi khi người dùng bấm nút "Xem tất cả" hoặc "Thu gọn" trên header danh mục.
 *
 * Logic:
 *   1. Tìm .product-grid gần nhất trong cùng .category-section.
 *   2. Lấy tất cả card có class "card-hidden".
 *   3. Nếu đang đóng (chưa expanded):
 *        - Bỏ class "card-hidden", thêm "card-showing" → CSS animation fade-in-up.
 *        - Sau 350ms (animation xong) xoá "card-showing" cho sạch.
 *        - Đổi nút thành "Thu gọn ▲".
 *   4. Nếu đang mở (đã expanded):
 *        - Thêm lại "card-hidden" → ẩn ngay.
 *        - Đổi nút về "Xem tất cả (N cuốn nữa) ▼".
 *
 * @param {HTMLElement} btn - Nút .cat-view-all được bấm
 */
function toggleCat(btn) {
    // Tìm container .category-section chứa nút này
    const section = btn.closest('.category-section');
    if (!section) return;

    // Lấy tất cả card đang ẩn (class-hidden) trong grid của section này
    const hiddenCards = section.querySelectorAll('.product-card.card-hidden');
    const isExpanded  = btn.classList.contains('expanded');

    if (!isExpanded) {
        // ── MỞ RỘNG: Hiện các card còn ẩn ──────────────────────────
        hiddenCards.forEach((card, i) => {
            // Thêm class card-showing trước để trigger animation
            card.classList.add('card-showing');
            // Delay nhỏ tăng dần → các card hiện ra lần lượt (stagger effect)
            card.style.animationDelay = (i * 0.05) + 's';
            // Xoá class card-hidden để card tham gia layout grid
            card.classList.remove('card-hidden');
            // Sau khi animation kết thúc, xoá card-showing (dọn dẹp)
            setTimeout(() => card.classList.remove('card-showing'), 400 + i * 50);
        });

        // Cập nhật trạng thái nút
        btn.classList.add('expanded');
        btn.innerHTML = 'Thu gọn <i class="fa-solid fa-chevron-up"></i>';
    } else {
        // ── THU GỌN: Ẩn lại các card từ vị trí thứ 7 ──────────────
        const allCards = section.querySelectorAll('.product-card');
        let hiddenCount = 0;

        allCards.forEach((card, i) => {
            if (i >= 5) {
                // Ẩn ngay, không cần animation
                card.classList.add('card-hidden');
                card.classList.remove('card-showing');
                card.style.animationDelay = '';
                hiddenCount++;
            }
        });

        // Cập nhật trạng thái nút
        btn.classList.remove('expanded');
        btn.innerHTML = `Xem tất cả <i class="fa-solid fa-chevron-down"></i>`;
    }
}


// ── B. CẤU HÌNH MÔ TẢ ──────────────────────────────────────────────────
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

    // Lưu lại card gốc để addToCartFromPreview() dùng
    window._previewCard = card;

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

    // Mở modal, reset về tab Chi tiết và tải bình luận
    modal.classList.add('open');
    document.body.style.overflow = 'hidden'; // chặn scroll trang phía sau
    switchPreviewTab('details', document.querySelector('.preview-tab')); // Mặc định mở tab Chi tiết
    loadComments(d.id); // Gọi AJAX tải bình luận
}

/**
 * Chuyển đổi giữa tab Chi tiết và tab Bình luận
 */
function switchPreviewTab(tabId, btnElement) {
    // Reset active buttons
    document.querySelectorAll('.preview-tab').forEach(btn => btn.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
    else document.querySelector('.preview-tabs').firstElementChild.classList.add('active'); // fallback

    // Hide all contents
    document.querySelectorAll('.preview-tab-content').forEach(content => content.classList.remove('active'));
    
    // Show target content
    if (tabId === 'details') {
        document.getElementById('previewTabDetails').classList.add('active');
    } else if (tabId === 'comments') {
        document.getElementById('previewTabComments').classList.add('active');
    }
}

/**
 * Tải bình luận từ API
 */
async function loadComments(productId) {
    const listEl = document.getElementById('previewCommentsList');
    listEl.innerHTML = '<div style="text-align:center; padding:10px; color:#999;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải bình luận...</div>';
    
    // Lưu productId vào form ẩn để submit
    const formProductId = document.getElementById('commentProductId');
    if (formProductId) formProductId.value = productId;

    try {
        const res = await fetch(`Customer/comment_api.php?product_id=${productId}`);
        const resultData = await res.json();
        
        const data = resultData.comments || [];
        const canComment = resultData.can_comment || false;
        
        // Hiện form comment nếu có quyền
        const formWrap = document.getElementById('commentFormWrap');
        const noticeWrap = document.getElementById('commentNoticeWrap');
        if (formWrap && noticeWrap) {
            if (canComment) {
                formWrap.style.display = 'block';
                noticeWrap.style.display = 'none';
            } else {
                formWrap.style.display = 'none';
                noticeWrap.style.display = 'block';
                // Nếu notice chưa hiện "Vui lòng đăng nhập", ta thay bằng câu báo cần mua hàng
                if (!noticeWrap.innerHTML.includes('Đăng nhập')) {
                    noticeWrap.innerHTML = 'Bạn cần mua sản phẩm này và nhận hàng thành công để có thể đánh giá.';
                }
            }
        }
        
        if (data && data.length > 0) {
            let html = '';
            data.forEach(cmt => {
                const date = new Date(cmt.Created_at).toLocaleString('vi-VN');
                const rating = parseInt(cmt.Rating) || 5;
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    starsHtml += `<i class="fa-solid fa-star" style="color: ${i <= rating ? '#ffc107' : '#e4e5e9'}; font-size: 12px;"></i>`;
                }
                
                html += `
                    <div class="comment-item">
                        <div class="comment-avatar"><i class="fa-solid fa-user"></i></div>
                        <div class="comment-body">
                            <div class="comment-author">${cmt.UserName} <span class="comment-time">${date}</span> <span style="margin-left: 8px;">${starsHtml}</span></div>
                            <div class="comment-content">${cmt.Comment_Detail.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</div>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html;
        } else {
            listEl.innerHTML = '<div style="text-align:center; padding:15px; color:#999; font-style:italic;">Chưa có đánh giá nào. Hãy là người đầu tiên!</div>';
        }
    } catch (e) {
        listEl.innerHTML = `<div style="text-align:center; color:red; padding:10px;">Lỗi tải đánh giá: ${e.message}</div>`;
    }
}

/**
 * Xử lý click sao đánh giá
 */
document.addEventListener('click', function(e) {
    if (e.target.closest('#ratingStars .fa-star')) {
        const star = e.target.closest('.fa-star');
        const val = parseInt(star.dataset.val);
        document.getElementById('commentRating').value = val;
        
        const stars = document.querySelectorAll('#ratingStars .fa-star');
        stars.forEach(s => {
            if (parseInt(s.dataset.val) <= val) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    }
});

/**
 * Gửi bình luận qua API
 */
async function submitComment(e) {
    e.preventDefault();
    const productId = document.getElementById('commentProductId').value;
    const contentInput = document.getElementById('commentContent');
    const content = contentInput.value.trim();
    const ratingInput = document.getElementById('commentRating');
    const rating = ratingInput ? ratingInput.value : 5;
    const btn = document.getElementById('btnSubmitComment');

    if (!content) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi...';

    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('content', content);
        formData.append('rating', rating);

        const res = await fetch('Customer/comment_api.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            contentInput.value = ''; // Xóa nội dung
            
            // Reset sao về 5
            if (ratingInput) ratingInput.value = 5;
            document.querySelectorAll('#ratingStars .fa-star').forEach(s => s.classList.add('active'));
            
            loadComments(productId); // Tải lại danh sách
        } else {
            alert("Lỗi: " + (result.message || "Không thể gửi đánh giá"));
        }
    } catch (err) {
        alert("Có lỗi xảy ra khi gửi đánh giá.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Gửi đánh giá';
    }
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
