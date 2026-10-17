<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentRole = $_SESSION['role'] ?? '';
$currentName = $_SESSION['full_name'] ?? 'Гость';
?>
<header class="main-header">
    <div class="logo"><a href="/">☕ Кафе-Заказ</a></div>
    <nav class="main-nav">
        <?php if ($currentRole === 'waiter'): ?>
            <a href="/waiter/dashboard.php">Рабочий стол</a>
            <a href="/waiter/order-create.php">Новый заказ</a>
        <?php elseif ($currentRole === 'cook'): ?>
            <a href="/cook/kitchen.php">Кухня</a>
        <?php elseif ($currentRole === 'admin'): ?>
            <a href="/admin/dashboard.php">Панель</a>
            <a href="/admin/dishes.php">Меню</a>
            <a href="/admin/tables.php">Столики</a>
            <a href="/admin/report-revenue.php">Отчёты</a>
        <?php endif; ?>
    </nav>
    <div class="user-info">
        <span><?= htmlspecialchars($currentName) ?></span>
        <a href="/logout.php" class="btn-logout">Выход</a>
    </div>
</header>
<main class="main-content">