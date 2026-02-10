<?php
session_start();
require '../config/db.php'; // ถอยออกไปหา config

// 1. เช็คล็อกอิน
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login/"); 
    exit; 
}

$user_id = $_SESSION['user_id'];

// 2. เช็ค ID สินค้า
if (!isset($_GET['id'])) { 
    header("Location: ../my-products/"); 
    exit; 
}
$product_id = $_GET['id'];

// 3. ดึงข้อมูลสินค้า (เช็คความเป็นเจ้าของด้วย)
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch();

if (!$product) { 
    echo "ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไขรายการนี้"; 
    exit; 
}

// 🔥 Path สำหรับจัดการไฟล์รูปภาพ (ถอยออกไปหา uploads)
$target_dir = "../uploads/products/";

// 4. ลบรูป Gallery ทีละใบ
if (isset($_GET['delete_img'])) {
    $img_id = $_GET['delete_img'];
    $stmt_check_img = $pdo->prepare("SELECT image_name FROM product_images WHERE id = ? AND product_id = ?");
    $stmt_check_img->execute([$img_id, $product_id]);
    $img_del = $stmt_check_img->fetch();

    if ($img_del) {
        $file_to_delete = $target_dir . $img_del['image_name'];
        if (file_exists($file_to_delete)) @unlink($file_to_delete);
        
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$img_id]);
        header("Location: edit-product.php?id=" . $product_id); 
        exit;
    }
}

// 5. บันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $full_price = (float)$_POST['full_price'];
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $action = $_POST['action'];
    $status = ($action == 'publish') ? 'active' : 'draft';

    // A. อัปเดตข้อมูลพื้นฐาน
    $sql = "UPDATE products SET title=?, full_price=?, price=?, description=?, category=?, status=? WHERE id=? AND user_id=?";
    $stmt_update = $pdo->prepare($sql);
    $stmt_update->execute([$title, $full_price, $price, $description, $category, $status, $product_id, $user_id]);

    // B. อัปเดตรูปหลัก (ถ้ามีการเลือกไฟล์ใหม่)
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // ลบรูปเก่าก่อน
            if (file_exists($target_dir . $product['image'])) @unlink($target_dir . $product['image']);
            
            $new_name = uniqid('main_') . "." . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name)) {
                $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$new_name, $product_id]);
            }
        }
    }

    // C. เพิ่มรูป Gallery (ถ้ามีการเลือกเพิ่ม)
    if (!empty($_FILES['gallery']['name'][0])) {
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
        $stmt_count->execute([$product_id]);
        $current_count = $stmt_count->fetchColumn();

        foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {
            if ($current_count >= 4) break; // จำกัดรวมไม่เกิน 4 รูป
            
            $g_name = $_FILES['gallery']['name'][$key];
            $g_ext = strtolower(pathinfo($g_name, PATHINFO_EXTENSION));

            if (!empty($g_name) && in_array($g_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $g_new_name = uniqid('gallery_') . "_" . $key . "." . $g_ext;
                if (move_uploaded_file($tmp_name, $target_dir . $g_new_name)) {
                    $pdo->prepare("INSERT INTO product_images (product_id, image_name) VALUES (?, ?)")
                        ->execute([$product_id, $g_new_name]);
                    $current_count++;
                }
            }
        }
    }
    header("Location: ../my-products/index.php?msg=updated"); 
    exit;
}

// ดึงรูป Gallery มาแสดงผล
$stmt_imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt_imgs->execute([$product_id]);
$gallery_images = $stmt_imgs->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า - NextHand</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container-main { padding-top: 30px; padding-bottom: 50px; }
        .preview-img { width: 100%; height: 250px; object-fit: cover; border-radius: 15px; }
        .gallery-item { width: 100%; height: 100px; object-fit: cover; border-radius: 10px; }
        .border-dashed { border-style: dashed !important; }
    </style>
</head>
<body>

    <?php include '../includes/navbar.php'; ?> <div class="container container-main" style="max-width: 950px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-pen-to-square fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0 text-dark">แก้ไขข้อมูลสินค้า</h4>
                        <p class="text-muted small m-0">อัปเดตรายละเอียดสินค้าให้ถูกต้อง</p>
                    </div>
                </div>
                <a href="../my-products/" class="btn btn-light rounded-pill px-3 fw-bold">ยกเลิก</a>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">ชื่อสินค้า</label>
                            <input type="text" name="title" class="form-control form-control-lg rounded-3" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">หมวดหมู่</label>
                            <select name="category" class="form-select rounded-3" required>
                                <?php 
                                $cats = [
                                    'mobile'=>'มือถือ/แท็บเล็ต', 'vehicles'=>'ยานยนต์', 'fashion'=>'เสื้อผ้า/แฟชั่น', 
                                    'electronics'=>'เครื่องใช้ไฟฟ้า', 'camera'=>'กล้อง', 'computer'=>'คอมพิวเตอร์', 
                                    'amulet'=>'พระเครื่อง', 'pets'=>'สัตว์เลี้ยง', 'shoes'=>'รองเท้า', 
                                    'game'=>'เกม/ของเล่น', 'sports'=>'กีฬา', 'home'=>'แต่งบ้าน', 'others'=>'อื่นๆ'
                                ];
                                foreach($cats as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ($product['category'] == $val) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ราคาเต็ม (บาท)</label>
                                    <input type="number" name="full_price" class="form-control" value="<?php echo $product['full_price']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">ราคาขายจริง <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control fw-bold text-success" value="<?php echo $product['price']; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">รายละเอียดสินค้า</label>
                            <textarea name="description" class="form-control rounded-3" rows="7" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card bg-white border border-light-subtle rounded-4 p-3 mb-4">
                            <label class="form-label fw-bold small text-secondary mb-3">รูปภาพหลัก (Cover)</label>
                            <img src="../uploads/products/<?php echo $product['image']; ?>" id="main-preview" class="preview-img mb-3 border">
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" onchange="previewImg(this, 'main-preview')">
                        </div>

                        <div class="card bg-light border-0 rounded-4 p-3">
                            <label class="form-label fw-bold small text-secondary d-flex justify-content-between">
                                <span>รูปเพิ่มเติม (สูงสุด 4)</span>
                                <span class="badge bg-secondary rounded-pill"><?php echo count($gallery_images); ?>/4</span>
                            </label>

                            <div class="row g-2 mb-3">
                                <?php foreach($gallery_images as $img): ?>
                                    <div class="col-6 position-relative">
                                        <img src="../uploads/products/<?php echo $img['image_name']; ?>" class="gallery-item border shadow-sm">
                                        <a href="?id=<?php echo $product_id; ?>&delete_img=<?php echo $img['id']; ?>" 
                                           class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle m-1"
                                           onclick="return confirm('ลบรูปภาพนี้?')">
                                            <i class="fa-solid fa-times"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if(count($gallery_images) < 4): ?>
                                <div class="p-3 border-2 border-secondary border-opacity-25 rounded-3 bg-white text-center border-dashed position-relative">
                                    <i class="fa-solid fa-plus text-muted mb-1"></i>
                                    <p class="small text-muted m-0">คลิกเพื่อเพิ่มรูป</p>
                                    <input type="file" name="gallery[]" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept="image/*" multiple>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-top d-flex justify-content-end gap-2">
                    <?php if($product['status'] == 'draft'): ?>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">บันทึกร่างต่อ</button>
                        <button type="submit" name="action" value="publish" class="btn btn-primary rounded-pill px-5 fw-bold shadow">ลงขายทันที!</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="publish" class="btn btn-primary rounded-pill px-5 fw-bold shadow">บันทึกการแก้ไข</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImg(input, targetId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById(targetId).src = e.target.result;
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>