<?php
// user_dashboard.php
session_start();



require_once 'configuser.php';

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Если пользователь не найден, выходим
if (!$user) {
    session_destroy();
    header('Location: configuser.php');
    exit;
}

// Получаем статистику заявок
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM requests WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_requests = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_requests = $stmt->fetch()['pending'];

$stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM requests WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$completed_requests = $stmt->fetch()['completed'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель пользователя - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 14px;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-name {
            color: #333;
            font-weight: 500;
        }
        
        .btn-requests {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-requests:hover {
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .welcome-card h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item strong {
            display: inline-block;
            width: 130px;
            color: #555;
            font-weight: 600;
        }
        
        .info-item span {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
            }
            
            .welcome-card {
                padding: 30px;
            }
            
            .welcome-card h1 {
                font-size: 24px;
            }
            
            .info-item strong {
                display: block;
                margin-bottom: 5px;
                width: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">ExtraService</div>
            <div class="nav-links">
                <a href="user_dashboard.php">Главная</a>
                <a href="my_requests.php">Мои заявки</a>
                <a href="new_request.php">Новая заявка</a>
            </div>
            <div class="user-info">
                <span class="user-name">👤 <?php echo htmlspecialchars($user['full_name'] ?: $user['email']); ?></span>
                <a href="my_requests.php" class="btn-requests">📋 Мои заявки</a>
                <form method="POST" action="index.html" style="margin: 0;">
                    <button type="submit" class="logout-btn">🚪 Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Добро пожаловать, <?php echo htmlspecialchars($user['full_name'] ?: $user['email']); ?>!</h1>
            <p>Вы успешно вошли в систему как обычный пользователь</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_requests; ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_requests; ?></div>
                <div class="stat-label">В обработке</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_requests; ?></div>
                <div class="stat-label">Выполнено</div>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>📋 Информация об аккаунте</h3>
                <div class="info-item">
                    <strong>ID пользователя:</strong>
                    <span>#<?php echo $user['id']; ?></span>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Полное имя:</strong>
                    <span><?php echo htmlspecialchars($user['full_name'] ?: 'Не указано'); ?></span>
                </div>
                <div class="info-item">
                    <strong>Телефон:</strong>
                    <span><?php echo htmlspecialchars($user['phone'] ?: 'Не указан'); ?></span>
                </div>
                <div class="info-item">
                    <strong>Дата регистрации:</strong>
                    <span><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></span>
                </div>
                <?php if ($user['last_login']): ?>
                <div class="info-item">
                    <strong>Последний вход:</strong>
                    <span><?php echo date('d.m.Y H:i', strtotime($user['last_login'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3>📊 Статистика аккаунта</h3>
                <div class="info-item">
                    <strong>Статус:</strong>
                    <span style="color: #27ae60;">✅ Активен</span>
                </div>
                <div class="info-item">
                    <strong>Тип аккаунта:</strong>
                    <span>👤 Пользователь</span>
                </div>
                <div class="info-item">
                    <strong>Всего заявок:</strong>
                    <span><?php echo $total_requests; ?></span>
                </div>
                <div class="info-item">
                    <strong>Активных заявок:</strong>
                    <span><?php echo $pending_requests; ?></span>
                </div>
                <div class="info-item">
                    <strong>Выполненных заявок:</strong>
                    <span><?php echo $completed_requests; ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>