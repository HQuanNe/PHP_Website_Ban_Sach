<?php
session_start();
include '../Connect/connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = $conn->real_escape_string($_POST['username']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu nhập lại không khớp!']);
        exit;
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải từ 6 ký tự trở lên và chứa cả chữ và số.']);
        exit;
    }

    if (!preg_match('/^[0-9]{10,}$/', $phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Số điện thoại phải có ít nhất 10 chữ số.']);
        exit;
    }

    // Kiểm tra username đã tồn tại chưa
    $check = $conn->query("SELECT * FROM users WHERE UserName = '$username'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập đã tồn tại!']);
        exit;
    }

    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (UserName, Passwd, Phone, Role) VALUES ('$username', '$hashed_pw', '$phone', 'customer')";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Bạn có thể đăng nhập ngay.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->error]);
    }
}
elseif ($action === 'login') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE UserName = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Passwd']) || $password === $user['Passwd']) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['UserName'];
            $_SESSION['role'] = $user['Role'];
            echo json_encode(['status' => 'success', 'message' => 'Đăng nhập thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập không tồn tại!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
}
?>
