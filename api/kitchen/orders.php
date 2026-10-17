<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$stmt = $pdo->prepare("
    SELECT o.id, o.table_id, t.number AS table_number, o.created_at,
           GROUP_CONCAT(CONCAT(d.name, ' (', oi.quantity, ' шт.)') SEPARATOR ', ') AS items
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN dishes d ON oi.dish_id = d.id
    WHERE o.status = 'in_progress'
    GROUP BY o.id
    ORDER BY o.created_at ASC
");
$stmt->execute();
echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
?>