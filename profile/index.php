<?php
session_start();
require "../config/db.php";

// 1. เช็คล็อกอิน
if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/");
  exit();
}

$user_id = $_SESSION["user_id"];
$msg = "";
$msg_type = "";

// 2. ถ้ามีการกดปุ่ม "บันทึกข้อมูล" (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $first_name = trim($_POST["first_name"]);
  $last_name = trim($_POST["last_name"]);
  $phone = trim($_POST["phone"]);
  $line_id = trim($_POST["line_id"]);
  $facebook = trim($_POST["facebook_link"]);
  $bio = trim($_POST["bio"]);
  $address = trim($_POST["address"]);
  $province = trim($_POST["province"]);
  $zipcode = trim($_POST["zipcode"]);

  // --- ส่วนจัดการอัปโหลดรูปภาพ ---
  $profile_image_name = "";

  if (!empty($_FILES["profile_image"]["name"])) {
    $target_dir = "../uploads/";
    $file_ext = strtolower(
      pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION),
    );
    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    $allowed = ["jpg", "jpeg", "png", "webp"];

    if (in_array($file_ext, $allowed)) {
      if (
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)
      ) {
        $profile_image_name = $new_filename;
        $_SESSION["profile_image"] = $new_filename;
      } else {
        $msg = "อัปโหลดรูปไม่สำเร็จ";
        $msg_type = "danger";
      }
    } else {
      $msg = "ขอเป็นไฟล์รูปเท่านั้น (JPG, PNG, WEBP)";
      $msg_type = "danger";
    }
  }

  // --- บันทึกลงฐานข้อมูล ---
  if ($profile_image_name != "") {
    $sql =
      "UPDATE users SET first_name=?, last_name=?, phone=?, line_id=?, facebook_link=?, bio=?, address=?, province=?, zipcode=?, profile_image=? WHERE id=?";
    $params = [
      $first_name,
      $last_name,
      $phone,
      $line_id,
      $facebook,
      $bio,
      $address,
      $province,
      $zipcode,
      $profile_image_name,
      $user_id,
    ];
  } else {
    $sql =
      "UPDATE users SET first_name=?, last_name=?, phone=?, line_id=?, facebook_link=?, bio=?, address=?, province=?, zipcode=? WHERE id=?";
    $params = [
      $first_name,
      $last_name,
      $phone,
      $line_id,
      $facebook,
      $bio,
      $address,
      $province,
      $zipcode,
      $user_id,
    ];
  }

  $stmt = $pdo->prepare($sql);
  if ($stmt->execute($params)) {
    $msg = "บันทึกข้อมูลเรียบร้อยแล้ว!";
    $msg_type = "success";
  } else {
    $msg = "เกิดข้อผิดพลาด ลองใหม่อีกครั้ง";
    $msg_type = "danger";
  }
}

// 3. ดึงข้อมูล User ล่าสุด
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 4. ดึงสถิติสินค้า
$stmt_count = $pdo->prepare(
  "SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'active'",
);
$stmt_count->execute([$user_id]);
$active_product_count = $stmt_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลส่วนตัว - NextHand</title>
</head>
<body>

    <?php include "../includes/navbar.php"; ?>

    <div class="container-main">
        <div class="row align-items-stretch">

            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">

                    <div class="text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <?php
                            $img_file = !empty($user["profile_image"])
                              ? $user["profile_image"]
                              : "default.png";
                            $img_path = "../uploads/" . $img_file;
                            if (!file_exists($img_path)) {
                              $img_path =
                                "https://via.placeholder.com/150?text=User";
                            }
                            ?>
                            <img id="preview-avatar" src="<?php echo $img_path; ?>" class="rounded-circle border border-3 border-light shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">

                            <label for="fileUpload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow-sm" style="cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                        </div>

                        <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars(
                          $user["username"],
                        ); ?></h4>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars(
                          $user["email"],
                        ); ?></p>

                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <?php if ($user["is_verified"]): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Verified</span>
                            <?php else: ?>
                                <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3">ยืนยันตัวตน</a>
                            <?php endif; ?>

                            <a href="../topup/" class="btn btn-sm btn-warning text-dark rounded-pill px-3">
                                <i class="fa-solid fa-ticket me-1"></i> โควตา <?php echo number_format(
                                  $user["item_quota"],
                                ); ?>
                            </a>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <div class="mb-4">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">About Shop</small>
                        <p class="small text-secondary mt-2 mb-0">
                            <?php echo !empty($user["bio"])
                              ? nl2br(htmlspecialchars($user["bio"]))
                              : "- ยังไม่มีข้อมูลแนะนำร้านค้า -"; ?>
                        </p>
                    </div>

                    <div class="mb-4">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Shop Stats</small>
                        <ul class="list-unstyled mt-2 mb-0 small text-secondary">
                            <li class="mb-2">
                                <i class="fa-solid fa-calendar-alt me-2 text-primary opacity-50"></i>
                                เป็นสมาชิกตั้งแต่: <strong><?php echo date(
                                  "d M Y",
                                  strtotime($user["created_at"]),
                                ); ?></strong>
                            </li>
                            <li>
                                <i class="fa-solid fa-box-open me-2 text-success opacity-50"></i>
                                สินค้าที่ลงขาย: <strong><?php echo number_format(
                                  $active_product_count,
                                ); ?></strong> ชิ้น
                            </li>
                        </ul>
                    </div>

                    <div class="mt-auto">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Quick Actions</small>
                        <div class="d-grid gap-2 mt-2">
                            <a href="../shop.php?user=<?php echo $user[
                              "id"
                            ]; ?>" class="btn btn-outline-primary rounded-pill btn-sm fw-bold">
                                <i class="fa-solid fa-store me-2"></i> ดูหน้าร้านของฉัน
                            </a>
                            <a href="../my-products/" class="btn btn-outline-secondary rounded-pill btn-sm fw-bold">
                                <i class="fa-solid fa-boxes-stacked me-2"></i> จัดการสินค้า
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h4 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-user-pen text-primary me-2"></i> แก้ไขข้อมูลส่วนตัว</h4>

                    <?php if ($msg): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> rounded-pill text-center py-2 mb-4">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="file" id="fileUpload" name="profile_image" class="d-none" accept="image/*" onchange="previewImage(event)">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">ชื่อจริง</label>
                                <input type="text" name="first_name" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["first_name"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["last_name"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["phone"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Line ID</label>
                                <input type="text" name="line_id" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["line_id"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Facebook Link</label>
                            <input type="text" name="facebook_link" class="form-control rounded-pill bg-light border-0 px-3" placeholder="https://facebook.com/..." value="<?php echo htmlspecialchars(
                              $user["facebook_link"] ?? "",
                            ); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">แนะนำร้านค้า (Bio)</label>
                            <textarea name="bio" class="form-control rounded-4 bg-light border-0 p-3" rows="3" placeholder="เขียนแนะนำร้านค้าของคุณสั้นๆ..."><?php echo htmlspecialchars(
                              $user["bio"] ?? "",
                            ); ?></textarea>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-location-dot text-danger me-2"></i> ที่อยู่จัดส่ง / นัดรับ</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">ที่อยู่</label>
                            <textarea name="address" class="form-control rounded-4 bg-light border-0 p-3" rows="2"><?php echo htmlspecialchars(
                              $user["address"] ?? "",
                            ); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">จังหวัด</label>
                                <input type="text" name="province" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["province"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">รหัสไปรษณีย์</label>
                                <input type="text" name="zipcode" class="form-control rounded-pill bg-light border-0 px-3" value="<?php echo htmlspecialchars(
                                  $user["zipcode"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm" style="background: linear-gradient(135deg, #0984e3 0%, #00cec9 100%); border: none;">
                                <i class="fa-solid fa-save me-2"></i> บันทึกข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('preview-avatar');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>
