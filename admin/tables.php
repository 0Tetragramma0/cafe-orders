<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $number = (int)($_POST['number'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 2);

        if ($number > 0) {
            $stmt = $pdo->prepare("SELECT id FROM tables WHERE number = ?");
            $stmt->execute([$number]);

            if ($stmt->fetch()) {
                $message = 'Столик с таким номером уже существует';
            } else {
                $pdo->prepare("INSERT INTO tables (number, capacity) VALUES (?, ?)")->execute([$number, $capacity]);
                $message = 'Столик добавлен';
            }
        } else {
            $message = 'Введите номер столика';
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $number = (int)$_POST['number'];
        $capacity = (int)$_POST['capacity'];
        $status = $_POST['status'] ?? 'free';

        $pdo->prepare("UPDATE tables SET number = ?, capacity = ?, status = ? WHERE id = ?")
            ->execute([$number, $capacity, $status, $id]);
        $message = 'Столик обновлён';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Проверка, есть ли активные заказы за этим столиком
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE table_id = ? AND status IN ('draft','in_progress','ready','paid')");
        $stmt->execute([$id]);

        if ($stmt->fetch()['cnt'] > 0) {
            $message = 'Нельзя удалить столик с активными заказами';
        } else {
            $pdo->prepare("DELETE FROM tables WHERE id = ?")->execute([$id]);
            $message = 'Столик удалён';
        }
    }
}

$tables = $pdo->query("SELECT * FROM tables ORDER BY number")->fetchAll();

$editTable = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editTable = $stmt->fetch();
}
?>
<?php include '../includes/header.php'; ?>

<h1>Управление столиками</h1>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" class="form-card">
    <h2><?= $editTable ? 'Редактировать столик' : 'Добавить столик' ?></h2>
    <input type="hidden" name="action" value="<?= $editTable ? 'edit' : 'add' ?>">
    <?php if ($editTable): ?>
        <input type="hidden" name="id" value="<?= $editTable['id'] ?>">
    <?php endif; ?>

    <label>Номер:
        <input type="number" name="number" min="1" required value="<?= $editTable['number'] ?? '' ?>">
    </label>
    <label>Вместимость (чел.):
        <input type="number" name="capacity" min="1" value="<?= $editTable['capacity'] ?? 2 ?>">
    </label>
    <?php if ($editTable): ?>
        <label>Статус:
            <select name="status">
                <?php foreach (['free' => 'Свободен', 'occupied' => 'Занят', 'reserved' => 'Зарезервирован'] as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $editTable['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <button type="submit"><?= $editTable ? 'Сохранить' : 'Добавить' ?></button>
    <?php if ($editTable): ?><a href="tables.php">Отмена</a><?php endif; ?>
</form>

<h2>Список столиков</h2>
<table class="data-table">
    <thead>
        <tr><th>ID</th><th>Номер</th><th>Вместимость</th><th>Статус</th><th>Действия</th></tr>
    </thead>
    <tbody>
        <?php foreach ($tables as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td>№<?= $t['number'] ?></td>
                <td><?= $t['capacity'] ?> чел.</td>
                <td><?= ['free' => 'Свободен', 'occupied' => 'Занят', 'reserved' => 'Зарезервирован'][$t['status']] ?? $t['status'] ?></td>
                <td>
                    <a href="tables.php?edit=<?= $t['id'] ?>">✏️</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Удалить столик?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>