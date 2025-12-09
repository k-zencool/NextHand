<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">

<nav class="navbar navbar-expand-lg floating-nav sticky-top">
    <div class="container-fluid">
        
        <a class="navbar-brand brand-logo" href="/index.php">
            <div class="brand-icon"><i class="fa-solid fa-handshake"></i></div>
            NextHand
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <i class="fa-solid fa-bars-staggered"></i> 
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            
            <div class="mx-auto search-group">
                <form action="/index.php" method="GET">
                    <input type="text" class="search-input" name="q" placeholder="ค้นหาสินค้า...">
                    <button type="submit" class="search-icon-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>

            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link" href="/index.php">หน้าแรก</a>
                </li>

                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="#" style="font-size: 1.1rem;">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width: 10px; height: 10px;">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </a>
                </li>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a href="/post/" class="btn btn-pill btn-register-white">
                            <i class="fa-solid fa-plus"></i> ลงขาย
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle p-0 ms-2" href="#" data-bs-toggle="dropdown">
                            <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg mt-3 border-0" style="border-radius: 15px; overflow: hidden;">
                            <li class="px-3 py-2 text-muted" style="font-size: 0.85rem;">
                                สวัสดี, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="/profile/">
                                    <i class="fa-solid fa-id-card me-2 text-primary"></i> ข้อมูลส่วนตัว
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/topup/">
                                    <i class="fa-solid fa-wallet me-2 text-warning"></i> เติมเงิน / โควตา
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fa-solid fa-box-open me-2 text-success"></i> สินค้าของฉัน
                                </a>
                            </li>

                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/admin/">
                                        <i class="fa-solid fa-shield-halved me-2"></i> ระบบหลังบ้าน
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="/login/" class="btn btn-pill btn-login-outline">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a href="/register/" class="btn btn-pill btn-register-white">สมัครสมาชิก</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>