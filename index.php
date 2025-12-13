<?php
session_start();
require "config/db.php";

// 1. รับค่าค้นหา (Search Query)
$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";

// 2. สร้าง SQL Query
$sql = "SELECT p.*, u.username, u.profile_image, u.is_verified
        FROM products p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'active'";

$params = [];

// ถ้ามีการค้นหา ให้เพิ่มเงื่อนไข WHERE
if ($search) {
  $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

// เรียงลำดับจากใหม่ไปเก่า
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextHand - ตลาดซื้อขายสินค้ามือสอง</title>
    </head>
<body>

    <?php include "includes/navbar.php"; ?>

    <?php if (!$search): ?>
    <div class="container-fluid mb-5" style="background: linear-gradient(135deg, #0984e3 0%, #74b9ff 100%); color: white; padding: 60px 0;">
        <div class="container text-center">
            <h1 class="fw-bold display-4">ตลาดมือสองที่คุณวางใจ</h1>
            <p class="lead opacity-75">ส่งต่อของรัก ตามหาของที่ใช่ ในราคามิตรภาพ</p>
            <?php if (!isset($_SESSION["user_id"])): ?>
                <a href="register/" class="btn btn-light text-primary rounded-pill px-4 fw-bold mt-3 shadow-sm">
                    <i class="fa-solid fa-user-plus"></i> สมัครสมาชิกเลย
                </a>
            <?php else: ?>
                <a href="post/" class="btn btn-light text-primary rounded-pill px-4 fw-bold mt-3 shadow-sm">
                    <i class="fa-solid fa-camera"></i> ลงขายสินค้า
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-main">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title m-0">
                <?php if ($search): ?>
                    <i class="fa-solid fa-magnifying-glass text-primary"></i> ผลการค้นหา: "<?php echo htmlspecialchars(
                      $search,
                    ); ?>"
                <?php else: ?>
                    <i class="fa-solid fa-fire text-danger"></i> สินค้ามาใหม่
                <?php endif; ?>
            </h3>
            <span class="text-muted small"><?php echo count(
              $products,
            ); ?> รายการ</span>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                    <a href="product.php?id=<?php echo $p[
                      "id"
                    ]; ?>" class="product-card text-decoration-none text-dark">
                        <div class="position-relative">
                            <?php
                            $img_path = "uploads/" . $p["image"];
                            if (!file_exists($img_path) || empty($p["image"])) {
                              $img_path =
                                "https://via.placeholder.com/300x200?text=No+Image";
                            }
                            ?>
                            <img src="<?php echo $img_path; ?>" class="product-img" alt="<?php echo htmlspecialchars(
  $p["title"],
); ?>">

                            <div class="position-absolute bottom-0 end-0 bg-white px-3 py-1 m-2 rounded-pill shadow-sm fw-bold text-primary">
                                ฿<?php echo number_format($p["price"]); ?>
                            </div>
                        </div>

                        <div class="product-info">
                            <h5 class="product-title"><?php echo htmlspecialchars(
                              $p["title"],
                            ); ?></h5>

                            <div class="d-flex align-items-center mt-3">
                                <?php
                                $seller_img = !empty($p["profile_image"])
                                  ? "uploads/" . $p["profile_image"]
                                  : "uploads/default.png";
                                if (!file_exists($seller_img)) {
                                  $seller_img =
                                    "https://via.placeholder.com/50?text=U";
                                }
                                ?>
                                <img src="<?php echo $seller_img; ?>" class="rounded-circle border me-2" width="30" height="30" style="object-fit:cover;">

                                <div class="small text-muted flex-grow-1 text-truncate">
                                    <?php echo htmlspecialchars(
                                      $p["username"],
                                    ); ?>
                                    <?php if ($p["is_verified"]): ?>
                                        <i class="fa-solid fa-circle-check text-success ms-1" title="ยืนยันตัวตนแล้ว"></i>
                                    <?php endif; ?>
                                </div>

                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <?php
                                    // คำนวณเวลาแบบ "2 ชั่วโมงที่แล้ว"
                                    $time_diff =
                                      time() - strtotime($p["created_at"]);
                                    if ($time_diff < 60) {
                                      echo "เมื่อสักครู่";
                                    } elseif ($time_diff < 3600) {
                                      echo floor($time_diff / 60) .
                                        " นาทีที่แล้ว";
                                    } elseif ($time_diff < 86400) {
                                      echo floor($time_diff / 3600) .
                                        " ชม.ที่แล้ว";
                                    } else {
                                      echo floor($time_diff / 86400) .
                                        " วันที่แล้ว";
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fa-solid fa-box-open fa-4x text-muted opacity-25"></i>
                </div>
                <h4 class="text-muted">ยังไม่มีสินค้าในตอนนี้</h4>
                <p class="text-muted small">มาเป็นคนแรกที่ลงขายสินค้ากันเถอะ!</p>
                <a href="post/" class="btn btn-primary rounded-pill px-4 mt-2">
                    <i class="fa-solid fa-plus"></i> ลงขายเลย
                </a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
