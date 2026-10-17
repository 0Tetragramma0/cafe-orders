<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('waiter');

// Получаем активные заказы официанта (можно добавить фильтр по user_id, если нужно)
$stmt = $pdo->prepare("
    SELECT o.*, t.number AS table_number
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.status IN ('in_progress', 'ready')
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Для каждого заказа получаем список блюд
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    // Создаем плейсхолдеры (?, ?, ?)
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
    
    $stmtItems = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, oi.is_ready, d.name
        FROM order_items oi
        JOIN dishes d ON oi.dish_id = d.id
        WHERE oi.order_id IN ($inQuery)
        ORDER BY oi.id
    ");
    $stmtItems->execute($orderIds);
    
    // Группируем блюда по ID заказа
    while ($row = $stmtItems->fetch()) {
        $orderItems[$row['order_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Рабочий стол официанта</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <h1>Активные заказы</h1>
    <a href="order-create.php" class="btn-primary" style="margin-bottom: 20px; display: inline-block;">+ Новый заказ</a>

    <?php if (empty($orders)): ?>
        <div class="message">Нет активных заказов</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card" data-order-id="<?= $order['id'] ?>">
                <div class="order-header" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong>Столик №<?= $order['table_number'] ?></strong>
                    <span class="status-<?= $order['status'] ?>">
                        <?= $order['status'] === 'ready' ? 'Готов к выдаче' : 'Готовится' ?>
                    </span>
                </div>
                
                <!-- Список блюд -->
                <div style="margin-bottom: 15px; font-size: 14px;">
                    <?php if (isset($orderItems[$order['id']])): ?>
                        <?php foreach ($orderItems[$order['id']] as $item): ?>
                            <div style="margin-bottom: 4px;">
                                <?= $item['is_ready'] ? '✅' : '⏳' ?> 
                                <?= htmlspecialchars($item['name']) ?> 
                                <strong>(<?= $item['quantity'] ?> шт.)</strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <div>Сумма: <strong><?= number_format($order['total'], 2) ?> ₽</strong></div>
                    <div>
                        <a href="order-view.php?id=<?= $order['id'] ?>" class="btn-back" style="margin-right: 10px;">Подробнее</a>
                        <?php if ($order['status'] === 'ready'): ?>
                            <a href="payment.php?order_id=<?= $order['id'] ?>" class="btn-primary">Оплатить</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Скрипт для автообновления статусов каждые 10 секунд -->
    <script>
        setInterval(function() {
            // Перезагружаем страницу, чтобы подтянуть свежие статусы из БД
            location.reload();
        }, 10000);
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>