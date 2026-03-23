<?php
// config.php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'uservhod');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки сайта
define('SITE_URL', 'http://localhost/ucebprakt/');
define('SITE_NAME', 'Мой Сайт');

// Создаем подключение к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Функция для проверки роли
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

// Функция для выхода
function logout() {
    session_start();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>