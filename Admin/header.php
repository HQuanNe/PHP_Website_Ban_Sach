<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream book</title>

    <link rel="icon" href="Resource/Image/Logo/LogoNoText.webp">
    <link rel="stylesheet" href="CSS/index.css">
    <link rel="stylesheet" href="CSS/default.css">
    <link rel="stylesheet" href="Resource/FontAwesome/fontawesome-free-7.2.0-web/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="container-header">
            <div class="container-header-top">
                <img src="Resource/Image/Slide_image/20240321_J5bpFnuw.png" alt="Header banner">
            </div>
            <div class="container-header-mid">
                <div class="header-mid-logo"><a href="#"><img src="Resource/Image/Logo/logo.jpg" alt=""></a></div>
                <div class="header-mid-search_bar">
                    <input type="text" name="header-mid-search" id="" placeholder="Tìm kiếm...">
                    <button><i style="color: #fff;" class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                <div class="header-mid-cart">
                    <i style="color: #7a6f63;" class="fa-solid fa-cart-shopping"></i>
                    <span class="header-mid-cart_quantity">5</span>
                </div>
                
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="header-logged-in" style="display: flex; gap: 15px; align-items: center;">
                        <span style="font-weight: bold; color: var(--color-4); font-size: 16px;">
                            <i class="fa-solid fa-user-check" style="margin-right: 5px;"></i>Xin chào, <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <button class="btn-modern" onclick="window.location.href='Admin/add_product.php'">
                                <i class="fa-solid fa-user-shield" style="margin-right: 5px;"></i>Quản trị
                            </button>
                        <?php endif; ?>
                        <button class="btn-modern btn-logout" onclick="window.location.href='logout.php'">
                            <i class="fa-solid fa-right-from-bracket" style="margin-right: 5px;"></i>Đăng xuất
                        </button>
                    </div>
                <?php else: ?>
                    <div class="header-mid-account">
                        <span class="header-mid-account_icon"><i class="fa-regular fa-user"></i></span>
                        <div class="header-mid-account_button">
                            <span><button id="btnLoginPopup">Đăng nhập</button></span>
                            <span><button id="btnRegisterPopup">Đăng ký</button></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="container-header-bottom">
                <div class="header-bottom-category">
                    <span><i class="fa-solid fa-bars"></i></span>
                    <span>
                        <h5>TẤT CẢ DANH MỤC</h5>
                        <div class="header-bottom-category_details">
                            <ul>
                                <li>Văn học</li>
                                <li>Cổ tích</li>
                                <li>Kỹ năng</li>
                            </ul>
                        </div>
                    </span>
                </div>
                <div class="header-bottom-navbar">
                    <ul>
                        <li><a href="index.php?layout=mainpage">Trang chủ</a></li>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#">Tin tức</a></li>
                        <li><a href="#">Tra cứu đơn</a></li>
                        <li><a href="#">Feedback</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container-dynamicBanner"></div>
    </div>

    <!-- Modal Popup Đăng nhập / Đăng ký -->
    <div id="authModal" class="auth-modal">
        <div class="auth-modal-overlay"></div>
        <div class="auth-modal-content">
            <span class="auth-modal-close">&times;</span>
            
            <!-- Form Đăng nhập -->
            <div id="loginForm" class="auth-form-container">
                <h2>Đăng nhập</h2>
                <form id="frmLogin">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Tên đăng nhập" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Mật khẩu" required>
                    </div>
                    <div id="loginError" style="color: red; font-size: 14px; margin-bottom: 10px; text-align: center;"></div>
                    <div id="loginSuccess" style="color: green; font-size: 14px; margin-bottom: 10px; text-align: center;"></div>
                    <button type="submit" class="auth-btn">Đăng nhập</button>
                </form>
                <p>Chưa có tài khoản? <a href="#" id="switchToRegister">Đăng ký ngay</a></p>
            </div>

            <!-- Form Đăng ký -->
            <div id="registerForm" class="auth-form-container" style="display: none;">
                <h2>Đăng ký</h2>
                <form id="frmRegister">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Tên đăng nhập" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="phone" placeholder="Số điện thoại" required pattern="[0-9]{10,}" title="Số điện thoại phải có ít nhất 10 chữ số">
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" id="regPassword" placeholder="Mật khẩu" required pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}" title="Mật khẩu phải từ 6 ký tự trở lên và chứa cả chữ và số">
                    </div>
                    <div class="input-group">
                        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}" title="Vui lòng nhập lại mật khẩu hợp lệ">
                    </div>
                    <div id="registerError" style="color: red; font-size: 14px; margin-bottom: 10px; text-align: center;"></div>
                    <div id="registerSuccess" style="color: green; font-size: 14px; margin-bottom: 10px; text-align: center;"></div>
                    <button type="submit" class="auth-btn">Đăng ký</button>
                </form>
                <p>Đã có tài khoản? <a href="#" id="switchToLogin">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>

    <script>
        const authModal = document.getElementById('authModal');
        const overlay = document.querySelector('.auth-modal-overlay');
        const btnLoginPopup = document.getElementById('btnLoginPopup');
        const btnRegisterPopup = document.getElementById('btnRegisterPopup');
        const closeBtn = document.querySelector('.auth-modal-close');
        
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');

        function openModal(isLogin = true) {
            authModal.classList.add('active');
            if (isLogin) {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }

        function closeModal() {
            authModal.classList.remove('active');
        }

        if (btnLoginPopup) btnLoginPopup.addEventListener('click', () => openModal(true));
        if (btnRegisterPopup) btnRegisterPopup.addEventListener('click', () => openModal(false));
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        switchToRegister.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(false);
        });

        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(true);
        });

        // Xử lý gửi form đăng nhập qua Ajax
        const frmLoginDOM = document.getElementById('frmLogin');
        const loginErrorDOM = document.getElementById('loginError');
        const loginSuccessDOM = document.getElementById('loginSuccess');

        frmLoginDOM.addEventListener('submit', async (e) => {
            e.preventDefault();
            loginErrorDOM.textContent = '';
            loginSuccessDOM.textContent = '';
            
            const formData = new FormData(frmLoginDOM);
            formData.append('action', 'login');
            
            try {
                const response = await fetch('Admin/auth.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.status === 'success') {
                    loginSuccessDOM.textContent = result.message;
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    loginErrorDOM.textContent = result.message;
                }
            } catch (err) {
                console.error(err);
                loginErrorDOM.textContent = 'Đã xảy ra lỗi khi kết nối tới máy chủ.';
            }
        });

        // Xử lý gửi form đăng ký qua Ajax
        const frmRegisterDOM = document.getElementById('frmRegister');
        const registerErrorDOM = document.getElementById('registerError');
        const registerSuccessDOM = document.getElementById('registerSuccess');

        frmRegisterDOM.addEventListener('submit', async (e) => {
            e.preventDefault();
            registerErrorDOM.textContent = '';
            registerSuccessDOM.textContent = '';
            
            const password = document.getElementById('regPassword').value;
            const confirm_password = frmRegisterDOM.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirm_password) {
                registerErrorDOM.textContent = 'Mật khẩu nhập lại không khớp!';
                return;
            }

            const formData = new FormData(frmRegisterDOM);
            formData.append('action', 'register');
            
            try {
                const response = await fetch('Admin/auth.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.status === 'success') {
                    registerSuccessDOM.textContent = result.message;
                    frmRegisterDOM.reset();
                    setTimeout(() => openModal(true), 1500); // Mở popup đăng nhập sau 1.5s
                } else {
                    registerErrorDOM.textContent = result.message;
                }
            } catch (err) {
                console.error(err);
                registerErrorDOM.textContent = 'Đã xảy ra lỗi khi kết nối tới máy chủ.';
            }
        });
    </script>
</body>
</html>