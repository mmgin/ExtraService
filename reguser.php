<?php
// register.php
session_start();



require_once 'config.php'; // подключаем файл с PDO

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';
$email = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Получаем данные из формы
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Валидация данных
    $errors = [];
    
    // Проверка email
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email адрес";
    }
    
    // Проверка пароля
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    // Проверка подтверждения пароля
    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
    }
    
    // Если нет ошибок, проверяем уникальность email
    if (empty($errors)) {
        try {
            // Проверка существования пользователя
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $errors[] = "Пользователь с таким email уже существует";
            } else {
                // Хеширование пароля
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставка нового пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password) 
                    VALUES (?, ?)
                ");
                
                $stmt->execute([$email, $hashed_password]);
                
                $user_id = $pdo->lastInsertId();
                
                // Автоматическая авторизация после регистрации
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                
                $success = "Регистрация успешно завершена! Перенаправление...";
                
                // Перенаправление через 2 секунды
                header("refresh:2;url=user_dashboard.php");
            }
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
    
    $error = implode('<br>', $errors);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input.error {
            border-color: #e74c3c;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        .error-alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-meter {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s;
            background: #e74c3c;
        }
        
        .strength-text {
            font-size: 11px;
            margin-top: 4px;
            color: #666;
        }
        
        .show-password {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .show-password input {
            width: auto;
            margin: 0;
        }
        
        .show-password label {
            margin: 0;
            font-size: 12px;
            font-weight: normal;
            cursor: pointer;
        }
        
        @media (max-width: 480px) {
            .register-container {
                margin: 20px;
            }
            
            .register-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Регистрация</h1>
            <p>Создайте аккаунт с помощью email и пароля</p>
        </div>
        
        <div class="register-form">
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="email">Email адрес *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="example@mail.com"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Минимум 6 символов"
                           required>
                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-meter-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля *</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Повторите пароль"
                           required>
                </div>
                
                <div class="show-password">
                    <input type="checkbox" id="showPassword">
                    <label for="showPassword">Показать пароли</label>
                </div>
                
                <button type="submit" class="btn-register">Зарегистрироваться</button>
            </form>
            
            <div class="login-link">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </div>
        </div>
    </div>
    
    <script>
        // Проверка сложности пароля
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            if (password.length === 0) return 0;
            if (strength <= 2) return 1;
            if (strength <= 4) return 2;
            return 3;
        }
        
        function updateStrengthMeter() {
            const password = passwordInput.value;
            const strength = checkPasswordStrength(password);
            
            let width = 0;
            let text = '';
            let color = '';
            
            switch(strength) {
                case 0:
                    width = 0;
                    text = '';
                    color = '#e0e0e0';
                    break;
                case 1:
                    width = 33;
                    text = 'Слабый пароль';
                    color = '#e74c3c';
                    break;
                case 2:
                    width = 66;
                    text = 'Средний пароль';
                    color = '#f39c12';
                    break;
                case 3:
                    width = 100;
                    text = 'Сильный пароль';
                    color = '#27ae60';
                    break;
            }
            
            strengthFill.style.width = width + '%';
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = text;
        }
        
        // Показать/скрыть пароль
        const showPasswordCheckbox = document.getElementById('showPassword');
        
        function togglePasswordVisibility() {
            const type = showPasswordCheckbox.checked ? 'text' : 'password';
            passwordInput.type = type;
            confirmInput.type = type;
        }
        
        passwordInput.addEventListener('input', updateStrengthMeter);
        showPasswordCheckbox.addEventListener('change', togglePasswordVisibility);
        
        // Валидация перед отправкой
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 6 символов!');
                return false;
            }
        });
    </script>
</body>
</html>