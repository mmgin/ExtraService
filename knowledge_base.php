<?php
// knowledge_base.php
session_start();
require_once 'configuser.php';

// Получаем категории для фильтра
$stmt = $pdo->query("SELECT DISTINCT category FROM knowledge_articles WHERE is_published = 1 ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получаем популярные теги
$stmt = $pdo->query("
    SELECT tags, COUNT(*) as count 
    FROM knowledge_articles 
    WHERE is_published = 1 AND tags IS NOT NULL 
    GROUP BY tags 
    ORDER BY count DESC 
    LIMIT 10
");
$popular_tags = $stmt->fetchAll();

// Парсим теги
$all_tags = [];
foreach ($popular_tags as $tag_item) {
    $tags_array = explode(',', $tag_item['tags']);
    foreach ($tags_array as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $all_tags[$tag] = ($all_tags[$tag] ?? 0) + $tag_item['count'];
        }
    }
}
arsort($all_tags);
$popular_tags_list = array_slice($all_tags, 0, 10);

// Фильтрация
$category_filter = $_GET['category'] ?? 'all';
$tag_filter = $_GET['tag'] ?? '';
$search_query = $_GET['search'] ?? '';

// Построение запроса
$sql = "SELECT * FROM knowledge_articles WHERE is_published = 1";
$params = [];

if ($category_filter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

if (!empty($tag_filter)) {
    $sql .= " AND tags LIKE ?";
    $params[] = "%$tag_filter%";
}

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR content LIKE ? OR tags LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY views DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Увеличиваем счетчик просмотров при клике
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $article_id = $_GET['view'];
    $stmt = $pdo->prepare("UPDATE knowledge_articles SET views = views + 1 WHERE id = ?");
    $stmt->execute([$article_id]);
    
    header('Location: view_article.php?id=' . $article_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База знаний - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Кнопка назад */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #e0e0e0;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        
        .back-button:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateX(-3px);
        }
        
        .back-button-bottom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #e0e0e0;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 30px;
        }
        
        .back-button-bottom:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateX(-3px);
        }
        
        .knowledge-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .knowledge-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .knowledge-header p {
            color: #666;
        }
        
        /* Поиск */
        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .clear-btn {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .search-info {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .search-info strong {
            color: #667eea;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }
        
        /* Сайдбар */
        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar-section {
            margin-bottom: 25px;
        }
        
        .sidebar-section h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-list li {
            margin-bottom: 10px;
        }
        
        .category-list a {
            color: #666;
            text-decoration: none;
            display: block;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .category-list a:hover,
        .category-list a.active {
            background: #667eea10;
            color: #667eea;
        }
        
        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .tag {
            background: #f0f0f0;
            color: #666;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .tag:hover,
        .tag.active {
            background: #667eea;
            color: white;
        }
        
        /* Список статей */
        .articles-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .article-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .article-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        
        .article-title a {
            color: #333;
            text-decoration: none;
        }
        
        .article-title a:hover {
            color: #667eea;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 0 2px;
            border-radius: 3px;
        }
        
        .article-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .article-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #999;
        }
        
        .article-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .article-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        
        .article-tag {
            background: #f0f0f0;
            color: #666;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-decoration: none;
        }
        
        .article-tag:hover {
            background: #667eea;
            color: white;
        }
        
        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px;
            text-align: center;
            color: #999;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #666;
        }
        
        .footer-buttons {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                order: 2;
            }
            
            .articles-list {
                order: 1;
            }
            
            .search-box {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Кнопка назад сверху -->
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
        
        <div class="knowledge-header">
            <h1>📚 База знаний</h1>
            <p>Полезные статьи, инструкции и руководства</p>
        </div>
        
        <!-- Блок поиска -->
        <div class="search-section">
            <form method="GET" action="" class="search-box">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="🔍 Поиск по статьям... (по названию, содержимому или тегам)"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">Найти</button>
                <?php if (!empty($search_query) || $category_filter !== 'all' || !empty($tag_filter)): ?>
                    <a href="knowledge_base.php" class="clear-btn">Сбросить фильтры</a>
                <?php endif; ?>
            </form>
            
            <?php if (!empty($search_query)): ?>
                <div class="search-info">
                    🔍 Результаты поиска: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong> — найдено <strong><?php echo count($articles); ?></strong> статей
                </div>
            <?php endif; ?>
        </div>
        
        <div class="main-grid">
            <!-- Сайдбар -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>📁 Категории</h3>
                    <ul class="category-list">
                        <li><a href="knowledge_base.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" class="<?php echo $category_filter === 'all' ? 'active' : ''; ?>">Все статьи</a></li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="?category=<?php echo urlencode($cat); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                   class="<?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php if (!empty($popular_tags_list)): ?>
                <div class="sidebar-section">
                    <h3>🏷️ Популярные теги</h3>
                    <div class="tag-cloud">
                        <?php foreach ($popular_tags_list as $tag => $count): ?>
                            <a href="?tag=<?php echo urlencode($tag); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="tag <?php echo $tag_filter === $tag ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($tag); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Список статей -->
            <div class="articles-list">
                <?php if (empty($articles)): ?>
                    <div class="empty-state">
                        <h3>😕 Статьи не найдены</h3>
                        <p>Попробуйте изменить поисковый запрос или выбрать другую категорию</p>
                        <a href="knowledge_base.php" style="display: inline-block; margin-top: 15px; color: #667eea;">Вернуться ко всем статьям</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="article-card" onclick="location.href='knowledge_base.php?view=<?php echo $article['id']; ?>'">
                            <div class="article-title">
                                <a href="knowledge_base.php?view=<?php echo $article['id']; ?>">
                                    <?php 
                                        $title = htmlspecialchars($article['title']);
                                        if (!empty($search_query)) {
                                            $title = preg_replace('/(' . preg_quote($search_query, '/') . ')/iu', '<span class="highlight">$1</span>', $title);
                                        }
                                        echo $title;
                                    ?>
                                </a>
                            </div>
                            <div class="article-description">
                                <?php 
                                    $content = strip_tags($article['content']);
                                    $description = mb_substr($content, 0, 200);
                                    if (!empty($search_query)) {
                                        $description = preg_replace('/(' . preg_quote($search_query, '/') . ')/iu', '<span class="highlight">$1</span>', $description);
                                    }
                                    echo $description . (mb_strlen($content) > 200 ? '...' : '');
                                ?>
                            </div>
                            <div class="article-meta">
                                <span>📁 <?php echo htmlspecialchars($article['category']); ?></span>
                                <span>👁️ <?php echo number_format($article['views']); ?> просмотров</span>
                                <span>📅 <?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                            </div>
                            <?php if ($article['tags']): ?>
                                <div class="article-tags">
                                    <?php 
                                        $tags = explode(',', $article['tags']);
                                        foreach ($tags as $tag):
                                            $tag = trim($tag);
                                            if (!empty($tag)):
                                    ?>
                                        <a href="?tag=<?php echo urlencode($tag); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="article-tag" onclick="event.stopPropagation()">
                                            #<?php echo htmlspecialchars($tag); ?>
                                        </a>
                                    <?php 
                                            endif;
                                        endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Кнопка назад снизу -->
        <div class="footer-buttons">
            <a href="javascript:history.back()" class="back-button-bottom">
                <i class="fas fa-arrow-left"></i> Вернуться назад
            </a>
        </div>
    </div>
    
    <!-- Добавляем Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</body>
</html>