<?php
session_start();
require '../config/db.php';

// 1. เช็คล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. จัดการคำสั่งลบ (Delete)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // เช็คความเป็นเจ้าของ
    $stmt_check = $pdo->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$delete_id, $user_id]);
    $product = $stmt_check->fetch();

    if ($product) {
        // ลบรูปหลัก
        $file_path = "../uploads/" . $product['image'];
        if (file_exists($file_path)) @unlink($file_path);

        // ลบรูป Gallery (ถ้ามี)
        $stmt_imgs = $pdo->prepare("SELECT image_name FROM product_images WHERE product_id = ?");
        $stmt_imgs->execute([$delete_id]);
        while ($img = $stmt_imgs->fetch()) {
            $g_path = "../uploads/" . $img['image_name'];
            if (file_exists($g_path)) @unlink($g_path);
        }

        // ลบข้อมูลใน DB (Constraint Cascade จะลบใน product_images ให้อัตโนมัติ ถ้าตั้งไว้)
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->execute([$delete_id]);

        // คืนโควตา (Optional)
        // $pdo->prepare("UPDATE users SET item_quota = item_quota + 1 WHERE id = ?")->execute([$user_id]);
        // $_SESSION['item_quota'] += 1;

        header("Location: index.php?msg=deleted");
        exit;
    }
}

// 3. จัดการคำสั่ง "ขายแล้ว" (Mark as Sold)
if (isset($_GET['sold_id'])) {
    $sold_id = $_GET['sold_id'];
    $stmt_sold = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND user_id = ?");
    $stmt_sold->execute([$sold_id, $user_id]);
    header("Location: index.php?msg=sold");
    exit;
}

// 4. ดึงสินค้าของฉันทั้งหมด
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="container-main">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary"></i> สินค้าของฉัน</h3>
                <p class="text-muted small m-0">จัดการรายการสินค้าที่คุณลงขายไว้</p>
            </div>
            <a href="../post/" class="btn btn-primary rounded-pill fw-bold">
                <i class="fa-solid fa-plus"></i> ลงขายเพิ่ม
            </a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success rounded-pill text-center mb-4"><i class="fa-solid fa-check-circle"></i> ลบสินค้าเรียบร้อยแล้ว</div>
            <?php elseif($_GET['msg'] == 'sold'): ?>
                <div class="alert alert-success rounded-pill text-center mb-4"><i class="fa-solid fa-handshake"></i> ปิดการขายเรียบร้อย! ยินดีด้วยครับ 🎉</div>
            <?php elseif($_GET['msg'] == 'draft_saved'): ?>
                <div class="alert alert-warning rounded-pill text-center mb-4 text-dark"><i class="fa-solid fa-pen-ruler"></i> บันทึกฉบับร่างแล้ว (อย่าลืมกลับมาลงขายนะ!)</div>
            <?php elseif($_GET['msg'] == 'success'): ?>
                <div class="alert alert-success rounded-pill text-center mb-4"><i class="fa-solid fa-check-circle"></i> ลงขายสำเร็จ! สินค้าออนไลน์แล้ว</div>
            <?php elseif($_GET['msg'] == 'updated'): ?>
                <div class="alert alert-success rounded-pill text-center mb-4"><i class="fa-solid fa-save"></i> อัปเดตข้อมูลสินค้าแล้ว</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <?php if(count($my_products) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 ps-4">สินค้า</th>
                                <th class="py-3">ราคา</th>
                                <th class="py-3">สถานะ</th>
                                <th class="py-3 text-end pe-4">ตัวเลือก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_products as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                                $img_path = "../uploads/" . $p['image'];
                                                if(!file_exists($img_path)) $img_path = "https://via.placeholder.com/50";
                                            ?>
                                            <div class="position-relative">
                                                <img src="<?php echo $img_path; ?>" class="rounded-3 border me-3 object-fit-cover" width="60" height="60">
                                                <?php if($p['status'] == 'sold'): ?>
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 rounded-3 d-flex align-items-center justify-content-center text-white small fw-bold">SOLD</div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($p['title']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fa-regular fa-clock me-1"></i>
                                                    <?php echo date('d/m/y H:i', strtotime($p['created_at'])); ?>
                                                </small>
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
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 border border-success border-opacity-25">
                                                <i class="fa-solid fa-circle-check me-1"></i> กำลังขาย
                                            </span>
                                        <?php elseif($p['status'] == 'sold'): ?>
                                            <span class="badge bg-secondary rounded-pill px-3">
                                                <i class="fa-solid fa-handshake me-1"></i> ขายแล้ว
                                            </span>
                                        <?php elseif($p['status'] == 'draft'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 border border-warning">
                                                <i class="fa-solid fa-pen-ruler me-1"></i> ฉบับร่าง
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3">
                                                <i class="fa-solid fa-ban me-1"></i> โดนแบน
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end pe-4">
                                        
                                        <?php if($p['status'] == 'draft'): ?>
                                            <a href="../edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark px-3 shadow-sm">
                                                <i class="fa-solid fa-pen me-1"></i> แก้ไข/ลงขาย
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-light btn-sm rounded-circle text-danger ms-1" title="ลบทิ้ง">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        <?php elseif($p['status'] == 'active'): ?>
                                            <a href="../product.php?id=<?php echo $p['id']; ?>" class="btn btn-light btn-sm rounded-circle text-secondary border" title="ดูสินค้า">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="../edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-light btn-sm rounded-circle text-primary border" title="แก้ไข">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a href="?sold_id=<?php echo $p['id']; ?>" class="btn btn-light btn-sm rounded-circle text-success border" title="กดปิดการขาย" onclick="return confirm('ยืนยันว่าขายสินค้านี้ได้แล้ว?')">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-light btn-sm rounded-circle text-danger border ms-1" title="ลบสินค้า">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        <?php else: ?>
                                            <a href="../product.php?id=<?php echo $p['id']; ?>" class="btn btn-light btn-sm rounded-circle text-secondary" title="ดูย้อนหลัง">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-light btn-sm rounded-circle text-danger" title="ลบประวัติ">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-25">
                        <i class="fa-solid fa-box-open fa-4x"></i>
                    </div>
                    <h5 class="text-muted fw-bold">คุณยังไม่มีสินค้าที่ลงขาย</h5>
                    <p class="text-muted small mb-4">เอาของไม่ได้ใช้มาเปลี่ยนเป็นเงินกันเถอะ!</p>
                    <a href="../post/" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-plus me-2"></i> ลงขายชิ้นแรกเลย!
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'แน่ใจนะว่าจะลบ?',
                text: "ลบแล้วกู้คืนไม่ได้นะ! (รูปภาพจะถูกลบออกจาก Server ด้วย)",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?delete_id=" + id;
                }
            })
        }
    </script>

</body>
</html>