<?php
// new_request.php
session_start();



require_once 'configuser.php';

$error = '';
$success = '';

// Проверяем, существует ли пользователь в базе
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_exists = $stmt->fetch();

if (!$user_exists) {
    // Если пользователь не найден, выходим из системы
    session_destroy();
    header('Location: login.php');
    exit;
}

// Получаем категории
$stmt = $pdo->query("SELECT name FROM request_categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    $errors = [];
    
    if (empty($category)) {
        $errors[] = "Выберите категорию";
    }
    
    if (empty($title)) {
        $errors[] = "Введите тему заявки";
    } elseif (strlen($title) < 3) {
        $errors[] = "Тема должна содержать минимум 3 символа";
    }
    
    if (empty($description)) {
        $errors[] = "Введите описание заявки";
    } elseif (strlen($description) < 10) {
        $errors[] = "Описание должно содержать минимум 10 символов";
    }
    
    if (empty($errors)) {
        try {
            // Используем ID из сессии
            $user_id = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("
                INSERT INTO requests (user_id, category, title, description, priority, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$user_id, $category, $title, $description, $priority]);
            
            $request_id = $pdo->lastInsertId();
            
            // Добавляем в историю
            $stmt = $pdo->prepare("
                INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, changed_by_role) 
                VALUES (?, NULL, 'pending', ?, 'user')
            ");
            $stmt->execute([$request_id, $user_id]);
            
            $success = "Заявка успешно создана!";
            
            // Очищаем форму
            $title = $description = '';
        } catch (PDOException $e) {
            $error = "Ошибка при создании заявки: " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание заявки - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
        }
        
        .header-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
        }
        
        .nav-links a {
            color: #666;
            text-decoration: none;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .form-card h1 {
            margin-bottom: 25px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .priority-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .priority-btn {
            flex: 1;
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
        }
        
        .priority-btn.selected {
            border-color: #667eea;
            background: #f0f4ff;
            color: #667eea;
        }
        
        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-back {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 32px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        @media (max-width: 600px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .priority-buttons {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-back, .btn-submit {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="user_dashboard.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="nav-links">
                <a href="user_dashboard.php">Главная</a>
                <a href="my_requests.php">Мои заявки</a>
                <a href="new_request.php">Новая заявка</a>
            </div>
            <div class="user-menu">
                <span></span>
                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="form-card">
            <h1>Создание новой заявки</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Категория *</label>
                    <select name="category" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Тема *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="Кратко опишите суть вопроса" required>
                </div>
                
                <div class="form-group">
                    <label>Приоритет</label>
                    <div class="priority-buttons">
                        <button type="button" class="priority-btn" data-priority="low" onclick="setPriority('low')">Низкий</button>
                        <button type="button" class="priority-btn selected" data-priority="medium" onclick="setPriority('medium')">Средний</button>
                        <button type="button" class="priority-btn" data-priority="high" onclick="setPriority('high')">Высокий</button>
                        <button type="button" class="priority-btn" data-priority="urgent" onclick="setPriority('urgent')">Срочный</button>
                    </div>
                    <input type="hidden" name="priority" id="priority" value="medium">
                </div>
                
                <div class="form-group">
                    <label>Описание *</label>
                    <textarea name="description" placeholder="Подробно опишите вашу проблему или вопрос..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="my_requests.php" class="btn-back">Назад</a>
                    <button type="submit" class="btn-submit">Отправить заявку</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function setPriority(priority) {
            document.getElementById('priority').value = priority;
            document.querySelectorAll('.priority-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            document.querySelector(`.priority-btn[data-priority="${priority}"]`).classList.add('selected');
        }
    </script>
</body>
</html>