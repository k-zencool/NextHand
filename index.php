<?php
session_start();
require 'config/db.php';

// 1. รับค่าค้นหาและหมวดหมู่
$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$category = isset($_GET['cat']) ? trim($_GET['cat']) : "";

// 2. Query สินค้า (แสดงเฉพาะสถานะ active)
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

// กรองตามหมวดหมู่ (กูเติมให้แล้ว)
if ($category) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-img { width: 100%; height: 200px; object-fit: cover; border-radius: 12px 12px 0 0; }
        .product-card { background: #fff; border-radius: 15px; border: 1px solid #eee; transition: 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .hero-section { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); color: #fff; padding: 60px 0; border-radius: 20px; margin-bottom: 40px; }
        .category-scroll { display: flex; overflow-x: auto; gap: 15px; padding: 10px 0 20px 0; white-space: nowrap; scrollbar-width: none; }
        .category-item { text-decoration: none; color: #333; text-align: center; min-width: 80px; }
        .category-icon { width: 55px; height: 55px; background: #fff; border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-size: 1.5rem; color: #0d6efd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-wishlist { position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 35px; height: 35px; color: #ff4d4d; z-index: 5; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/navbar.php'; ?>

    <div class="container py-4">

        <?php if(!$search && !$category): ?>
        <div class="hero-section px-4 px-md-5">
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
                <div class="col-md-5 d-none d-md-block text-center opacity-25">
                    <i class="fa-solid fa-bag-shopping fa-8x text-white"></i>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h5 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-layer-group text-primary me-2"></i> เลือกตามหมวดหมู่
            </h5>
            <div class="category-scroll">
                <?php 
                $all_cats = [
                    'mobile' => ['icon' => 'fa-mobile-screen-button', 'name' => 'มือถือ'],
                    'vehicles' => ['icon' => 'fa-car-side', 'name' => 'ยานยนต์'],
                    'fashion' => ['icon' => 'fa-shirt', 'name' => 'แฟชั่น'],
                    'electronics' => ['icon' => 'fa-plug', 'name' => 'เครื่องใช้ไฟฟ้า'],
                    'camera' => ['icon' => 'fa-camera', 'name' => 'กล้อง'],
                    'amulet' => ['icon' => 'fa-hands-praying', 'name' => 'พระเครื่อง'],
                    'computer' => ['icon' => 'fa-laptop', 'name' => 'คอมพิวเตอร์'],
                    'pets' => ['icon' => 'fa-paw', 'name' => 'สัตว์เลี้ยง'],
                    'shoes' => ['icon' => 'fa-shoe-prints', 'name' => 'รองเท้า'],
                    'game' => ['icon' => 'fa-gamepad', 'name' => 'เกม'],
                    'sports' => ['icon' => 'fa-basketball', 'name' => 'กีฬา'],
                    'home' => ['icon' => 'fa-couch', 'name' => 'แต่งบ้าน']
                ];
                foreach($all_cats as $key => $val): ?>
                <a href="?cat=<?php echo $key; ?>" class="category-item">
                    <div class="category-icon"><i class="fa-solid <?php echo $val['icon']; ?>"></i></div>
                    <span class="small fw-bold"><?php echo $val['name']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0 text-dark">
                <?php 
                    if($search) echo 'ผลการค้นหา: "' . htmlspecialchars($search) . '"';
                    elseif($category) echo 'หมวดหมู่: ' . ($all_cats[$category]['name'] ?? 'อื่นๆ');
                    else echo '<i class="fa-solid fa-fire text-danger me-2"></i>สินค้ามาใหม่';
                ?>
            </h4>
            <span class="badge bg-white text-dark border rounded-pill px-3 py-2"><?php echo count($products); ?> รายการ</span>
        </div>

        <?php if(count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach($products as $p): ?>
                    <div class="product-card position-relative overflow-hidden">
                        <button class="btn-wishlist shadow-sm" title="ถูกใจ"><i class="fa-solid fa-heart"></i></button>

                        <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark">
                            <div class="position-relative">
                                <?php 
                                    // 🔥 จุดตาย! ต้องเป็น uploads/products/
                                    $img_path = "uploads/products/" . $p['image'];
                                    if(empty($p['image']) || !file_exists($img_path)) $img_path = "assets/img/no-image.png"; 
                                ?>
                                <img src="<?php echo $img_path; ?>" class="product-img" alt="<?php echo htmlspecialchars($p['title']); ?>">
                                
                                <div class="position-absolute bottom-0 start-0 bg-primary text-white px-3 py-1 m-2 rounded-pill shadow fw-bold" style="font-size: 0.85rem;">
                                    ฿<?php echo number_format($p['price']); ?>
                                </div>
                            </div>

                            <div class="p-3">
                                <h6 class="text-truncate fw-bold mb-1"><?php echo htmlspecialchars($p['title']); ?></h6>
                                
                                <div class="d-flex align-items-center mt-3 pt-2 border-top">
                                    <?php 
                                        // Path รูปโปรไฟล์ผู้ขาย (ถ้าไม่มีใช้รูป default)
                                        $seller_img = !empty($p['profile_image']) ? "uploads/profiles/" . $p['profile_image'] : "assets/img/default-user.png"; 
                                    ?>
                                    <img src="<?php echo $seller_img; ?>" class="rounded-circle border me-2" width="24" height="24" style="object-fit:cover;">
                                    
                                    <div class="small text-muted flex-grow-1 text-truncate" style="font-size: 0.75rem;">
                                        <?php echo htmlspecialchars($p['username']); ?>
                                        <?php if($p['is_verified']): ?>
                                            <i class="fa-solid fa-circle-check text-success ms-1"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <small class="text-secondary" style="font-size: 0.7rem;">
                                        <?php 
                                            $time_diff = time() - strtotime($p['created_at']);
                                            if($time_diff < 60) echo "เมื่อครู่";
                                            elseif($time_diff < 3600) echo floor($time_diff/60) . " นาที";
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
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border mt-4">
                <div class="mb-3 opacity-25">
                    <i class="fa-solid fa-box-open fa-5x text-secondary"></i>
                </div>
                <h4 class="text-dark fw-bold">ไม่พบสินค้า</h4>
                <p class="text-muted">ลองค้นหาด้วยคำอื่น หรือเลือกหมวดหมู่ใหม่ดูนะเพื่อน</p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 mt-2">กลับหน้าแรก</a>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5 py-4 border-top">
            <p class="text-muted m-0">&copy; <?php echo date('Y'); ?> NextHand. ตลาดซื้อขายสินค้ามือสองออนไลน์</p>
            <small class="text-secondary opacity-50">Created with ❤️ by ZenCool</small>
        </div>

    </div>

</body>
</html>