<?php

function landing_available_langs(): array
{
    return ['es', 'en'];
}

function landing_lang(): string
{
    $requested = strtolower(trim((string)($_GET['lang'] ?? '')));
    if (in_array($requested, landing_available_langs(), true)) {
        $_SESSION['landing_lang'] = $requested;
        return $requested;
    }

    $sessionLang = strtolower(trim((string)($_SESSION['landing_lang'] ?? '')));
    return in_array($sessionLang, landing_available_langs(), true) ? $sessionLang : 'es';
}

function lang_switch_url(string $lang): string
{
    $lang = in_array($lang, landing_available_langs(), true) ? $lang : 'es';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $query = [];
    parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $query);
    $query['lang'] = $lang;

    return url($path . '?' . http_build_query($query));
}

function lurl(string $path = ''): string
{
    $lang = landing_lang();
    $url = url($path);
    $separator = str_contains($url, '?') ? '&' : '?';

    if (str_contains($url, '#')) {
        [$base, $hash] = explode('#', $url, 2);
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . 'lang=' . rawurlencode($lang) . '#' . $hash;
    }

    return $url . $separator . 'lang=' . rawurlencode($lang);
}

function t(string $key, array $replace = []): string
{
    static $catalog = null;
    if ($catalog === null) {
        $catalog = require dirname(__DIR__) . '/Lang/messages.php';
    }

    $lang = landing_lang();
    $text = $catalog[$lang][$key] ?? $catalog['es'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string)$value, $text);
    }

    return $text;
}

function localized_value(array $row, string $field, ?string $lang = null): string
{
    $lang = $lang ?: landing_lang();
    $value = $row[$field] ?? '';

    if ($lang === 'en') {
        $translated = trim((string)($row[$field . '_en'] ?? ''));
        if ($translated !== '') {
            return $translated;
        }
    }

    return (string)($value ?? '');
}
