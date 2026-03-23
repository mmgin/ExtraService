<?php
// configadmin.php
session_start();

// =====================================================
// Настройки базы данных
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'edinaya');
define('DB_USER', 'root');
define('DB_PASS', '');

// =====================================================
// Настройки сайта
// =====================================================
define('SITE_NAME', 'Система заявок');
define('SITE_URL', 'http://localhost/ucebprakt/');

// =====================================================
// Подключение к базе данных
// =====================================================
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

// =====================================================
// Функция для проверки авторизации администратора
// =====================================================
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// =====================================================
// Функция для проверки, является ли пользователь админом
// =====================================================
function isAdmin($user_id = null) {
    global $pdo;
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$user_id) return false;
    
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    return $user && $user['role'] == 'admin';
}

// =====================================================
// Функция для получения данных администратора
// =====================================================
function getAdmin($user_id = null) {
    global $pdo;
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$user_id) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// =====================================================
// Функция для экранирования вывода
// =====================================================
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>