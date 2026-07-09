<?php

final class SettingsController extends Controller
{
    public function edit(): void
    {
        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        render('settings/edit', ['title' => 'Configuracion', 'settings' => $settings]);
    }

    public function update(): void
    {
        $allowed = ['app_name', 'cooperative_name', 'ruc', 'support_email', 'whatsapp_landing', 'whatsapp_products', 'map_tag', 'map_title', 'map_description', 'topbar_phone', 'topbar_email', 'topbar_address', 'site_title'];
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($allowed as $key) {
            $stmt->execute([$key, trim((string)($_POST[$key] ?? ''))]);
        }

        $mapInput = trim((string)($_POST['map_embed_html'] ?? ''));
        $mapEmbed = '';
        if ($mapInput !== '') {
            $mapEmbed = $this->sanitizeMapEmbed($mapInput);
            if ($mapEmbed === null) {
                back_with_errors(['El código del mapa debe ser un iframe de Google Maps valido (Google Maps > Compartir > Insertar un mapa).'], $_POST);
            }
        }
        $stmt->execute(['map_embed_html', $mapEmbed]);

        foreach (['header_logo' => 'header_logo_path', 'footer_logo' => 'footer_logo_path', 'favicon' => 'favicon_path'] as $input => $key) {
            $path = $this->storeLogo($input);
            if ($path !== null) {
                $stmt->execute([$key, $path]);
            }
        }

        activity('Actualizo configuracion', 'settings');
        flash('status', 'Configuracion guardada.');
        Response::redirect('/settings');
    }

    /**
     * Acepta unicamente el codigo de "Insertar un mapa" de Google Maps.
     * Reconstruye el iframe desde cero usando solo el src validado, para
     * descartar cualquier atributo ajeno (onload, script, etc.) que venga
     * pegado junto con el embed.
     */
    private function sanitizeMapEmbed(string $html): ?string
    {
        if (!preg_match('/<iframe\b[^>]*\ssrc\s*=\s*"([^"]+)"/i', $html, $m)) {
            return null;
        }

        $src = html_entity_decode($m[1], ENT_QUOTES);
        $host = parse_url($src, PHP_URL_HOST);
        if (!filter_var($src, FILTER_VALIDATE_URL) || !in_array($host, ['www.google.com', 'maps.google.com'], true)) {
            return null;
        }

        return '<iframe src="' . e($src) . '" width="100%" height="100%" style="border:0" '
            . 'allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
    }

    private function storeLogo(string $input): ?string
    {
        $file = $_FILES[$input] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            back_with_errors(['No se pudo subir uno de los archivos de marca.'], $_POST);
        }
        if ((int)$file['size'] > 3145728) {
            back_with_errors(['Los archivos no deben superar 3MB.'], $_POST);
        }

        $esFavicon = $input === 'favicon';
        $allowed = $esFavicon
            ? ['image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico', 'image/png' => 'png']
            : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            back_with_errors([$esFavicon ? 'El favicon debe ser ICO o PNG.' : 'Los logos deben ser JPG, PNG o WEBP.'], $_POST);
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/settings';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = $input . '-' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            back_with_errors(['No se pudo guardar uno de los archivos.'], $_POST);
        }

        [$width, $height] = $mime !== 'image/x-icon' && $mime !== 'image/vnd.microsoft.icon'
            ? (getimagesize($target) ?: [null, null])
            : [null, null];
        $etiquetas = ['header_logo' => 'Logo header', 'footer_logo' => 'Logo footer', 'favicon' => 'Favicon'];
        Database::connection()->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/settings/' . $name,
            basename((string)$file['name']),
            $mime,
            (int)$file['size'],
            $width,
            $height,
            $etiquetas[$input] ?? $input,
            user()['id'] ?? null,
        ]);

        return 'uploads/settings/' . $name;
    }
}
