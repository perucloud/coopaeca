<?php

final class UbigeoService
{
    public static function departments(): array
    {
        return Database::connection()
            ->query('SELECT DISTINCT department_code AS code, department_name AS name FROM ubigeo ORDER BY department_name ASC')
            ->fetchAll();
    }

    public static function provinces(string $departmentCode): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT province_code AS code, province_name AS name
             FROM ubigeo
             WHERE department_code = ?
             ORDER BY province_name ASC'
        );
        $stmt->execute([self::digits($departmentCode, 2)]);
        return $stmt->fetchAll();
    }

    public static function districts(string $provinceCode): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT district_code AS code, district_name AS name
             FROM ubigeo
             WHERE province_code = ?
             ORDER BY district_name ASC'
        );
        $stmt->execute([self::digits($provinceCode, 4)]);
        return $stmt->fetchAll();
    }

    public static function coverageCount(): int
    {
        return (int)Database::connection()->query('SELECT COUNT(*) FROM ubigeo')->fetchColumn();
    }

    private static function digits(string $value, int $length): string
    {
        return substr(preg_replace('/\D+/', '', $value) ?: '', 0, $length);
    }
}
