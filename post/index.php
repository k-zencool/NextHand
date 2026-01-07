<?php
session_start();
require '../config/db.php';

// 1. เช็คล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Submit Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action']; 
    // ถ้ากดปุ่ม "ลงขายทันที" สถานะ = active, ถ้ากด "บันทึกร่าง" สถานะ = draft
    $status = ($action == 'publish') ? 'active' : 'draft';

    $title = trim($_POST['title']);
    
    // 🔥 แก้จุดตาย! ถ้าไม่กรอกราคาเต็ม ให้ค่าเป็น 0 (ป้องกัน Error SQL)
    $full_price = !empty($_POST['full_price']) ? $_POST['full_price'] : 0;
    
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $category = $_POST['category'];

    // 🔥 กำหนดโฟลเดอร์ปลายทาง (เก็บแยกใน products)
    $target_dir = "../uploads/products/";
    // ถ้าไม่มีโฟลเดอร์ ให้สร้างใหม่เอง
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // -- A. จัดการรูปหลัก (Cover) --
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = uniqid() . "." . $ext;
            
            // อัปโหลดลงโฟลเดอร์ products
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name)) {
                
                // บันทึกข้อมูลสินค้า
                $sql = "INSERT INTO products (user_id, title, price, full_price, description, image, category, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$user_id, $title, $price, $full_price, $description, $new_name, $category, $status])) {
                    $product_id = $pdo->lastInsertId();

                    // -- B. จัดการรูปเพิ่มเติม (Gallery) --
                    if (isset($_FILES['gallery'])) {
                        $files = $_FILES['gallery'];
                        $count = count($files['name']);
                        
                        for ($i = 0; $i < $count; $i++) {
                            // กันเหนียว: เอาแค่ 4 รูปพอ
                            if ($i >= 4) break; 

                            $g_name = $files['name'][$i];
                            $g_tmp = $files['tmp_name'][$i];
                            $g_ext = strtolower(pathinfo($g_name, PATHINFO_EXTENSION));

                            if (!empty($g_name) && in_array($g_ext, $allowed)) {
                                $g_new_name = uniqid() . "_extra_" . $i . "." . $g_ext;
                                // อัปโหลดลงโฟลเดอร์ products
                                move_uploaded_file($g_tmp, $target_dir . $g_new_name);
                                
                                // Insert ลงตารางรูปภาพ
                                $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_name) VALUES (?, ?)");
                                $stmt_img->execute([$product_id, $g_new_name]);
                            }
                        }
                    }

                    // แยกข้อความแจ้งเตือน (ร่าง หรือ ลงขาย)
                    $msg = ($status == 'draft') ? 'draft_saved' : 'success';
                    header("Location: ../my-products/?msg=" . $msg);
                    exit;
                }
            } else { 
                $error = "อัปโหลดรูปหลักไม่ผ่าน (เช็คสิทธิ์โฟลเดอร์ uploads/products/)"; 
            }
        } else { 
            $error = "รูปหลักต้องเป็นไฟล์ภาพ (JPG, PNG) เท่านั้น"; 
        }
    } else { 
        $error = "กรุณาใส่รูปหลักอย่างน้อย 1 รูป"; 
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงขายสินค้า - NextHand</title>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="container-main" style="max-width: 900px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-store fa-lg"></i>
                </div>
                <div>
                    <h4 class="fw-bold m-0">ลงขายสินค้า</h4>
                    <p class="text-muted small m-0">กรอกข้อมูลให้ครบถ้วนเพื่อเพิ่มโอกาสขาย</p>
                </div>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger rounded-pill text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="postForm">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3 py-2" required placeholder="เช่น iPhone 13, เสื้อยืดมือสอง">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select rounded-3 py-2" required>
                                <option value="" selected disabled>เลือกหมวด...</option>
                                <option value="mobile">มือถือ/แท็บเล็ต</option>
                                <option value="vehicles">ยานยนต์</option>
                                <option value="fashion">เสื้อผ้า/แฟชั่น</option>
                                <option value="electronics">เครื่องใช้ไฟฟ้า</option>
                                <option value="camera">กล้อง</option>
                                <option value="computer">คอมพิวเตอร์</option>
                                <option value="amulet">พระเครื่อง</option>
                                <option value="pets">สัตว์เลี้ยง</option>
                                <option value="shoes">รองเท้า</option>
                                <option value="game">เกม/ของเล่น</option>
                                <option value="sports">กีฬา</option>
                                <option value="home">แต่งบ้าน</option>
                                <option value="others">อื่นๆ</option>
                            </select>
                        </div>

                        <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                            <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-tags"></i> ตั้งราคาขาย</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ราคาเต็ม (ไม่บังคับ)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">฿</span>
                                        <input type="number" id="full_price" name="full_price" class="form-control border-start-0" placeholder="0" oninput="calculateDiscount()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">ราคาขาย <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-success fw-bold">฿</span>
                                        <input type="number" id="price" name="price" class="form-control border-start-0 fw-bold text-success" placeholder="0" required oninput="calculateDiscount()">
                                    </div>
                                </div>
                            </div>
                            <div id="discount-result" class="mt-3 p-2 bg-white rounded-3 border d-none">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted">ส่วนลด: <span id="show-percent" class="fw-bold text-danger">0%</span></span>
                                    <span class="badge bg-success rounded-pill px-3">ประหยัด <span id="show-saved">0</span> บ.</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">รายละเอียดสินค้า</label>
                            <textarea name="description" class="form-control rounded-3" rows="6" placeholder="บอกรายละเอียดสินค้า ตำหนิ สภาพกี่ %..." required></textarea>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">รูปหลัก (Cover) <span class="text-danger">*</span></label>
                            <div class="text-center p-3 border border-2 border-primary border-opacity-25 rounded-4 bg-white position-relative" style="border-style: dashed !important; height: 220px;">
                                <div id="main-upload-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <i class="fa-solid fa-image fa-3x text-primary mb-2 opacity-50"></i>
                                    <p class="small text-muted m-0">คลิกเพื่อเลือกรูปปก</p>
                                </div>
                                <img id="preview-main-img" src="" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover rounded-4 d-none">
                                <input type="file" name="image" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept="image/*" onchange="previewMain(this)" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex justify-content-between">
                                <span>รูปเพิ่มเติม</span>
                                <span class="badge bg-secondary rounded-pill" id="img-count">0/4</span>
                            </label>
                            
                            <div class="text-center p-3 border border-2 border-secondary border-opacity-25 rounded-4 bg-light position-relative mb-3" style="border-style: dashed !important;">
                                <i class="fa-solid fa-plus fa-2x text-secondary mb-2 opacity-50"></i>
                                <p class="small text-muted m-0">คลิกเพื่อเพิ่มรูป (สะสมได้)</p>
                                <input type="file" id="gallery-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept="image/*" multiple>
                            </div>

                            <input type="file" name="gallery[]" id="real-gallery-input" class="d-none" multiple>

                            <div id="gallery-preview" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;"></div>
                        </div>

                    </div>
                </div>

                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="../my-products/" class="btn btn-light rounded-pill px-4 fw-bold">ยกเลิก</a>
                    
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกร่าง
                    </button>
                    
                    <button type="submit" name="action" value="publish" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fa-solid fa-check-circle me-2"></i> ลงขายทันที
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        // 1. ระบบสะสมรูปภาพ (DataTransfer)
        const dt = new DataTransfer();
        const galleryInput = document.getElementById('gallery-input');
        const realInput = document.getElementById('real-gallery-input');
        const previewArea = document.getElementById('gallery-preview');
        const countBadge = document.getElementById('img-count');

        galleryInput.addEventListener('change', function(){
            for(let i = 0; i < this.files.length; i++){
                let file = this.files[i];
                // เช็คจำนวนรูปสูงสุด
                if(dt.items.length >= 4){
                    alert("เพิ่มได้สูงสุดแค่ 4 รูปครับเพื่อน!");
                    break; 
                }
                dt.items.add(file);
            }
            // อัปเดต Input ของจริง
            realInput.files = dt.files;
            // รีเซ็ต Input หลอก (เพื่อให้เลือกซ้ำได้)
            this.value = '';
            // วาดรูปใหม่
            renderPreview();
        });

        function renderPreview(){
            previewArea.innerHTML = '';
            countBadge.innerText = dt.items.length + "/4";

            for(let i = 0; i < dt.files.length; i++){
                let file = dt.files[i];
                let reader = new FileReader();
                
                reader.onload = function(e){
                    let html = `
                        <div class="position-relative" style="width: 100%; height: 100px;">
                            <img src="${e.target.result}" class="w-100 h-100 object-fit-cover rounded-3 border">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 d-flex justify-content-center align-items-center rounded-circle" 
                                style="width: 20px; height: 20px; transform: translate(30%, -30%);"
                                onclick="removeImage(${i})">
                                <i class="fa-solid fa-times" style="font-size: 10px;"></i>
                            </button>
                        </div>
                    `;
                    previewArea.insertAdjacentHTML('beforeend', html);
                }
                reader.readAsDataURL(file);
            }
        }

        // ฟังก์ชันลบรูปออกจากถังพัก
        window.removeImage = function(index){
            dt.items.remove(index);
            realInput.files = dt.files;
            renderPreview();
        }

        // 2. ระบบคำนวณส่วนลด
        function calculateDiscount() {
            let fullPrice = parseFloat(document.getElementById('full_price').value) || 0;
            let sellingPrice = parseFloat(document.getElementById('price').value) || 0;
            let resultBox = document.getElementById('discount-result');

            if (fullPrice > sellingPrice && sellingPrice > 0) {
                let saved = fullPrice - sellingPrice;
                let percent = (saved / fullPrice) * 100;
                document.getElementById('show-percent').innerText = percent.toFixed(0) + "% OFF";
                document.getElementById('show-saved').innerText = saved.toLocaleString();
                resultBox.classList.remove('d-none');
            } else {
                resultBox.classList.add('d-none');
            }
        }

        // 3. ระบบ Preview รูปหลัก
        function previewMain(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('preview-main-img');
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                    document.getElementById('main-upload-placeholder').classList.add('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>