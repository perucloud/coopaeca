<?php

final class ContactController extends Controller
{
    public function index(): void
    {
        // Reintento perezoso: cada vez que se abre la bandeja, se reintentan
        // en segundo plano las notificaciones pendientes/fallidas que ya
        // cumplieron su tiempo de espera. No requiere cron del servidor.
        ContactNotifier::reintentarPendientes();

        $items = Database::connection()->query('SELECT * FROM contact_messages ORDER BY id DESC')->fetchAll();

        $stats = [
            'total' => count($items),
            'nuevos' => count(array_filter($items, fn ($i) => $i['status'] === 'new')),
            'enviados' => count(array_filter($items, fn ($i) => $i['notify_status'] === 'sent')),
            'pendientes' => count(array_filter($items, fn ($i) => $i['notify_status'] !== 'sent')),
        ];

        render('contacts/index', ['title' => 'Contáctenos', 'items' => $items, 'stats' => $stats]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'read', ['new', 'read', 'answered', 'archived'], true) ? $_POST['status'] : 'read';
        Database::connection()->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $id]);
        activity('Actualizo mensaje de contacto #' . $id, 'contacts');
        flash('status', 'Mensaje actualizado.');
        Response::redirect('/contacts');
    }

    /** POST /contacts/retry — reintento manual e inmediato de la notificacion por correo */
    public function retry(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);

        $ok = ContactNotifier::intentarEnviar($item);
        activity('Reintento notificacion de contacto #' . $id, 'contacts');
        flash('status', $ok ? 'Notificacion enviada correctamente.' : 'No se pudo enviar la notificacion. Se reintentara mas tarde.');
        Response::redirect('/contacts');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        Database::connection()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        activity('Elimino mensaje de contacto #' . $id, 'contacts');
        flash('status', 'Mensaje eliminado.');
        Response::redirect('/contacts');
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            Response::abort(404, 'Mensaje no encontrado.');
        }
        return $item;
    }
}
