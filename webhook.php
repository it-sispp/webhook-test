<?php
// GitHub Webhook Handler
// Секретный ключ для проверки подлинности (установите свой)
$secret = 'your_secret_key_here';

// Логирование для отладки
$logFile = __DIR__ . '/webhook.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Получаем данные запроса
$payload = file_get_contents('php://input');
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    // Альтернативный способ получения заголовков
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headers[str_replace('_', '-', substr($key, 5))] = $value;
        }
    }
}

writeLog("Webhook received");

// Проверяем подпись GitHub (отключено для тестирования)
// if (isset($headers['X-Hub-Signature-256'])) {
//     $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
//     if (!hash_equals($signature, $headers['X-Hub-Signature-256'])) {
//         writeLog("Invalid signature");
//         http_response_code(403);
//         exit('Invalid signature');
//     }
// }

// Декодируем JSON данные
$data = json_decode($payload, true);

// Проверяем что это push в main ветку
if ($data['ref'] === 'refs/heads/main') {
    writeLog("Push to main branch detected");
    
    // Переходим в директорию репозитория (.git находится здесь)
    $repoPath = '/var/www/sispp/data/www/git-test.sinmo.ru/';
    chdir($repoPath);
    
    // Выполняем git pull
    $output = [];
    $returnVar = 0;
    
    exec('git pull origin main 2>&1', $output, $returnVar);
    
    $gitOutput = implode("\n", $output);
    writeLog("Git pull output: " . $gitOutput);
    
    if ($returnVar === 0) {
        // Копируем обновленные файлы в frontend/web
        exec('cp index.html /var/www/sispp/data/www/git-test.sinmo.ru/frontend/web/', $copyOutput);
        exec('cp style.css /var/www/sispp/data/www/git-test.sinmo.ru/frontend/web/', $copyOutput2);
        writeLog("Repository updated and files copied to frontend/web");
        echo "Repository updated successfully";
    } else {
        writeLog("Git pull failed: " . $gitOutput);
        http_response_code(500);
        echo "Git pull failed";
    }
} else {
    writeLog("Not a push to main branch");
    echo "Not a push to main branch";
}

http_response_code(200);
?> 