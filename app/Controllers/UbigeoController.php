<?php

final class UbigeoController extends Controller
{
    public function departments(): void
    {
        $this->json(['ok' => true, 'items' => UbigeoService::departments()]);
    }

    public function provinces(): void
    {
        $this->json(['ok' => true, 'items' => UbigeoService::provinces((string)($_GET['department_code'] ?? ''))]);
    }

    public function districts(): void
    {
        $this->json(['ok' => true, 'items' => UbigeoService::districts((string)($_GET['province_code'] ?? ''))]);
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
