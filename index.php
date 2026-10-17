<?php
session_start();

// Если пользователь уже авторизован — направляем по роли
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'waiter':
            header('Location: /waiter/dashboard.php');
            exit();
        case 'cook':
            header('Location: /cook/kitchen.php');
            exit();
        case 'admin':
            header('Location: /admin/dashboard.php');
            exit();
    }
}

// Иначе — на страницу входа
header('Location: /login.php');
exit();