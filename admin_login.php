<?php
// admin_login.php - ИСПРАВЛЕННАЯ ВЕРСИЯ
session_start();
require_once 'configuser.php';


$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Заполните все поля";
    } else {
        try {
            // Ищем пользователя по email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Проверяем пароль и роль
            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] == 'admin') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'] ?: $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Обновляем время последнего входа
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = "У вас нет прав администратора";
                }
            } else {
                $error = "Неверный email или пароль";
            }
        } catch (PDOException $e) {
            $error = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход для администратора</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .login-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fcc;
        }
        
        .links {
            margin-top: 20px;
            text-align: center;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .demo {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 13px;
            text-align: center;
            border: 1px solid #e1e5e9;
        }
        
        .demo p {
            margin: 5px 0;
        }
        
        .demo strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Вход для администратора</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" 
                       name="email" 
                       placeholder="Email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
            </div>
            <div class="form-group">
                <input type="password" 
                       name="password" 
                       placeholder="Пароль" 
                       required>
            </div>
            <button type="submit" class="btn">Войти как администратор</button>
        </form>
        
        <div class="links">
            <a href="login.php">← Вход для пользователей</a>
            <span style="margin: 0 10px">|</span>
            <a href="index.php">На главную</a>
        </div>
        
        <div class="demo">
            <p><strong>Демо-доступ для администратора:</strong></p>
            <p>📧 Email: <strong>superadmin@site.com</strong></p>
            <p>🔑 Пароль: <strong>SuperAdmin2024!</strong></p>
            <hr style="margin: 10px 0;">
            <p><small>После входа вы сможете управлять заявками и пользователями</small></p>
        </div>
    </div>
</body>
</html>