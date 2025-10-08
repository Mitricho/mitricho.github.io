Вот готовый HTML файл с полным решением:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Перенаправление...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            transition: background 0.5s ease;
        }

        .loader-container {
            text-align: center;
            color: white;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .countdown {
            font-size: 18px;
            margin-top: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader-container">
        <div class="loader"></div>
        <div>Подготовка перенаправления...</div>
        <div class="countdown" id="countdown">5</div>
    </div>

    <script>
        // Список URL для редиректа
        const redirectUrls = {
            'capture': 'https://example.com/capture',
            'pioneer': 'https://example.com/pioneer',
            'junior': 'https://example.com/junior'
        };

        // Градиенты для каждого ключевого слова
        const gradients = {
            'capture': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'pioneer': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'junior': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'default': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
        };

        // Функция для получения параметра из URL
        function getUrlParameter(name) {
            name = name.replace(/[\[\]]/g, '\\$&');
            const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
            const results = regex.exec(window.location.search);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }

        // Основная функция
        function initRedirect() {
            // Получаем ключевое слово из параметра URL
            const keyword = getUrlParameter('url') || 'default';
            
            // Устанавливаем соответствующий градиент
            const gradient = gradients[keyword] || gradients['default'];
            document.body.style.background = gradient;
            
            // Получаем URL для редиректа
            const redirectUrl = redirectUrls[keyword] || 'https://example.com';
            
            // Обновляем счетчик
            let secondsLeft = 5;
            const countdownElement = document.getElementById('countdown');
            
            const countdownInterval = setInterval(() => {
                secondsLeft--;
                countdownElement.textContent = secondsLeft;
                
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    // Выполняем редирект
                    window.location.href = redirectUrl;
                }
            }, 1000);
        }

        // Запускаем когда страница полностью загружена
        window.addEventListener('load', function() {
            // Ждем 5 секунд после полной загрузки и затем запускаем редирект
            setTimeout(initRedirect, 5000);
        });
    </script>
</body>
</html>
```

Этот HTML файл содержит:

1. **Градиентный фон** на всю страницу
2. **Анимированный лоадер** по центру страницы
3. **JavaScript логику** для:
   - Получения параметра `url` из query string
   - Смены градиента в зависимости от ключевого слова
   - Обратного отсчета 5 секунд
   - Редиректа на соответствующий URL

**Как использовать:**
- Откройте файл в браузере с параметром, например: `file.html?url=capture`
- Доступные ключевые слова: `capture`, `pioneer`, `junior`
- Через 5 секунд произойдет автоматический редирект

**Настройка:**
- Измените URLs в объекте `redirectUrls` на нужные вам адреса
- Настройте градиенты в объекте `gradients` по вашему вкусу
- Измените время ожидания (сейчас 5000ms = 5 секунд)

Все ресурсы самодостаточны и не требуют внешних зависимостей.


<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$current_url = $protocol . $host . $uri;
?>


    <?php
    $search_string = "example";
    if (strpos($current_url, $search_string) !== false) {
        echo "The URL contains '{$search_string}'.";
    } else {
        echo "The URL does not contain '{$search_string}'.";
    }
    ?>

