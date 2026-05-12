<?php
/**
 * feedback.php — Trang gửi Feedback
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'Connect/connect.php';

$success = '';
$error = '';

$user_id = $_SESSION['user_id'] ?? null;
$default_name = '';
$default_email = '';

// Nếu đã đăng nhập, lấy tên từ session
if ($user_id) {
    $default_name = $_SESSION['username'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Vui lòng điền đầy đủ các trường yêu cầu.";
    } else {
        $uid_sql = $user_id ? intval($user_id) : 'NULL';
        $sql = "INSERT INTO feedbacks (User_ID, Name, Email, Subject, Message) VALUES ($uid_sql, '$name', '$email', '$subject', '$message')";
        if ($conn->query($sql)) {
            $success = "Cảm ơn bạn! Feedback của bạn đã được gửi thành công.";
        } else {
            $error = "Đã xảy ra lỗi khi gửi feedback: " . $conn->error;
        }
    }
}
?>

<style>
.fb-wrapper {
    max-width: 600px;
    margin: 40px auto;
    padding: 30px 40px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    color: var(--text-color);
}
.fb-wrapper h2 {
    font-size: 28px;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 10px;
    text-align: center;
}
.fb-wrapper p {
    text-align: center;
    color: var(--text-muted);
    margin-bottom: 30px;
    font-size: 15px;
}
.fb-form-group {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}
.fb-form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}
.fb-form-group input, .fb-form-group textarea {
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.2s;
    background: var(--bg-color);
}
.fb-form-group input:focus, .fb-form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    background: #fff;
}
.fb-form-group textarea {
    resize: vertical;
    min-height: 120px;
}
.fb-btn {
    width: 100%;
    padding: 14px;
    background: var(--primary-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.3s;
}
.fb-btn:hover {
    background: var(--primary-dark, #5c4e43);
}
.fb-alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.fb-alert.success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}
.fb-alert.error {
    background: #fce4e4;
    color: #c0392b;
    border: 1px solid #f5c6cb;
}
</style>

<div class="fb-wrapper">
    <h2>Gửi Phản Hồi</h2>
    <p>Chúng tôi luôn trân trọng mọi ý kiến đóng góp từ bạn để cải thiện chất lượng dịch vụ.</p>

    <?php if ($success): ?>
        <div class="fb-alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="fb-alert error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="fb-form-group">
            <label>Họ và tên *</label>
            <input type="text" name="name" required placeholder="Nhập họ tên của bạn" value="<?= htmlspecialchars($default_name) ?>">
        </div>
        <div class="fb-form-group">
            <label>Email *</label>
            <input type="email" name="email" required placeholder="Nhập địa chỉ email" value="<?= htmlspecialchars($default_email) ?>">
        </div>
        <div class="fb-form-group">
            <label>Tiêu đề *</label>
            <input type="text" name="subject" required placeholder="Chủ đề bạn muốn góp ý">
        </div>
        <div class="fb-form-group">
            <label>Nội dung *</label>
            <textarea name="message" required placeholder="Nhập chi tiết nội dung phản hồi của bạn..."></textarea>
        </div>
        <button type="submit" class="fb-btn"><i class="fa-solid fa-paper-plane"></i> Gửi Phản Hồi</button>
    </form>
</div>
