<?php
// config/db.php

// ถ้าใช้ Docker ให้ใส่ host เป็น 'db'
// ถ้าใช้ XAMPP ให้ใส่ host เป็น '127.0.0.1'
$host = 'db'; 
$db   = 'nexthand';
$user = 'root';
$pass = 'root'; // รหัสผ่านใน Docker (ถ้า XAMPP ลบให้ว่าง)

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    // บรรทัดนี้สำคัญ! ต้องชื่อ $pdo นะ
    $pdo = new PDO($dsn, $user, $pass);
    
    // ตั้งค่าให้มันแจ้งเตือน Error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // ถ้าพัง ให้จบการทำงานแล้วบอกสาเหตุ
    die("❌ เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}
?>