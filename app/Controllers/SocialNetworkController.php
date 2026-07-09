<?php

final class SocialNetworkController extends Controller
{
    public function index(): void
    {
        $items = Database::connection()->query('SELECT * FROM social_networks ORDER BY position ASC, id ASC')->fetchAll();
        render('socialnetworks/index', [
            'title' => 'Redes sociales',
            'items' => $items,
            'plataformas' => social_platforms(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validated();
        Database::connection()->prepare(
            'INSERT INTO social_networks (platform, platform_key, url, is_active, position) VALUES (?, ?, ?, ?, ?)'
        )->execute([$data['platform'], $data['platform_key'], $data['url'], $data['is_active'], $data['position']]);
        activity('Agrego red social ' . $data['platform'], 'social_networks');
        flash('status', 'Red social agregada.');
        Response::redirect('/social-networks');
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->find($id);
        $data = $this->validated();
        Database::connection()->prepare(
            'UPDATE social_networks SET platform = ?, platform_key = ?, url = ?, is_active = ?, position = ? WHERE id = ?'
        )->execute([$data['platform'], $data['platform_key'], $data['url'], $data['is_active'], $data['position'], $id]);
        activity('Actualizo red social ' . $data['platform'], 'social_networks');
        flash('status', 'Red social actualizada.');
        Response::redirect('/social-networks');
    }

    public function toggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('UPDATE social_networks SET is_active = ? WHERE id = ?')
            ->execute([(int)!$item['is_active'], $id]);
        activity('Cambio estado de red social ' . $item['platform'], 'social_networks');
        flash('status', 'Estado actualizado.');
        Response::redirect('/social-networks');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('DELETE FROM social_networks WHERE id = ?')->execute([$id]);
        activity('Elimino red social ' . $item['platform'], 'social_networks');
        flash('status', 'Red social eliminada.');
        Response::redirect('/social-networks');
    }

    private function validated(): array
    {
        $platformKey = trim((string)($_POST['platform_key'] ?? ''));
        $platforms = social_platforms();
        if (!isset($platforms[$platformKey])) {
            back_with_errors(['Selecciona una plataforma válida.'], $_POST);
        }

        $customName = trim((string)($_POST['custom_name'] ?? ''));
        $platform = $platformKey === 'otro'
            ? ($customName !== '' ? $customName : 'Otro')
            : $platforms[$platformKey]['label'];

        $url = trim((string)($_POST['url'] ?? ''));
        $position = (int)($_POST['position'] ?? 0);
        $active = !empty($_POST['is_active']) ? 1 : 0;

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            back_with_errors(['La URL debe ser un enlace válido (https://...).'], $_POST);
        }

        return [
            'platform' => $platform,
            'platform_key' => $platformKey,
            'url' => $url,
            'is_active' => $active,
            'position' => $position,
        ];
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM social_networks WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::abort(404, 'Red social no encontrada.');
        return $item;
    }
}
