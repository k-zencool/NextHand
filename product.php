<?php
session_start();
require 'config/db.php';

// 1. ตรวจสอบ ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// ตัดส่วนอัปเดตยอดวิวออกไปแล้ว!

// 2. ดึงข้อมูลสินค้า + ข้อมูลคนขาย (ตัด views ออกจาก Select กันเหนียว)
$sql = "SELECT p.*, u.username, u.profile_image, u.phone, u.line_id, u.created_at as seller_joined 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.status = 'active'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$product = $stmt->fetch();

// ถ้าหาไม่เจอ ดีดกลับหน้าแรก
if (!$product) {
    header("Location: index.php");
    exit();
}

// จัดการรูปภาพ
$img_path = "uploads/" . $product['image'];
if (!file_exists($img_path) || empty($product['image'])) {
    $img_path = "https://via.placeholder.com/600x400?text=No+Image";
}

$seller_img = !empty($product['profile_image']) ? "uploads/" . $product['profile_image'] : "uploads/default.png";
if (!file_exists($seller_img)) {
    $seller_img = "https://via.placeholder.com/100?text=U";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - NextHand</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .product-detail-img {
            border-radius: 20px;
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        .seller-card {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            border: 1px solid #f1f2f6;
        }
        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: #0984e3;
            letter-spacing: -1px;
        }
        .badge-cat {
            background: #e1f0ff;
            color: #0984e3;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-main mt-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">หน้าแรก</a></li>
                <li class="breadcrumb-item"><a href="index.php?cat=<?php echo $product['category']; ?>" class="text-muted text-decoration-none"><?php echo ucfirst($product['category']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">รายละเอียดสินค้า</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="position-relative">
                    <img src="<?php echo $img_path; ?>" class="product-detail-img mb-3" alt="<?php echo htmlspecialchars($product['title']); ?>">
                </div>
                
                <div class="bg-white p-4 rounded-4 shadow-sm mt-3">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-align-left me-2 text-secondary"></i> รายละเอียดสินค้า</h5>
                    <p class="text-muted" style="white-space: pre-line; line-height: 1.8;">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    <hr class="my-4 opacity-25">
                    <div class="row text-muted small">
                        <div class="col-6 mb-2"><i class="fa-regular fa-clock me-2"></i> ลงขายเมื่อ: <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></div>
                        <div class="col-6"><i class="fa-solid fa-tag me-2"></i> หมวดหมู่: <?php echo $product['category']; ?></div>
                        <div class="col-6"><i class="fa-solid fa-box me-2"></i> สภาพ: 95% (Hardcode)</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white p-4 rounded-4 shadow-sm mb-4 position-sticky" style="top: 100px;">
                    <span class="badge-cat mb-2 d-inline-block">
                        <i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                    
                    <h1 class="fw-bold text-dark mb-2" style="font-size: 1.8rem; line-height: 1.3;">
                        <?php echo htmlspecialchars($product['title']); ?>
                    </h1>
                    
                    <div class="price-tag mb-4">
                        ฿<?php echo number_format($product['price']); ?>
                    </div>

                    <div class="d-grid gap-2 mb-4">
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['user_id']): ?>
                            <?php if(!empty($product['line_id'])): ?>
                            <a href="https://line.me/ti/p/~<?php echo htmlspecialchars($product['line_id']); ?>" target="_blank" class="btn btn-success rounded-pill py-3 fw-bold">
                                <i class="fa-brands fa-line fa-lg me-2"></i> ทักไลน์ไปเลย
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($product['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($product['phone']); ?>" class="btn btn-outline-dark rounded-pill py-2 fw-bold">
                                <i class="fa-solid fa-phone me-2"></i> โทรติดต่อ
                            </a>
                            <?php endif; ?>

                        <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['user_id']): ?>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning rounded-pill py-3 fw-bold text-white">
                                <i class="fa-solid fa-pen-to-square me-2"></i> แก้ไขสินค้า
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary rounded-pill py-3 fw-bold">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> ล็อกอินเพื่อติดต่อคนขาย
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="seller-card d-flex align-items-center gap-3">
                        <img src="<?php echo $seller_img; ?>" class="rounded-circle border" width="60" height="60" style="object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark">
                                <?php echo htmlspecialchars($product['username']); ?>
                                <?php if($product['is_verified'] ?? 0): ?>
                                    <i class="fa-solid fa-circle-check text-success small ms-1" title="ยืนยันตัวตนแล้ว"></i>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted d-block">สมาชิกเมื่อ <?php echo date('M Y', strtotime($product['seller_joined'])); ?></small>
                        </div>
                        <a href="profile.php?id=<?php echo $product['user_id']; ?>" class="btn btn-sm btn-light rounded-pill px-3">
                            ดูร้าน <i class="fa-solid fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                        </a>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded-3 border border-warning-subtle">
                        <div class="d-flex gap-3">
                            <i class="fa-solid fa-shield-cat text-warning fs-4"></i>
                            <div>
                                <h6 class="fw-bold text-dark m-0" style="font-size: 0.9rem;">ซื้อขายปลอดภัยไว้ก่อน</h6>
                                <p class="text-muted m-0 small" style="font-size: 0.8rem;">นัดรับสินค้าดีที่สุด ห้ามโอนเงินก่อนเห็นของเด็ดขาด!</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="simple-footer">
            <p class="m-0">&copy; <?php echo date('Y'); ?> NextHand. By ZenCool</p>
        </div>
    </div>

</body>
</html>