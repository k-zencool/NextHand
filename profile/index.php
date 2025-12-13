<?php
session_start();
require "../config/db.php";

// 1. เช็คว่าล็อกอินยัง? ถ้ายัง ถีบไปหน้า Login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/");
  exit();
}

$user_id = $_SESSION["user_id"];
$msg = "";
$msg_type = "";

// 2. ถ้ามีการกดบันทึกข้อมูล (POST)
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

  // --- ส่วนอัปโหลดรูปภาพ ---
  $profile_image_name = ""; // ตัวแปรเก็บชื่อรูป (ถ้าไม่อัป ก็จะว่างไว้)

  if (!empty($_FILES["profile_image"]["name"])) {
    $target_dir = "../uploads/";
    $file_ext = strtolower(
      pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION),
    );
    // ตั้งชื่อไฟล์ใหม่เป็น userid_timestamp.jpg กันชื่อซ้ำ
    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    // อนุญาตเฉพาะไฟล์รูป
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (in_array($file_ext, $allowed_types)) {
      if (
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)
      ) {
        $profile_image_name = $new_filename;
        // อัปเดต Session ทันที จะได้เห็นรูปเปลี่ยนใน Navbar เลย
        $_SESSION["profile_image"] = $new_filename;
      } else {
        $msg = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
        $msg_type = "danger";
      }
    } else {
      $msg = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF) เท่านั้น";
      $msg_type = "danger";
    }
  }

  // --- SQL Update ---
  // ถ้ามีการอัปรูปใหม่ ให้แก้ field profile_image ด้วย
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
    // ถ้าไม่อัปรูป ก็ไม่ต้องไปยุ่งกับ field profile_image (ใช้รูปเดิม)
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

// 3. ดึงข้อมูลล่าสุดมาโชว์ในฟอร์ม
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรไฟล์ - NextHand</title>
    </head>
<body>

    <?php include "../includes/navbar.php"; ?>

    <div class="container-main">
        <div class="row">

            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                    <div class="position-relative d-inline-block mb-3">
                        <?php
                        $img_path = !empty($user["profile_image"])
                          ? "../uploads/" . $user["profile_image"]
                          : "../uploads/default.png";
                        // ถ้าหาไฟล์ไม่เจอ ให้ใช้รูป default จากเน็ต
                        if (!file_exists($img_path)) {
                          $img_path =
                            "https://via.placeholder.com/150?text=User";
                        }
                        ?>
                        <img src="<?php echo $img_path; ?>" class="rounded-circle border border-3 border-white shadow" style="width: 150px; height: 150px; object-fit: cover;">

                        <label for="fileUpload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 cursor-pointer shadow-sm" style="cursor: pointer;">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                    </div>

                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars(
                      $user["username"],
                    ); ?></h4>
                    <p class="text-muted small"><?php echo htmlspecialchars(
                      $user["email"],
                    ); ?></p>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <?php if ($user["is_verified"]): ?>
                            <span class="badge bg-success rounded-pill"><i class="fa-solid fa-circle-check"></i> ยืนยันตัวตนแล้ว</span>
                        <?php else: ?>
                            <span class="badge bg-secondary rounded-pill">รอการยืนยันตัวตน</span>
                        <?php endif; ?>

                        <span class="badge bg-warning text-dark rounded-pill">
                            <i class="fa-solid fa-box"></i> โควตา <?php echo number_format(
                              $user["item_quota"],
                            ); ?> ชิ้น
                        </span>
                    </div>

                    <hr>
                    <div class="text-start">
                        <small class="text-muted fw-bold">เกี่ยวกับร้านค้า</small>
                        <p class="small text-muted mt-1">
                            <?php echo !empty($user["bio"])
                              ? nl2br(htmlspecialchars($user["bio"]))
                              : "ยังไม่มีข้อมูลแนะนำร้านค้า..."; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-pen text-primary"></i> แก้ไขข้อมูลส่วนตัว</h4>

                    <?php if ($msg): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> rounded-pill text-center">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="file" id="fileUpload" name="profile_image" class="d-none" accept="image/*" onchange="previewImage(event)">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">ชื่อจริง</label>
                                <input type="text" name="first_name" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["first_name"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["last_name"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["phone"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Line ID</label>
                                <input type="text" name="line_id" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["line_id"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Facebook Link</label>
                            <input type="text" name="facebook_link" class="form-control rounded-pill" placeholder="https://facebook.com/..." value="<?php echo htmlspecialchars(
                              $user["facebook_link"] ?? "",
                            ); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">คำแนะนำร้านค้า (Bio)</label>
                            <textarea name="bio" class="form-control rounded-4" rows="3" placeholder="เขียนแนะนำร้านค้าของคุณสั้นๆ..."><?php echo htmlspecialchars(
                              $user["bio"] ?? "",
                            ); ?></textarea>
                        </div>

                        <hr class="my-4">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger"></i> ที่อยู่สำหรับจัดส่ง / นัดรับ</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small">ที่อยู่</label>
                            <textarea name="address" class="form-control rounded-4" rows="2"><?php echo htmlspecialchars(
                              $user["address"] ?? "",
                            ); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">จังหวัด</label>
                                <input type="text" name="province" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["province"] ?? "",
                                ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">รหัสไปรษณีย์</label>
                                <input type="text" name="zipcode" class="form-control rounded-pill" value="<?php echo htmlspecialchars(
                                  $user["zipcode"] ?? "",
                                ); ?>">
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); border: none;">
                                <i class="fa-solid fa-save"></i> บันทึกข้อมูล
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
                var output = document.querySelector('img.rounded-circle');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>
