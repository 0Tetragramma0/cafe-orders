<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('cook');

$orderId = (int)($_GET['id'] ?? 0);
$message = '';

// Отметка готовности блюда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_dish'])) {
    $dishId = (int)$_POST['dish_id'];
    $stmt = $pdo->prepare("UPDATE order_items SET is_ready = 1 WHERE order_id = ? AND dish_id = ?");
    $stmt->execute([$orderId, $dishId]);
    $message = 'Блюдо отмечено как готовое';
}

// Отметка готовности всего заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_order'])) {
    $pdo->prepare("UPDATE order_items SET is_ready = 1 WHERE order_id = ?")->execute([$orderId]);
    $pdo->prepare("UPDATE orders SET status = 'ready' WHERE id = ?")->execute([$orderId]);
    $message = 'Заказ отмечен как готовый';
}

// Получаем данные заказа
$stmt = $pdo->prepare("
    SELECT o.*, t.number AS table_number, u.full_name AS waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Заказ не найден');
}

// Позиции заказа
$stmt = $pdo->prepare("
    SELECT oi.*, d.name, d.cooking_time
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Проверка: все ли блюда готовы
$allReady = true;
foreach ($items as $item) {
    if (empty($item['is_ready'])) {
        $allReady = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ №<?= $orderId ?> | Кухня</title>
    <!-- Подключаем стили -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <h1>Заказ №<?= $orderId ?></h1>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Блок с информацией (белая карточка) -->
    <div class="order-info">
        <p><strong>Столик:</strong> №<?= $order['table_number'] ?></p>
        <p><strong>Официант:</strong> <?= htmlspecialchars($order['waiter_name']) ?></p>
        <p><strong>Создан:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
        <p><strong>Статус:</strong> <span class="status-<?= $order['status'] ?>"><?= $order['status'] ?></span></p>
    </div>

    <h2>Состав заказа</h2>

    <!-- Красивая таблица -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Блюдо</th>
                <th>Кол-во</th>
                <th>Время, мин</th>
                <th>Готово</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['cooking_time'] ?></td>
                    <td><?= $item['is_ready'] ? '✅' : '⏳' ?></td>
                    <td>
                        <?php if (!$item['is_ready']): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="dish_id" value="<?= $item['dish_id'] ?>">
                                <button type="submit" name="mark_dish" class="btn-primary" style="padding: 4px 12px; font-size: 13px;">Готово</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Кнопки внизу -->
    <div class="actions" style="margin-top: 20px; display: flex; gap: 10px; align-items: flex-start;">
        <?php if ($order['status'] !== 'ready' && $order['status'] !== 'paid'): ?>
            <form method="post">
                <button type="submit" name="mark_order" class="btn-primary" <?= $allReady ? '' : 'disabled' ?>>
                    Весь заказ готов
                </button>
                <?php if (!$allReady): ?>
                    <div style="margin-top: 5px; font-size: 13px; color: #777;">Отметьте все блюда готовыми</div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <a href="kitchen.php" class="btn-back" style="margin-left: 0; padding-top: 10px;">← К списку заказов</a>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>