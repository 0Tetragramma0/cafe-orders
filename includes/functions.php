<?php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /cafe_orders/login.php');
        exit();
    }
}

function checkRole($role) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (($_SESSION['role'] ?? '') !== $role) {
        die('Доступ запрещён');
    }
}
?>