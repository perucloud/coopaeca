<?php
/**
 * Importador de ubigeo Peru (region/provincia/distrito) desde CSVs de
 * https://github.com/jmcastagnetto/ubigeo-peru-aumentado
 *
 * Fuente local: database/seeders/data/ubigeo/{departamento,provincia,distrito}.csv
 *
 * Uso: php database/seeders/import_ubigeo.php
 */

require __DIR__ . '/../../app/bootstrap.php';

function read_csv(string $path): array
{
    $handle = fopen($path, 'r');
    if (!$handle) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    $header = fgetcsv($handle);
    $rows = [];
    while (($line = fgetcsv($handle)) !== false) {
        if (count($line) !== count($header)) {
            continue;
        }
        $rows[] = array_combine($header, $line);
    }
    fclose($handle);
    return $rows;
}

function clean(?string $value): ?string
{
    $value = trim((string)$value);
    return ($value === '' || strtoupper($value) === 'NA') ? null : $value;
}

$dataDir = __DIR__ . '/data/ubigeo';

$departamentos = read_csv($dataDir . '/departamento.csv');
$provincias = read_csv($dataDir . '/provincia.csv');
$distritos = read_csv($dataDir . '/distrito.csv');

$departmentByName = [];
foreach ($departamentos as $row) {
    $inei = clean($row['inei'] ?? null);
    if ($inei === null) {
        continue;
    }
    $departmentByName[$row['departamento']] = [
        'code' => substr($inei, 0, 2),
        'reniec' => ($reniec = clean($row['reniec'] ?? null)) !== null ? substr($reniec, 0, 2) : null,
    ];
}

$provinceByKey = [];
foreach ($provincias as $row) {
    $inei = clean($row['inei'] ?? null);
    if ($inei === null) {
        continue;
    }
    $key = $row['departamento'] . '|' . $row['provincia'];
    $provinceByKey[$key] = [
        'code' => substr($inei, 0, 4),
        'reniec' => ($reniec = clean($row['reniec'] ?? null)) !== null ? substr($reniec, 0, 4) : null,
    ];
}

$pdo = Database::connection();
$stmt = $pdo->prepare(
    'INSERT INTO ubigeo
        (department_code, department_name, department_reniec_code,
         province_code, province_name, province_reniec_code,
         district_code, district_name, district_reniec_code)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        department_code = VALUES(department_code),
        department_name = VALUES(department_name),
        department_reniec_code = VALUES(department_reniec_code),
        province_code = VALUES(province_code),
        province_name = VALUES(province_name),
        province_reniec_code = VALUES(province_reniec_code),
        district_name = VALUES(district_name),
        district_reniec_code = VALUES(district_reniec_code)'
);

$inserted = 0;
$skipped = [];

foreach ($distritos as $row) {
    $districtInei = clean($row['inei'] ?? null);
    $departmentName = $row['departamento'] ?? '';
    $provinceName = $row['provincia'] ?? '';
    $districtName = $row['distrito'] ?? '';

    if ($districtInei === null) {
        $skipped[] = "$departmentName / $provinceName / $districtName (sin codigo INEI)";
        continue;
    }

    $department = $departmentByName[$departmentName] ?? null;
    $province = $provinceByKey[$departmentName . '|' . $provinceName] ?? null;

    if (!$department || !$province) {
        $skipped[] = "$departmentName / $provinceName / $districtName (sin region/provincia asociada)";
        continue;
    }

    $districtReniec = clean($row['reniec'] ?? null);

    $stmt->execute([
        $department['code'],
        $departmentName,
        $department['reniec'],
        $province['code'],
        $provinceName,
        $province['reniec'],
        $districtInei,
        $districtName,
        $districtReniec,
    ]);
    $inserted++;
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM ubigeo')->fetchColumn();

echo "Distritos procesados: $inserted\n";
echo "Distritos omitidos: " . count($skipped) . "\n";
foreach ($skipped as $reason) {
    echo "  - $reason\n";
}
echo "Total de filas en tabla ubigeo: $total\n";
