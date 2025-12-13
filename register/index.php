<?php
session_start();
require '../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "ชื่อผู้ใช้หรืออีเมลนี้มีคนใช้แล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, item_quota, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user', 10, 'default.png')";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone])) {
                header("Location: ../login/?registered=1");
                exit;
            } else {
                $error = "เกิดข้อผิดพลาดทางเทคนิค กรุณาลองใหม่";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - NextHand</title>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="auth-wrapper">
        <div class="auth-card">
            
            <div class="text-center mb-3">
                <i class="fa-solid fa-user-plus fa-3x text-primary"></i>
            </div>
            
            <h2 class="auth-title">สร้างบัญชีใหม่</h2>
            <p class="auth-subtitle">สมัครสมาชิกเพื่อเริ่มลงขายสินค้าได้ทันที</p>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger text-center rounded-pill py-2" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="row">
                    <div class="col-6 mb-3">
                        <input type="text" name="first_name" class="form-control" placeholder="ชื่อจริง" required>
                    </div>
                    <div class="col-6 mb-3">
                        <input type="text" name="last_name" class="form-control" placeholder="นามสกุล" required>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้ (Username)" required>
                </div>
                
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="อีเมล (Email)" required>
                </div>

                <div class="mb-3">
                    <input type="text" name="phone" class="form-control" placeholder="เบอร์โทรศัพท์" required>
                </div>

                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                </div>
                
                <div class="mb-3">
                    <input type="password" name="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
                </div>

                <button type="submit" class="btn-auth">สมัครสมาชิก</button>
            </form>
            
            <div class="link-back">
                มีบัญชีอยู่แล้ว? <a href="../login/">เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>

    <style>
        .auth-wrapper {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
            border: 1px solid #f0f0f0;
        }
        
        .auth-title { text-align: center; font-weight: 700; color: #333; margin-bottom: 10px; }
        .auth-subtitle { text-align: center; color: #999; margin-bottom: 30px; font-size: 0.9rem; }
        
        .form-control {
            border-radius: 50px; padding: 12px 20px; background: #f8f9fa; border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(0, 114, 255, 0.1); border-color: #0072ff; background: white;
        }
        
        .btn-auth {
            width: 100%; border-radius: 50px; padding: 12px; font-weight: 600;
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            border: none; color: white; margin-top: 20px; transition: 0.3s;
        }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 114, 255, 0.4); }
        
        .link-back { text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem; }
        .link-back a { color: #0072ff; font-weight: 600; }
    </style>

</body>
</html>