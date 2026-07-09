<?php

function app_log(string $level, string $message, array $context = []): void
{
    $dir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function activity(string $action, string $module = 'system'): void
{
    try {
        Database::connection()->prepare(
            'INSERT INTO activity_logs (user_id, action, module, ip, user_agent) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $module,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        app_log('error', 'No se pudo registrar actividad', ['error' => $e->getMessage()]);
    }
}
