<?php
require_once 'config.php';
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$page_mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($page_mode === 'register') {
        if (!empty($email) && !empty($password)) {
            try {
                // В таблице Admins нет поля name, используем только email и password
                $stmt = $pdo->prepare('INSERT INTO Admins (email, password) VALUES (?, ?)');
                $stmt->execute([$email, $password]);
                header('Location: auth.php?mode=login&registered=true');
                exit;
            } catch (PDOException $e) {
                $error_message = 'Пользователь с таким email уже существует.';
            }
        } else {
            $error_message = 'Все поля обязательны для заполнения.';
        }
    } else {
        if (!empty($email) && !empty($password)) {
            $stmt = $pdo->prepare('SELECT * FROM Admins WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $password === $user['password']) {
                $_SESSION['user'] = $user;
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Неверный email или пароль.';
            }
        } else {
            $error_message = 'Введите email и пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $page_mode === 'login' ? 'Вход' : 'Регистрация' ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 5rem;">
        <h2><?= $page_mode === 'login' ? 'Вход в панель управления' : 'Регистрация админа' ?></h2>

        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <p class="success" style="background-color: #2ecc71; color: white; padding: 1rem; border-radius: 8px;">Регистрация успешна!</p>
        <?php endif; ?>

        <form method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="<?= $page_mode === 'login' ? 'Войти' : 'Создать аккаунт' ?>">
        </form>

        <p style="text-align: center; margin-top: 1.5rem;">
            <?php if ($page_mode === 'login'): ?>
                Нет доступа? <a href="?mode=register">Зарегистрируйтесь</a>
            <?php else: ?>
                Уже есть доступ? <a href="?mode=login">Войдите</a>
            <?php endif; ?>
        </p>
    </div>
</body>
</html>