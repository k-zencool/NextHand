<?php
session_start();
require '../config/db.php'; // ดึง Config จากนอกโฟลเดอร์ my-products

// 1. เช็คล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$target_dir = "../uploads/products/"; // Path สำหรับลบไฟล์จริงใน Server

// 2. จัดการคำสั่งลบสินค้า (Delete)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // เช็คความเป็นเจ้าของก่อนลบ
    $stmt_check = $pdo->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$delete_id, $user_id]);
    $product = $stmt_check->fetch();

    if ($product) {
        // ลบรูปภาพหลักออกจากโฟลเดอร์
        if (!empty($product['image']) && file_exists($target_dir . $product['image'])) {
            @unlink($target_dir . $product['image']);
        }

        // ลบรูป Gallery ทั้งหมดที่เกี่ยวข้อง
        $stmt_imgs = $pdo->prepare("SELECT image_name FROM product_images WHERE product_id = ?");
        $stmt_imgs->execute([$delete_id]);
        while ($img = $stmt_imgs->fetch()) {
            if (file_exists($target_dir . $img['image_name'])) {
                @unlink($target_dir . $img['image_name']);
            }
        }

        // ลบข้อมูลในฐานะข้อมูล (SQL)
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->execute([$delete_id]);

        header("Location: index.php?msg=deleted");
        exit;
    }
}

// 3. จัดการคำสั่งปิดการขาย (Mark as Sold)
if (isset($_GET['sold_id'])) {
    $sold_id = $_GET['sold_id'];
    $stmt_sold = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND user_id = ?");
    $stmt_sold->execute([$sold_id, $user_id]);
    header("Location: index.php?msg=sold");
    exit;
}

// 4. ดึงสินค้าทั้งหมดของผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้าของฉัน - NextHand</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .object-fit-cover { object-fit: cover; }
        .container-main { padding-top: 30px; padding-bottom: 50px; }
        .product-img-wrapper { width: 60px; height: 60px; overflow: hidden; border-radius: 8px; }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/navbar.php'; ?>

    <div class="container container-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>สินค้าของฉัน</h3>
                <p class="text-muted small m-0">จัดการและตรวจสอบสถานะสินค้าที่คุณลงขาย</p>
            </div>
            <a href="../post/" class="btn btn-primary rounded-pill fw-bold shadow-sm px-4">
                <i class="fa-solid fa-plus me-1"></i> ลงขายเพิ่ม
            </a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm text-center mb-4 py-3">
                <?php 
                    $msgs = [
                        'deleted' => '<i class="fa-solid fa-trash-can me-2"></i> ลบสินค้าออกจากระบบเรียบร้อยแล้ว',
                        'sold' => '<i class="fa-solid fa-check-double me-2"></i> ปิดการขายสำเร็จ! ยินดีด้วยครับ 🎉',
                        'updated' => '<i class="fa-solid fa-save me-2"></i> อัปเดตข้อมูลสินค้าเรียบร้อย',
                        'success' => '<i class="fa-solid fa-rocket me-2"></i> ลงขายสินค้าสำเร็จ! ออนไลน์แล้วตอนนี้'
                    ];
                    echo $msgs[$_GET['msg']] ?? 'ดำเนินการสำเร็จ';
                ?>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <?php if(count($my_products) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4 border-0">ข้อมูลสินค้า</th>
                                <th class="py-3 border-0">ราคาขาย</th>
                                <th class="py-3 border-0">สถานะ</th>
                                <th class="py-3 text-end pe-4 border-0">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_products as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative me-3">
                                                <?php 
                                                    // Path รูปภาพสำหรับแสดงผล (ถอยออกไปหา uploads)
                                                    $img_display = "../uploads/products/" . $p['image'];
                                                    if(empty($p['image']) || !file_exists($img_display)) $img_display = "../assets/img/no-image.png";
                                                ?>
                                                <div class="product-img-wrapper border shadow-sm">
                                                    <img src="<?php echo $img_display; ?>" class="w-100 h-100 object-fit-cover">
                                                </div>
                                                <?php if($p['status'] == 'sold'): ?>
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 rounded-2 d-flex align-items-center justify-content-center text-white" style="font-size: 9px; font-weight: bold;">SOLD</div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($p['title']); ?></h6>
                                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?php echo date('d/m/Y', strtotime($p['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">฿<?php echo number_format($p['price']); ?></div>
                                        <?php if($p['full_price'] > $p['price']): ?>
                                            <small class="text-muted text-decoration-line-through">฿<?php echo number_format($p['full_price']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($p['status'] == 'active'): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> กำลังขาย</span>
                                        <?php elseif($p['status'] == 'sold'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-handshake me-1"></i> ขายแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-pen-ruler me-1"></i> ฉบับร่าง</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="../post/edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary btn-sm rounded-start-pill px-3" title="แก้ไข">
                                                <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                            </a>
                                            
                                            <?php if($p['status'] == 'active'): ?>
                                                <a href="?sold_id=<?php echo $p['id']; ?>" class="btn btn-outline-success btn-sm px-3" onclick="return confirm('ยืนยันการปิดการขายสินค้านี้?')" title="ขายแล้ว">
                                                    <i class="fa-solid fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-outline-danger btn-sm rounded-end-pill px-3" title="ลบ">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-box-open fa-4x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted fw-bold">ยังไม่มีสินค้าที่ลงขาย</h5>
                    <p class="text-muted mb-4">ลองลงขายสินค้าชิ้นแรกของคุณเพื่อเริ่มทำกำไร!</p>
                    <a href="../post/" class="btn btn-primary rounded-pill px-4">ลงขายสินค้าเลย</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "รูปภาพและข้อมูลสินค้าจะถูกลบถาวร ไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบเลย!',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    confirmButton: 'rounded-pill',
                    cancelButton: 'rounded-pill'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?delete_id=" + id;
                }
            })
        }
    </script>
</body>
</html>