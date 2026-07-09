<?php

final class Response
{
    public static function redirect(string $path): never
    {
        if (preg_match('#^https?://#i', $path)) {
            header('Location: ' . $path);
            exit;
        }

        header('Location: ' . app_path($path));
        exit;
    }

    public static function abort(int $code = 404, string $message = 'Pagina no encontrada'): never
    {
        http_response_code($code);
        view('layouts/error', [
            'title' => "Error {$code}",
            'code' => $code,
            'message' => $message,
        ]);
        exit;
    }
}
