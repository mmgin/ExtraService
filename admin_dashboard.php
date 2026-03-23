<?php
// admin_dashboard.php
session_start();
require_once 'configuser.php';

// Проверяем авторизацию администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$admin = getCurrentUser();

// Функции для статусов (добавляем здесь, если их нет в config2.php)
function getStatusText($status) {
    $texts = [
        'pending' => 'Ожидает обработки',
        'in_progress' => 'В работе',
        'completed' => 'Выполнена',
        'rejected' => 'Отклонена',
        'cancelled' => 'Отменена'
    ];
    return $texts[$status] ?? $status;
}

function getStatusColor($status) {
    $colors = [
        'pending' => '#f39c12',
        'in_progress' => '#3498db',
        'completed' => '#27ae60',
        'rejected' => '#e74c3c',
        'cancelled' => '#95a5a6'
    ];
    return $colors[$status] ?? '#95a5a6';
}

function getPriorityText($priority) {
    $texts = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный'
    ];
    return $texts[$priority] ?? $priority;
}

// Статистика
// Всего заявок
$stmt = $pdo->query("SELECT COUNT(*) as total FROM requests");
$total_requests = $stmt->fetch()['total'];

// Заявки по статусам
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status");
$status_stats = [];
while ($row = $stmt->fetch()) {
    $status_stats[$row['status']] = $row['count'];
}

// Всего пользователей
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total'];

// Всего администраторов
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$total_admins = $stmt->fetch()['total'];

// Последние заявки
$stmt = $pdo->query("
    SELECT r.*, u.email as user_email, u.full_name as user_name
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$recent_requests = $stmt->fetchAll();

// Функция для получения данных текущего пользователя
function getCurrentUser() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - <?php echo SITE_NAME; ?></title>
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
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .welcome-card h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .section-header h2 {
            font-size: 18px;
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .request-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .request-item:hover {
            background: #f8f9fa;
        }
        
        .request-item:last-child {
            border-bottom: none;
        }
        
        .request-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .request-meta {
            font-size: 12px;
            color: #999;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="admin_dashboard.php" class="logo">ExtraService - Админ панель</a>
            <div class="nav-links">
                <a href="admin_dashboard.php">Главная</a>
                <a href="admin_requests.php">Все заявки</a>
                <a href="admin_users.php">Пользователи</a>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($admin['full_name'] ?: $admin['email']); ?></span>
                <form method="POST" action="index.html" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Добро пожаловать, <?php echo htmlspecialchars($admin['full_name'] ?: $admin['email']); ?>!</h1>
            <p>Панель управления системой заявок</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_requests; ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Ожидают обработки</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['in_progress'] ?? 0; ?></div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['completed'] ?? 0; ?></div>
                <div class="stat-label">Выполнено</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_admins; ?></div>
                <div class="stat-label">Администраторов</div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>📋 Последние заявки</h2>
                <a href="admin_requests.php" class="btn-primary">Все заявки →</a>
            </div>
            
            <?php if (empty($recent_requests)): ?>
                <div class="empty-state">
                    <p>Нет заявок</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_requests as $request): ?>
                    <div class="request-item" onclick="location.href='admin_view_request.php?id=<?php echo $request['id']; ?>'">
                        <div class="request-title">
                            #<?php echo $request['id']; ?> - <?php echo htmlspecialchars($request['title']); ?>
                        </div>
                        <div class="request-meta">
                            <span>👤 <?php echo htmlspecialchars($request['user_email']); ?></span>
                            <span>📂 <?php echo htmlspecialchars($request['category']); ?></span>
                            <span>📅 <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></span>
                            <span>
                                <span class="status-badge" style="background: <?php echo getStatusColor($request['status']); ?>">
                                    <?php echo getStatusText($request['status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>