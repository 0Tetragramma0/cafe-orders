<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$dishId  = $data['dish_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'error' => 'Missing order_id']);
    exit;
}

try {
    // Если передан dish_id — отмечаем только это блюдо
    if ($dishId) {
        $stmt = $pdo->prepare("UPDATE order_items SET is_ready = 1 WHERE order_id = ? AND dish_id = ?");
        $stmt->execute([$orderId, $dishId]);
        echo json_encode(['success' => true, 'message' => 'Блюдо готово']);
        exit;
    }

    // Если dish_id нет — отмечаем весь заказ
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE order_items SET is_ready = 1 WHERE order_id = ?")->execute([$orderId]);
    $pdo->prepare("UPDATE orders SET status = 'ready' WHERE id = ?")->execute([$orderId]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Заказ готов']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}