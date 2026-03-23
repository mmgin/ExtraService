<?php
// view_article.php
session_start();
require_once 'configuser.php';

$article_id = $_GET['id'] ?? 0;

// Получаем статью
$stmt = $pdo->prepare("SELECT * FROM knowledge_articles WHERE id = ? AND is_published = 1");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: knowledge_base.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - База знаний</title>
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
            max-width: 900px;
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .article-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        
        .article-header h1 {
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .article-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .article-body {
            padding: 40px;
            line-height: 1.8;
            color: #333;
        }
        
        .article-body h2,
        .article-body h3 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .article-body p {
            margin-bottom: 15px;
        }
        
        .article-body ul,
        .article-body ol {
            margin-left: 25px;
            margin-bottom: 15px;
        }
        
        .article-body li {
            margin-bottom: 5px;
        }
        
        .article-body code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .article-tags {
            padding: 20px 40px 40px;
            border-top: 1px solid #f0f0f0;
        }
        
        .tag {
            display: inline-block;
            background: #f0f0f0;
            color: #666;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .tag:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 768px) {
            .article-header {
                padding: 20px;
            }
            
            .article-header h1 {
                font-size: 22px;
            }
            
            .article-body {
                padding: 20px;
            }
            
            .article-tags {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="nav-links">
                <a href="user_dashboard.php">Главная</a>
                <a href="my_requests.php">Мои заявки</a>
                <a href="new_request.php">Новая заявка</a>
                <a href="knowledge_base.php">База знаний</a>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Гость'); ?></span>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="logout.php" style="margin: 0;">
                        <button type="submit" class="logout-btn">Выйти</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" style="background: #667eea; color: white; padding: 6px 15px; border-radius: 6px; text-decoration: none;">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <a href="knowledge_base.php" class="back-link">← Назад к базе знаний</a>
        
        <div class="article-card">
            <div class="article-header">
                <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="article-meta">
                    <span>📁 <?php echo htmlspecialchars($article['category']); ?></span>
                    <span>👁️ <?php echo number_format($article['views']); ?> просмотров</span>
                    <span>📅 <?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="article-body">
                <?php echo $article['content']; ?>
            </div>
            
            <?php if ($article['tags']): ?>
                <div class="article-tags">
                    <strong>🏷️ Теги:</strong><br><br>
                    <?php 
                        $tags = explode(',', $article['tags']);
                        foreach ($tags as $tag):
                            $tag = trim($tag);
                            if (!empty($tag)):
                    ?>
                        <a href="knowledge_base.php?tag=<?php echo urlencode($tag); ?>" class="tag">#<?php echo htmlspecialchars($tag); ?></a>
                    <?php 
                            endif;
                        endforeach; 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>