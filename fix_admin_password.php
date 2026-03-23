<?php
// fix_admin_password.php
require_once 'configadmin.php';

$email = 'admin@example.com';
$new_password = 'admin123';

echo "<h2>Сброс пароля администратора</h2>";

try {
    // Проверяем существование администратора
    $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Найден пользователь:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Роль: " . $user['role'] . "<br>";
        echo "Текущий хеш пароля: " . $user['password'] . "<br><br>";
        
        // Создаем новый хеш
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Обновляем пароль
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$new_hash, $email]);
        
        echo "✅ Пароль успешно обновлен!<br>";
        echo "Новый хеш: " . $new_hash . "<br><br>";
        
        // Проверяем новый пароль
        $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $updated = $stmt->fetch();
        
        if (password_verify($new_password, $updated['password'])) {
            echo "✅ <strong style='color: green;'>Проверка пройдена! Пароль работает.</strong><br>";
        } else {
            echo "❌ <strong style='color: red;'>Ошибка: пароль не прошел проверку!</strong><br>";
        }
        
    } else {
        echo "❌ Пользователь с email {$email} не найден!<br>";
        echo "Создаем нового администратора...<br><br>";
        
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role, is_active) 
            VALUES (?, ?, 'Главный Администратор', 'admin', 1)
        ");
        $stmt->execute([$email, $new_hash]);
        
        echo "✅ Администратор создан!<br>";
        echo "Email: admin@example.com<br>";
        echo "Пароль: admin123<br>";
    }
    
    echo "<br><hr><br>";
    echo "<h3>Все пользователи в системе:</h3>";
    $stmt = $pdo->query("SELECT id, email, full_name, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Email</th><th>Имя</th><th>Роль</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td>{$u['full_name']}</td>";
        echo "<td>{$u['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><br>";
    echo "<a href='admin_login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Перейти к входу</a>";
    
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>