<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('cook');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кухня</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <main>
        <h1>Активные заказы</h1>
        <div id="orders-container">Загрузка...</div>
    </main>
    <script src="../assets/js/kitchen.js"></script>
</body>
</html>