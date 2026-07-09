<?php

final class IdentityController extends Controller
{
    public function lookup(): void
    {
        try {
            $data = ApiPeruIdentityService::lookup(
                (string)($_POST['document_type'] ?? ''),
                (string)($_POST['document_number'] ?? '')
            );

            $this->json(['ok' => true, 'data' => $data]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            app_log('identity_lookup_error', $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage()], 503);
        }
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
