<?php
// my_requests.php
session_start();

require_once 'configuser.php';
require_once 'functions.php'; // <-- ВАЖНО: подключаем functions.php

$user_id = $_SESSION['user_id'];

// Получаем статус из фильтра
$status_filter = $_GET['status'] ?? 'all';

// Построение запроса
$sql = "SELECT * FROM requests WHERE user_id = ?";
$params = [$user_id];

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Статистика по статусам
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM requests 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$user_id]);
$status_stats = [];
while ($row = $stmt->fetch()) {
    $status_stats[$row['status']] = $row['count'];
}

// Для отладки - проверим, есть ли заявки
// echo "Найдено заявок: " . count($requests); // Раскомментируйте для проверки
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заявки - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
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
            font-size: 14px;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .username {
            color: #666;
            font-size: 14px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-card.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #e0e0e0;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .request-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .request-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-status {
            color: white;
        }
        
        .badge-priority {
            background: #f0f0f0;
            color: #666;
        }
        
        .badge-category {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .request-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .request-meta {
            font-size: 12px;
            color: #999;
            display: flex;
            gap: 15px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .btn-new {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .request-header {
                flex-direction: column;
            }
            
            .header-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="user_dashboard.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="nav-links">
            
                <a href="my_requests.php" class="active">Мои заявки</a>
                <a href="new_request.php">Новая заявка</a>
            </div>
            <div class="user-menu">
                <span class="username"></span>
                <form method="POST" action="index.html" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card <?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="filterByStatus('all')">
                <div class="stat-number"><?php echo array_sum($status_stats); ?></div>
                <div class="stat-label">Все заявки</div>
            </div>
            <div class="stat-card <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" onclick="filterByStatus('pending')">
                <div class="stat-number"><?php echo $status_stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Ожидают</div>
            </div>
            <div class="stat-card <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>" onclick="filterByStatus('in_progress')">
                <div class="stat-number"><?php echo $status_stats['in_progress'] ?? 0; ?></div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="stat-card <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" onclick="filterByStatus('completed')">
                <div class="stat-number"><?php echo $status_stats['completed'] ?? 0; ?></div>
                <div class="stat-label">Выполнены</div>
            </div>
        </div>
        
        <div class="requests-list">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <h3>У вас пока нет заявок</h3>
                    <p>Создайте первую заявку, и мы обязательно поможем вам!</p>
                    <a href="new_request.php" class="btn-new">+ Создать заявку</a>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <div class="request-card" onclick="location.href='view_request.php?id=<?php echo $request['id']; ?>'">
                        <div class="request-header">
                            <div class="request-title"><?php echo htmlspecialchars($request['title']); ?></div>
                            <div class="request-badges">
                                <span class="badge badge-status" style="background: <?php echo getStatusColor($request['status']); ?>">
                                    <?php echo getStatusText($request['status']); ?>
                                </span>
                                <span class="badge badge-priority">
                                    <?php echo getPriorityText($request['priority']); ?>
                                </span>
                                <span class="badge badge-category">
                                    <?php echo htmlspecialchars($request['category']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="request-description">
                            <?php echo htmlspecialchars(mb_substr($request['description'], 0, 150)); ?>
                            <?php if (mb_strlen($request['description']) > 150): ?>...<?php endif; ?>
                        </div>
                        <div class="request-meta">
                            <span>📅 <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></span>
                            <span>🆔 №<?php echo $request['id']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function filterByStatus(status) {
            window.location.href = 'my_requests.php?status=' + status;
        }
    </script>
</body>
</html>