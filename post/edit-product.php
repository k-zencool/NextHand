<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login/"); exit; }
$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) { header("Location: my-products/"); exit; }
$product_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch();
if (!$product) { echo "ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไข"; exit; }

// 🔥 โฟลเดอร์รูป (อยู่ใน root ไม่ต้องใช้ ../)
$target_dir = "uploads/products/";

// 4. ลบรูป Gallery
if (isset($_GET['delete_img'])) {
    $img_id = $_GET['delete_img'];
    $stmt_check_img = $pdo->prepare("SELECT image_name FROM product_images WHERE id = ? AND product_id = ?");
    $stmt_check_img->execute([$img_id, $product_id]);
    $img_del = $stmt_check_img->fetch();

    if ($img_del) {
        @unlink($target_dir . $img_del['image_name']); // ลบไฟล์จากโฟลเดอร์ products
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$img_id]);
        header("Location: edit-product.php?id=" . $product_id); exit;
    }
}

// 5. บันทึกแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $full_price = $_POST['full_price'];
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $action = $_POST['action'];
    $status = ($action == 'publish') ? 'active' : 'draft';

    // A. อัปเดตข้อมูล
    $sql = "UPDATE products SET title=?, full_price=?, price=?, description=?, category=?, status=? WHERE id=?";
    $stmt_update = $pdo->prepare($sql);
    $stmt_update->execute([$title, $full_price, $price, $description, $category, $status, $product_id]);

    // B. รูปหลัก
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            @unlink($target_dir . $product['image']); // ลบรูปเก่า
            $new_name = uniqid() . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name);
            $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$new_name, $product_id]);
        }
    }

    // C. รูป Gallery
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
    $stmt_count->execute([$product_id]);
    $current_count = $stmt_count->fetchColumn();

    if (isset($_FILES['gallery'])) {
        $files = $_FILES['gallery'];
        $count_upload = count($files['name']);
        
        for ($i = 0; $i < $count_upload; $i++) {
            if ($current_count >= 4) break;
            $g_name = $files['name'][$i];
            $g_tmp = $files['tmp_name'][$i];
            $g_ext = strtolower(pathinfo($g_name, PATHINFO_EXTENSION));

            if (!empty($g_name) && in_array($g_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $g_new_name = uniqid() . "_extra_" . $i . "." . $g_ext;
                move_uploaded_file($g_tmp, $target_dir . $g_new_name);
                $pdo->prepare("INSERT INTO product_images (product_id, image_name) VALUES (?, ?)")
                    ->execute([$product_id, $g_new_name]);
                $current_count++;
            }
        }
    }
    header("Location: my-products/index.php?msg=updated"); exit;
}

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
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-main" style="max-width: 900px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="fa-solid fa-pen-to-square fa-lg"></i></div>
                    <div><h4 class="fw-bold m-0">แก้ไขสินค้า</h4><p class="text-muted small m-0">อัปเดตข้อมูลสินค้าของคุณ</p></div>
                </div>
                <a href="my-products/index.php?delete_id=<?php echo $product_id; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('ยืนยันที่จะลบสินค้านี้?')"><i class="fa-solid fa-trash me-1"></i> ลบทิ้ง</a>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อสินค้า</label>
                            <input type="text" name="title" class="form-control rounded-3 py-2" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">หมวดหมู่</label>
                            <select name="category" class="form-select rounded-3 py-2" required>
                                <?php 
                                $cats = ['mobile'=>'มือถือ/แท็บเล็ต', 'vehicles'=>'ยานยนต์', 'fashion'=>'เสื้อผ้า/แฟชั่น', 'electronics'=>'เครื่องใช้ไฟฟ้า', 'camera'=>'กล้อง', 'computer'=>'คอมพิวเตอร์', 'amulet'=>'พระเครื่อง', 'pets'=>'สัตว์เลี้ยง', 'shoes'=>'รองเท้า', 'game'=>'เกม/ของเล่น', 'sports'=>'กีฬา', 'home'=>'แต่งบ้าน', 'others'=>'อื่นๆ'];
                                foreach($cats as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php if($product['category'] == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small text-muted">ราคาเต็ม</label><div class="input-group"><span class="input-group-text bg-white border-end-0">฿</span><input type="number" name="full_price" class="form-control border-start-0" value="<?php echo $product['full_price']; ?>"></div></div>
                                <div class="col-md-6"><label class="form-label small fw-bold text-dark">ราคาขายจริง</label><div class="input-group"><span class="input-group-text bg-white border-end-0 text-success fw-bold">฿</span><input type="number" name="price" class="form-control border-start-0 fw-bold text-success" value="<?php echo $product['price']; ?>" required></div></div>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label fw-bold">รายละเอียดสินค้า</label><textarea name="description" class="form-control rounded-3" rows="6" required><?php echo htmlspecialchars($product['description']); ?></textarea></div>
                    </div>

                    <div class="col-lg-5">
                        <div class="mb-4">
                            <label class="form-label fw-bold">รูปหลัก</label>
                            <div class="position-relative mb-2">
                                <img src="uploads/products/<?php echo $product['image']; ?>" class="w-100 rounded-4 object-fit-cover shadow-sm" style="height: 220px;">
                                <div class="position-absolute bottom-0 start-0 w-100 p-2 bg-dark bg-opacity-50 text-white text-center rounded-bottom-4 small">รูปปัจจุบัน</div>
                            </div>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex justify-content-between"><span>รูปเพิ่มเติม</span><span class="badge bg-secondary rounded-pill"><?php echo count($gallery_images); ?>/4</span></label>
                            <?php if(count($gallery_images) > 0): ?>
                                <div class="d-grid gap-2 mb-3" style="grid-template-columns: repeat(2, 1fr);">
                                    <?php foreach($gallery_images as $img): ?>
                                        <div class="position-relative" style="height: 100px;">
                                            <img src="uploads/products/<?php echo $img['image_name']; ?>" class="w-100 h-100 object-fit-cover rounded-3 border">
                                            <a href="?id=<?php echo $product_id; ?>&delete_img=<?php echo $img['id']; ?>" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 d-flex justify-content-center align-items-center rounded-circle" style="width: 22px; height: 22px; transform: translate(30%, -30%);" onclick="return confirm('ลบรูปนี้?')"><i class="fa-solid fa-times" style="font-size: 12px;"></i></a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if(count($gallery_images) < 4): ?>
                                <div class="text-center p-3 border border-2 border-secondary border-opacity-25 rounded-4 bg-light position-relative" style="border-style: dashed !important;"><i class="fa-solid fa-plus fa-lg text-secondary mb-2 opacity-50"></i><p class="small text-muted m-0">เพิ่มรูปอีก</p><input type="file" name="gallery[]" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept="image/*" multiple></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="my-products/" class="btn btn-light rounded-pill px-4 fw-bold">ยกเลิก</a>
                    <?php if($product['status'] == 'draft'): ?>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">บันทึกร่างต่อ</button>
                        <button type="submit" name="action" value="publish" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">ลงขายเลย!</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="publish" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">บันทึกการแก้ไข</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>