<?php

final class AboutController extends Controller
{
    public function edit(): void
    {
        $settings = Database::connection()
            ->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'about_%'")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        render('about/edit', ['title' => 'Nosotros', 'settings' => $settings]);
    }

    public function update(): void
    {
        $allowed = [
            'about_title',
            'about_body',
            'about_values',
            'about_more_title',
            'about_more_body',
            'about_history_title',
            'about_history_body',
            'about_mission',
            'about_vision',
        ];
        $allowed = array_merge($allowed, array_map(
            static fn (string $key): string => $key . '_en',
            $allowed
        ));
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($allowed as $key) {
            $stmt->execute([$key, trim((string)($_POST[$key] ?? ''))]);
        }
        activity('Actualizo contenido Nosotros', 'about');
        flash('status', 'Datos de Nosotros guardados.');
        Response::redirect('/about');
    }
}
