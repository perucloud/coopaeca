<?php

abstract class Controller
{
    protected function request(): Request
    {
        return Request::capture();
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request()->input($key, $default);
    }

    protected function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        render($template, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function back(): never
    {
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
