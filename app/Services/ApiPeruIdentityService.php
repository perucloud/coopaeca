<?php

final class ApiPeruIdentityService
{
    private const DEFAULT_BASE_URL = 'https://apiperu.dev/api';

    public static function lookup(string $documentType, string $documentNumber): array
    {
        $type = strtoupper(trim($documentType));
        $number = preg_replace('/\D+/', '', $documentNumber) ?: '';

        if (!in_array($type, ['DNI', 'RUC'], true)) {
            throw new InvalidArgumentException('Tipo de documento no valido.');
        }
        if ($type === 'DNI' && strlen($number) !== 8) {
            throw new InvalidArgumentException('El DNI debe tener 8 digitos.');
        }
        if ($type === 'RUC' && strlen($number) !== 11) {
            throw new InvalidArgumentException('El RUC debe tener 11 digitos.');
        }

        $token = trim((string)env_value('API_PERU_TOKEN', ''));
        if ($token === '') {
            throw new RuntimeException('Consulta DNI/RUC no configurada.');
        }

        $payload = self::post($type === 'DNI' ? '/dni' : '/ruc', [
            strtolower($type) => $number,
        ], $token);

        if (empty($payload['success']) || !is_array($payload['data'] ?? null)) {
            throw new InvalidArgumentException((string)($payload['message'] ?? 'No se encontraron datos para el documento.'));
        }

        return $type === 'DNI'
            ? self::normalizeDni($payload['data'], $number)
            : self::normalizeRuc($payload['data'], $number);
    }

    private static function post(string $endpoint, array $params, string $token): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extension cURL no esta disponible en el servidor.');
        }

        $baseUrl = rtrim((string)env_value('API_PERU_BASE_URL', self::DEFAULT_BASE_URL), '/');
        $curl = curl_init($baseUrl . $endpoint);
        $verifySsl = filter_var(env_value('API_PERU_SSL_VERIFY', 'true'), FILTER_VALIDATE_BOOL);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $error !== '') {
            app_log('api_peru_error', 'Error de conexion con API Peru', ['status' => $status]);
            throw new RuntimeException('No se pudo conectar con el servicio de consulta.');
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            app_log('api_peru_error', 'Respuesta invalida de API Peru', ['status' => $status]);
            throw new RuntimeException('El servicio de consulta devolvio una respuesta invalida.');
        }

        if ($status >= 400) {
            throw new InvalidArgumentException((string)($decoded['message'] ?? 'No se pudo validar el documento.'));
        }

        return $decoded;
    }

    private static function normalizeDni(array $data, string $number): array
    {
        return [
            'document_type' => 'DNI',
            'document_number' => (string)($data['numero'] ?? $number),
            'customer_name' => (string)($data['nombre_completo'] ?? ''),
            'names' => (string)($data['nombres'] ?? ''),
            'paternal_last_name' => (string)($data['apellido_paterno'] ?? ''),
            'maternal_last_name' => (string)($data['apellido_materno'] ?? ''),
            'verification_code' => (string)($data['codigo_verificacion'] ?? ''),
        ];
    }

    private static function normalizeRuc(array $data, string $number): array
    {
        return [
            'document_type' => 'RUC',
            'document_number' => (string)($data['ruc'] ?? $number),
            'customer_name' => (string)($data['nombre_o_razon_social'] ?? ''),
            'status' => (string)($data['estado'] ?? ''),
            'condition' => (string)($data['condicion'] ?? ''),
            'address' => (string)($data['direccion'] ?? ''),
            'full_address' => (string)($data['direccion_completa'] ?? ''),
            'region' => (string)($data['departamento'] ?? ''),
            'province' => (string)($data['provincia'] ?? ''),
            'district' => (string)($data['distrito'] ?? ''),
            'ubigeo_sunat' => (string)($data['ubigeo_sunat'] ?? ''),
            'ubigeo' => is_array($data['ubigeo'] ?? null) ? $data['ubigeo'] : [],
        ];
    }
}
