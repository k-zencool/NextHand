<?php
session_start();
// ล้าง Session
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #ecf0f3; }
    </style>
</head>
<body>

<script>
    // เด้ง Alert ขึ้นมาทันทีที่เข้าหน้านี้
    Swal.fire({
        title: 'กำลังออกจากระบบ',
        html: 'ขอบคุณที่ใช้บริการครับ ไว้เจอกันใหม่นะ! 👋',
        timer: 1500, // รอ 1.5 วินาที
        timerProgressBar: true, // มีหลอดวิ่งๆ เหมือนดาวน์โหลด
        didOpen: () => {
            Swal.showLoading()
        },
        willClose: () => {
            // พอเวลาหมด ให้ดีดไปหน้า Login
            window.location.href = "/login/?logout=1";
        }
    });
</script>

</body>
</html>
