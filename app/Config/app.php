<?php

return [
    'name' => env_value('APP_NAME', 'Dashboard Base'),
    'env' => env_value('APP_ENV', 'local'),
    'debug' => filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'base_url' => rtrim(env_value('APP_URL', ''), '/'),
    'timezone' => env_value('APP_TIMEZONE', 'America/Lima'),
    'session_name' => env_value('APP_SESSION_NAME', 'base_dashboard_session'),
    'secure_cookies' => filter_var(env_value('APP_SECURE_COOKIES', 'false'), FILTER_VALIDATE_BOOL),
];
