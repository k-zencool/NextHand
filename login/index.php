<?php
session_start();
require "../config/db.php";

if (isset($_SESSION["user_id"])) {
  header("Location: ../index.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $input = trim($_POST["username_email"]);
  $password = $_POST["password"];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
  $stmt->execute([$input, $input]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user["password"])) {
    if ($user["status"] == "banned") {
      $error = "บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อแอดมิน";
    } elseif ($user["status"] == "suspended") {
      $error = "บัญชีนี้ถูกระงับชั่วคราว";
    } else {

      // --- ✅ ล็อกอินผ่านฉลุย ---
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["username"] = $user["username"];
      $_SESSION["role"] = $user["role"];
      $_SESSION["item_quota"] = $user["item_quota"];
      $_SESSION["profile_image"] = !empty($user["profile_image"])
        ? $user["profile_image"]
        : "default.png";

      $update = $pdo->prepare(
        "UPDATE users SET last_login = NOW() WHERE id = ?",
      );
      $update->execute([$user["id"]]);

      // กำหนดหน้าปลายทาง
      $redirect_url = $user["role"] == "admin" ? "../admin/" : "../index.php";

      // 🔥 หยุดการทำงาน PHP แล้วแสดงหน้า Loading สวยๆ แทน
      ?>
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
                <style> body { font-family: 'Kanit', sans-serif; background: #ecf0f3; } </style>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ!',
                        text: 'กำลังพาไปยังหน้าร้านค้า...',
                        timer: 1500, // ดีเลย์ 1.5 วินาที
                        timerProgressBar: true,
                        showConfirmButton: false,
                        willClose: () => {
                            window.location.href = "<?php echo $redirect_url; ?>";
                        }
                    });
                </script>
            </body>
            </html>
            <?php exit(); // จบการทำงานตรงนี้เลย ไม่ต้องโหลดฟอร์มด้านล่างต่อ
    }
  } else {
    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
  }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - NextHand</title>
</head>
<body>

    <?php include "../includes/navbar.php"; ?>

    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="text-center mb-4">
                <i class="fa-solid fa-right-to-bracket fa-3x text-primary"></i>
            </div>

            <h2 class="auth-title">ยินดีต้อนรับกลับ!</h2>
            <p class="auth-subtitle">เข้าสู่ระบบเพื่อจัดการร้านค้าและสินค้าของคุณ</p>

            <?php if (isset($_GET["registered"])): ?>
                <div class="alert alert-success text-center rounded-pill py-2 small" role="alert">
                    <i class="fa-solid fa-check-circle me-1"></i> สมัครสมาชิกสำเร็จ! เข้าสู่ระบบได้เลย
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center rounded-pill py-2 small" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["logout"])): ?>
                 <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label ps-3 text-muted small">ชื่อผู้ใช้ หรือ อีเมล</label>
                    <input type="text" name="username_email" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label ps-3 text-muted small">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label small text-muted" for="rememberMe">จำฉันไว้</label>
                    </div>
                    <a href="#" class="small text-decoration-none text-muted">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" class="btn-auth">เข้าสู่ระบบ</button>
            </form>

            <div class="link-back">
                ยังไม่มีบัญชี? <a href="../register/">สมัครสมาชิกใหม่</a>
            </div>
        </div>
    </div>

    <style>
        .auth-wrapper {
            min-height: 80vh;
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
            max-width: 400px;
            border: 1px solid #f0f0f0;
        }

        .auth-title { text-align: center; font-weight: 700; color: #333; margin-bottom: 5px; }
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
            border: none; color: white; margin-top: 10px; transition: 0.3s;
        }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 114, 255, 0.4); }

        .link-back { text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem; }
        .link-back a { color: #0072ff; font-weight: 600; }
    </style>

</body>
</html>
