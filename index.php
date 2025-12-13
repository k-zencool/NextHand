<?php
session_start();
require 'config/db.php';

// 1. รับค่าค้นหา
$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$category = isset($_GET['cat']) ? trim($_GET['cat']) : "";

// 2. Query สินค้า
$sql = "SELECT p.*, u.username, u.profile_image, u.is_verified 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'active'";

$params = [];

// กรองตามคำค้นหา
if ($search) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// (อนาคต) กรองตามหมวดหมู่
// if ($category) { ... }

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

    <?php include 'includes/navbar.php'; ?>

    <div class="container-main">

        <?php if(!$search): ?>
        <div class="hero-section">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="fw-bold display-5 mb-3">ส่งต่อของรัก <br>ในราคามิตรภาพ</h1>
                    <p class="lead opacity-75 mb-4">แหล่งรวมสินค้ามือสองคุณภาพดี นัดรับได้ ปลอดภัยหายห่วง</p>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="register/" class="btn btn-light text-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-user-plus me-2"></i> สมัครสมาชิกฟรี
                        </a>
                    <?php else: ?>
                        <a href="post/" class="btn btn-light text-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-camera me-2"></i> ลงขายสินค้า
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-md-5 d-none d-md-block text-center opacity-50">
                    <i class="fa-solid fa-bag-shopping fa-8x text-white"></i>
                </div>
            </div>
        </div>

        <div class="category-section">
            <h5 class="fw-bold text-dark mb-2">
                <i class="fa-solid fa-layer-group text-primary me-2"></i> เลือกตามหมวดหมู่
            </h5>
            <p class="text-muted small mb-0">ค้นหาสิ่งที่คุณต้องการได้ง่ายๆ</p>
            
            <div class="category-scroll">
                <a href="?cat=mobile" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <span>มือถือ</span>
                </a>
                
                <a href="?cat=vehicles" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-car-side"></i></div>
                    <span>ยานยนต์</span>
                </a>

                <a href="?cat=fashion" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-shirt"></i></div>
                    <span>แฟชั่น</span>
                </a>
                
                <a href="?cat=electronics" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-plug"></i></div>
                    <span>เครื่องใช้ไฟฟ้า</span>
                </a>

                <a href="?cat=camera" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-camera"></i></div>
                    <span>กล้อง</span>
                </a>
                
                <a href="?cat=amulet" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-hands-praying"></i></div>
                    <span>พระเครื่อง</span>
                </a>

                <a href="?cat=computer" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-laptop"></i></div>
                    <span>คอมพิวเตอร์</span>
                </a>
                
                <a href="?cat=pets" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-paw"></i></div>
                    <span>สัตว์เลี้ยง</span>
                </a>

                <a href="?cat=shoes" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-shoe-prints"></i></div>
                    <span>รองเท้า</span>
                </a>
                
                <a href="?cat=game" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-gamepad"></i></div>
                    <span>เกม/ของเล่น</span>
                </a>

                <a href="?cat=sports" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-basketball"></i></div>
                    <span>กีฬา</span>
                </a>
                
                <a href="?cat=home" class="category-item">
                    <div class="category-icon"><i class="fa-solid fa-couch"></i></div>
                    <span>แต่งบ้าน</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0 text-dark">
                <?php if($search): ?>
                    ผลการค้นหา: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    <i class="fa-solid fa-fire text-danger me-2"></i> สินค้ามาใหม่
                <?php endif; ?>
            </h4>
            <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo count($products); ?> รายการ</span>
        </div>

        <?php if(count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach($products as $p): ?>
                    <div class="product-card position-relative">
                        <button class="btn-wishlist shadow-sm" title="ถูกใจ"><i class="fa-solid fa-heart"></i></button>

                        <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark">
                            <div class="position-relative">
                                <?php 
                                    $img_path = "uploads/" . $p['image'];
                                    if(!file_exists($img_path) || empty($p['image'])) $img_path = "https://via.placeholder.com/300x200?text=No+Image";
                                ?>
                                <img src="<?php echo $img_path; ?>" class="product-img" alt="<?php echo htmlspecialchars($p['title']); ?>">
                                
                                <div class="position-absolute bottom-0 start-0 bg-dark text-white px-3 py-1 m-2 rounded-pill shadow-sm fw-bold" style="font-size: 0.9rem;">
                                    ฿<?php echo number_format($p['price']); ?>
                                </div>
                            </div>

                            <div class="product-info">
                                <h5 class="product-title"><?php echo htmlspecialchars($p['title']); ?></h5>
                                
                                <div class="d-flex align-items-center mt-3 pt-2 border-top">
                                    <?php 
                                        $seller_img = !empty($p['profile_image']) ? "uploads/" . $p['profile_image'] : "uploads/default.png"; 
                                        if(!file_exists($seller_img)) $seller_img = "https://via.placeholder.com/50?text=U";
                                    ?>
                                    <img src="<?php echo $seller_img; ?>" class="rounded-circle border me-2" width="25" height="25" style="object-fit:cover;">
                                    
                                    <div class="small text-muted flex-grow-1 text-truncate" style="font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($p['username']); ?>
                                        <?php if($p['is_verified']): ?>
                                            <i class="fa-solid fa-circle-check text-success ms-1" style="font-size: 0.8rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <small class="text-secondary" style="font-size: 0.75rem;">
                                        <?php 
                                            $time_diff = time() - strtotime($p['created_at']);
                                            if($time_diff < 3600) echo floor($time_diff/60) . " นาที";
                                            elseif($time_diff < 86400) echo floor($time_diff/3600) . " ชม.";
                                            else echo floor($time_diff/86400) . " วัน";
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-light">
                <div class="mb-3 opacity-50">
                    <i class="fa-solid fa-box-open fa-5x text-secondary"></i>
                </div>
                <h4 class="text-dark fw-bold">ไม่พบสินค้าที่คุณค้นหา</h4>
                <p class="text-muted">ลองค้นหาด้วยคำอื่น หรือมาลงขายสินค้าชิ้นแรกกันเถอะ</p>
                <a href="post/" class="btn btn-primary rounded-pill px-4 mt-2 fw-bold">
                    <i class="fa-solid fa-plus me-2"></i> ลงขายสินค้า
                </a>
            </div>
        <?php endif; ?>

        <div class="simple-footer">
            <p class="m-0">&copy; <?php echo date('Y'); ?> NextHand. ตลาดซื้อขายสินค้ามือสองออนไลน์</p>
            <small>Created with ❤️ by ZenCool</small>
        </div>

    </div>

</body>
</html>