<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - ExtraService</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Шапка */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
            transform: translateY(-2px);
        }
        
        /* Контейнер */
        .container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        /* Заголовок */
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: white;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .page-header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.2rem;
        }
        
        /* Контакты */
        .contacts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .contact-info {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .contact-info:hover {
            transform: translateY(-5px);
        }
        
        .contact-info h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .contact-item {
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .contact-details h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 8px;
        }
        
        .contact-details p {
            color: #666;
            line-height: 1.5;
            margin: 3px 0;
        }
        
        .work-time {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .work-time h4 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .work-time p {
            color: #555;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .work-time i {
            color: #667eea;
            width: 20px;
        }
        
        /* Карта */
        .map {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .map:hover {
            transform: translateY(-5px);
        }
        
        .map h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .map iframe {
            width: 100%;
            height: 350px;
            border: none;
        }
        
        .address-details {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .address-details i {
            color: #667eea;
            font-size: 1.2rem;
        }
        
        /* Дополнительный блок */
        .feedback-section {
            margin-top: 50px;
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feedback-section h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 15px;
        }
        
        .feedback-section p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .feedback-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid #667eea;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Подвал */
        .footer {
            background: rgba(0,0,0,0.8);
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }
        
        .footer p {
            color: rgba(255,255,255,0.7);
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .contacts-container {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav {
                flex-direction: column;
                text-align: center;
            }
            
            .contact-item {
                flex-direction: column;
                text-align: center;
            }
            
            .contact-icon {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
<a class="navbar-brand" href="index.html">
                <i class="fas fa-headset me-2"></i>ExtraService
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="nav-links">
                <a href="index.html">Главная</a>
                <a href="knowledge_base.php">База знаний</a>
                <a href="contacts.php" style="color: #667eea;">Контакты</a>
                <a href="login.php">Войти</a>
                <a href="register.php">Регистрация</a>
                <a href="admin_login.php">Администратор</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1>Свяжитесь с нами</h1>
            <p>Мы всегда рады помочь вам! Наши специалисты ответят на все вопросы</p>
        </div>
        
        <div class="contacts-container">
            <div class="contact-info">
                <h2>Наши контакты</h2>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Телефон горячей линии</h3>
                        <p><strong>8-999-999-99-99</strong> (бесплатно по России)</p>
                        <p>+7 (999) 999-99-99 (Москва)</p>
                        <p>+7 (999) 999-99-99 (Санкт-Петербург)</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Электронная почта</h3>
                        <p><strong>support@extraservice.ru</strong> - для технической поддержки</p>
                        <p><strong>info@extraservice.ru</strong> - для общих вопросов</p>
                        <p><strong>sales@extraservice.ru</strong> - для коммерческих предложений</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Главный офис</h3>
                        <p>г. Москва, ул. Тверская, д. 25, стр. 1</p>
                        <p>БЦ "Тверской", 5 этаж, офис 502</p>
                    </div>
                </div>
                
                <div class="work-time">
                    <h4><i class="far fa-clock"></i> Режим работы</h4>
                    <p><i class="fas fa-calendar-week"></i> Понедельник - Пятница: 9:00 - 20:00</p>
                    <p><i class="fas fa-calendar-day"></i> Суббота: 10:00 - 18:00</p>
                    <p><i class="fas fa-calendar-day"></i> Воскресенье: выходной</p>
                    <p><i class="fas fa-headset"></i> <strong>Онлайн-поддержка:</strong> 24/7 (круглосуточно)</p>
                </div>
            </div>
            
            <div class="map">
                <h2>Мы на карте</h2>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2245.3731411754097!2d37.6176!3d55.7558!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54a5a738fa419%3A0x7c347d50b6d8b8c4!2z0JzQvtGB0LrQstCw!5e0!3m2!1sru!2sru!4v1234567890!5m2!1sru!2sru" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
                <div class="address-details">
                    <i class="fas fa-location-dot"></i>
                    <span>м. Тверская, выход к Тверской улице, 2 минуты пешком</span>
                </div>
            </div>
        </div>
        

       
        <!-- Блок с соцсетями -->
        <div style="margin-top: 40px; text-align: center;">
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <a href="#" style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                    <i class="fab fa-vk fa-lg"></i>
                </a>
                <a href="#" style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                    <i class="fab fa-telegram fa-lg"></i>
                </a>

<a href="#" style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
    <span style="font-weight: 800; font-size: 1rem;">MAX</span>
</a>
                </a>
                <a href="#" style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                    <i class="fab fa-youtube fa-lg"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 ExtraService - Информационная поддержка клиентов. Все права защищены.</p>
        <p style="font-size: 0.8rem; margin-top: 10px;">Работаем для вас 24/7</p>
    </div>
</body>
</html>