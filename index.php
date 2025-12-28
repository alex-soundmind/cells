<?php
require_once 'config.php';
session_start();

$is_logged_in = isset($_SESSION['user']);
$action = $_GET['action'] ?? 'list';
$table = $_GET['table'] ?? 'subscribers'; // Таблица по умолчанию
$id = $_GET['id'] ?? null;

// --- ОБНОВЛЕННЫЙ СЛОВАРЬ ТАБЛИЦ ---
$tables = [
    'subscribers' => 'Абоненты',
    'tariffs' => 'Тарифы',
    'sim_cards' => 'SIM-карты',
    'contracts' => 'Договоры',
    'calls' => 'Звонки',
    'sms_messages' => 'SMS-сообщения',
    'internet_sessions' => 'Интернет-сессии',
    'admins' => 'Администраторы'
];

if (!isset($tables[$table])) {
    die('<p class="error">Неверная таблица</p>');
}

// Получение структуры
try {
    $stmt = $pdo->query("SELECT * FROM $table LIMIT 0");
    $columns = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $meta = $stmt->getColumnMeta($i);
        $columns[] = $meta['name'];
    }
    $pk = $columns[0] ?? 'id';
} catch (PDOException $e) {
    die('<p class="error">Ошибка получения структуры таблицы.</p>');
}

// --- ЛОГИКА POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $data = [];
    $errors = [];
    
    foreach ($columns as $col) {
        if ($col === $pk) continue;
        $value = $_POST[$col] ?? '';

        if ($value === '' && !str_contains($col, 'end_')) { // Некоторые поля дат могут быть пустыми
            $errors[] = "Поле '" . translate($col) . "' обязательно.";
        }
        $data[$col] = $value === '' ? null : $value;
    }

    if (empty($errors)) {
        try {
            if ($action === 'create') {
                $cols = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_fill(0, count($data), '?'));
                $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
                $stmt->execute(array_values($data));
            } elseif ($action === 'edit' && $id) {
                if ($table === 'admins' && empty($data['password'])) {
                    unset($data['password']);
                }
                $set_clauses = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE $table SET $set_clauses WHERE $pk = ?");
                $stmt->execute([...array_values($data), $id]);
            }
            header("Location: index.php?table=$table");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// --- УДАЛЕНИЕ ---
if ($action === 'delete' && $id && $is_logged_in) {
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die('<p class="error">Ошибка удаления (возможно, есть связанные записи): ' . $e->getMessage() . '</p>');
    }
    header("Location: index.php?table=$table");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Биллинг Телеком: <?= $tables[$table] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <?php foreach ($tables as $tbl_name => $tbl_title): ?>
                <a href="?table=<?= $tbl_name ?>" class="<?= $table === $tbl_name ? 'active' : '' ?>"><?= $tbl_title ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <div class="container">
        <?php if ($action === 'list'): ?>
            <h2><?= $tables[$table] ?></h2>
            <?php
            $stmt = $pdo->query("SELECT * FROM $table ORDER BY $pk DESC");
            $rows = $stmt->fetchAll();

            if (!$rows): ?>
                <p>Нет записей.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col):
                                if ($table === 'admins' && $col === 'password' && !$is_logged_in) continue;
                            ?>
                                <th><?= translate($col) ?></th>
                            <?php endforeach; ?>
                            <?php if ($is_logged_in): ?><th>Действия</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $key => $val):
                                    if ($table === 'admins' && $key === 'password' && !$is_logged_in) continue;
                                ?>
                                    <td><?= htmlspecialchars((string)$val, ENT_QUOTES) ?></td>
                                <?php endforeach; ?>
                                <?php if ($is_logged_in): ?>
                                    <td class="actions">
                                        <a href="?table=<?= $table ?>&action=edit&id=<?= $row[$pk] ?>" title="Редактировать">✏️</a>
                                        <a href="?table=<?= $table ?>&action=delete&id=<?= $row[$pk] ?>" class="delete" onclick="return confirm('Удалить запись?')">❌</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php if ($is_logged_in): ?>
                <a href="?table=<?= $table ?>&action=create" class="btn-add"><button>Добавить запись</button></a>
            <?php endif; ?>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <?php
            if (!$is_logged_in) die('Доступ запрещен.');
            $values = [];
            if ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
                $stmt->execute([$id]);
                $values = $stmt->fetch();
            }
            ?>
            <h2><?= $action === 'create' ? 'Новая запись' : 'Редактирование' ?></h2>
            <form method="post">
                <?php foreach ($columns as $col):
                    if ($col === $pk) continue;
                    $val = $values[$col] ?? '';
                    
                    $type = 'text';
                    if (str_contains($col, 'date')) $type = 'date';
                    elseif (str_contains($col, 'time')) $type = 'time';
                    elseif (in_array($col, ['cost', 'balance', 'traffic', 'duration', 'included_minutes', 'included_traffic'])) $type = 'number';
                    elseif ($col === 'email') $type = 'email';
                    elseif ($col === 'password') $type = 'password';
                ?>
                    <label><?= translate($col) ?></label>
                    <?php if ($col === 'description' || $col === 'text'): ?>
                        <textarea name="<?= $col ?>" required><?= htmlspecialchars($val) ?></textarea>
                    <?php else: ?>
                        <input type="<?= $type ?>" name="<?= $col ?>" value="<?= htmlspecialchars($val) ?>" step="any" required>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="form-actions">
                    <input type="submit" value="Сохранить">
                    <a href="?table=<?= $table ?>"><button type="button" class="danger">Отмена</button></a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <footer>
        <?php if (!$is_logged_in): ?>
            <a href="auth.php?mode=login">Вход для персонала</a>
        <?php else: ?>
            Админ: <b><?= htmlspecialchars($_SESSION['user']['email']) ?></b> | <a href="logout.php">Выйти</a>
        <?php endif; ?>
    </footer>
</body>
</html>