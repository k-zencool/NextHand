<?php
session_start();
require 'config/db.php'; // เรียก DB ไว้เผื่อใช้เช็ค Session ใน Navbar
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>NextHand - ตลาดมือสอง</title>
</head>
<body style="font-family: sans-serif; margin: 0; padding: 20px; background-color: #f0f0f0;">

    <?php include 'includes/navbar.php'; ?>

    <h1 style="text-align:center; color:#999; margin-top: 50px;">
        หน้าแรกว่างเปล่า... รอการเติมเต็ม
    </h1>

</body>
</html>