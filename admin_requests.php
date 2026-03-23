<?php
// admin_requests.php
session_start();
require_once 'configuser.php';

// Проверяем авторизацию администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Получаем параметры фильтрации
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Построение запроса
$sql = "
    SELECT r.*, u.email as user_email, u.full_name as user_name
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Статистика по статусам
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status");
$status_stats = [];
while ($row = $stmt->fetch()) {
    $status_stats[$row['status']] = $row['count'];
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Ожидает',
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все заявки - Админ панель</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .requests-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 1px solid #e0e0e0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
            cursor: pointer;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                display: block;
                overflow-x: auto;
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
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Администратор'); ?></span>
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
            <div class="stat-card <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" onclick="filterByStatus('rejected')">
                <div class="stat-number"><?php echo $status_stats['rejected'] ?? 0; ?></div>
                <div class="stat-label">Отклонены</div>
            </div>
        </div>
        
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <input type="text" name="search" placeholder="Поиск по теме, описанию или email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Поиск</button>
                <?php if ($search): ?>
                    <a href="?status=<?php echo $status_filter; ?>" style="background: #e74c3c; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="requests-table">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <h3>Нет заявок</h3>
                    <p>Пока нет ни одной заявки в системе.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тема</th>
                            <th>Пользователь</th>
                            <th>Категория</th>
                            <th>Приоритет</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr onclick="location.href='admin_view_request.php?id=<?php echo $req['id']; ?>'">
                            <td>#<?php echo $req['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars(mb_substr($req['title'], 0, 50)); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($req['category']); ?></td>
                            <td><?php echo $req['priority']; ?></td>
                            <td><span class="status-badge" style="background: <?php echo getStatusColor($req['status']); ?>"><?php echo getStatusText($req['status']); ?></span></td>
                            <td><?php echo date('d.m.Y', strtotime($req['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function filterByStatus(status) {
            let url = new URL(window.location.href);
            url.searchParams.set('status', status);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>