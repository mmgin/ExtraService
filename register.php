<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $department = $_POST['department'] ?? null;
        $position = $_POST['position'] ?? null;
        
      
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('Заполните обязательные поля');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Некорректный email');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Пароль должен содержать минимум 8 символов');
        }
        
      
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            throw new Exception('Пользователь с таким email или именем уже существует');
        }
        
     
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, phone, department, position, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user')
        ");
        
        $stmt->execute([$username, $email, $hashedPassword, $full_name, $phone, $department, $position]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Регистрация успешна'
        ]);
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
}
?>