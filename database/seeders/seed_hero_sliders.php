<?php

declare(strict_types=1);

/**
 * Siembra los 3 slides originales del hero (imagenes de public/assets/img/hero
 * y textos ES/EN de app/Lang/messages.php) en el modulo Hero / Sliders.
 * Las imagenes pasan por el mismo pipeline de optimizacion (crop 1800x650,
 * WebP, variante mobile) que las subidas desde el dashboard.
 *
 * Uso (una sola vez): php database/seeders/seed_hero_sliders.php
 * Es idempotente: si ya existen slides del hero, no hace nada.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este seeder solo puede ejecutarse desde la linea de comandos.\n");
}

$root = dirname(__DIR__, 2);
require $root . '/app/Helpers/app.php';
require $root . '/app/Core/Database.php';
require $root . '/app/Services/ImageOptimizerService.php';
load_env_file($root . '/.env');

$pdo = Database::connection();

// Slider del hero (mismo criterio que SliderController::heroSliderId()).
$stmt = $pdo->prepare('SELECT id FROM sliders WHERE slug = ? LIMIT 1');
$stmt->execute(['hero']);
$sliderId = $stmt->fetchColumn();
if ($sliderId === false) {
    $pdo->prepare('INSERT INTO sliders (name, slug) VALUES (?, ?)')->execute(['Hero principal', 'hero']);
    $sliderId = $pdo->lastInsertId();
}
$sliderId = (int)$sliderId;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM slider_items WHERE slider_id = ?');
$stmt->execute([$sliderId]);
if ((int)$stmt->fetchColumn() > 0) {
    exit("Ya existen slides del hero en la base de datos; no se siembra nada.\n");
}

$catalog = require $root . '/app/Lang/messages.php';

// Los titulos del catalogo usan <br> para el salto de linea; en BD se guarda
// "\n" (el hero dinamico renderiza con nl2br). Los badges usan &bull;.
$titulo = static fn (string $t): string => str_ireplace(['<br />', '<br/>', '<br>'], "\n", $t);
$badge = static fn (string $b): string => html_entity_decode($b, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$slides = [
    ['image' => 'aereo.png', 'key' => 'hero.1'],
    ['image' => 'cacao1.png', 'key' => 'hero.2'],
    ['image' => 'cacao3.png', 'key' => 'hero.3'],
];

$service = new ImageOptimizerService();
$creados = [];

try {
    $pdo->beginTransaction();
    $insertFile = $pdo->prepare(
        'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL)'
    );
    $insertSlide = $pdo->prepare(
        'INSERT INTO slider_items (slider_id, image_id, title, title_en, subtitle, subtitle_en, badge, badge_en, position, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
    );

    foreach ($slides as $i => $slide) {
        $key = $slide['key'];
        $processed = $service->processHeroImageFromFile($root . '/public/assets/img/hero/' . $slide['image']);
        $creados[] = $processed['disk_path'];

        $tituloEs = $titulo((string)$catalog['es'][$key . '.title']);
        $insertFile->execute([
            $processed['disk_path'],
            $slide['image'],
            $processed['mime'],
            $processed['size_bytes'],
            $processed['width'],
            $processed['height'],
            str_replace("\n", ' ', $tituloEs),
        ]);
        $fileId = (int)$pdo->lastInsertId();

        $insertSlide->execute([
            $sliderId,
            $fileId,
            $tituloEs,
            $titulo((string)$catalog['en'][$key . '.title']),
            (string)$catalog['es'][$key . '.text'],
            (string)$catalog['en'][$key . '.text'],
            $badge((string)$catalog['es'][$key . '.badge']),
            $badge((string)$catalog['en'][$key . '.badge']),
            $i + 1,
        ]);

        echo 'Slide ' . ($i + 1) . ' creado: ' . str_replace("\n", ' ', $tituloEs) . ' (' . $processed['disk_path'] . ")\n";
    }

    $pdo->commit();
    echo "Listo: 3 slides del hero sembrados y activos.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    foreach ($creados as $diskPath) {
        $service->deleteHeroImage($diskPath);
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
