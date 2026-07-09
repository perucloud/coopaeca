<?php

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Wrapper de webklex/php-imap para el modulo de webmail.
 * Recibe una fila de mail_accounts (con password_encrypted) y expone
 * operaciones de solo lectura sobre el buzon IMAP.
 */
final class ImapService
{
    private array $account;
    private ?Client $client = null;

    public function __construct(array $account)
    {
        $this->account = $account;
    }

    /**
     * Prueba una conexion IMAP con credenciales en texto plano.
     * Lanza una excepcion si el servidor rechaza la conexion o el login.
     */
    public static function probar(string $host, int $port, string $email, string $password): void
    {
        $client = self::makeClient($host, $port, $email, $password);
        $client->connect();
        $client->disconnect();
    }

    private static function makeClient(string $host, int $port, string $email, string $password): Client
    {
        return (new ClientManager())->make([
            'host'          => $host,
            'port'          => $port,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => $email,
            'password'      => $password,
            'protocol'      => 'imap',
            'timeout'       => 20,
        ]);
    }

    /** Conexion perezosa: solo conecta la primera vez que se necesita. */
    private function client(): Client
    {
        if ($this->client === null) {
            $this->client = self::makeClient(
                (string)$this->account['imap_host'],
                (int)$this->account['imap_port'],
                (string)$this->account['email'],
                decrypt((string)$this->account['password_encrypted'])
            );
            $this->client->connect();
        }
        return $this->client;
    }

    public function desconectar(): void
    {
        if ($this->client !== null && $this->client->isConnected()) {
            $this->client->disconnect();
        }
        $this->client = null;
    }

    /**
     * Lista las carpetas del buzon con contadores.
     * @return array<int, array{path: string, name: string, total: int, unseen: int}>
     */
    public function carpetas(): array
    {
        $result = [];
        foreach ($this->client()->getFolders(false) as $folder) {
            $status = [];
            try {
                $status = $folder->status();
            } catch (Throwable) {
                // Algunas carpetas (p. ej. contenedores \Noselect) no soportan STATUS.
            }
            $result[] = [
                'path'   => (string)$folder->path,
                'name'   => (string)$folder->name,
                'total'  => (int)($status['messages'] ?? $status['MESSAGES'] ?? 0),
                'unseen' => (int)($status['unseen'] ?? $status['UNSEEN'] ?? 0),
            ];
        }

        // INBOX primero, el resto en orden alfabetico
        usort($result, function (array $a, array $b): int {
            $ai = strcasecmp($a['path'], 'INBOX') === 0 ? 0 : 1;
            $bi = strcasecmp($b['path'], 'INBOX') === 0 ? 0 : 1;
            return $ai <=> $bi ?: strcasecmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Lista cabeceras de una carpeta, paginadas y de mas reciente a mas antiguo.
     * No expone cuerpos ni adjuntos al llamador (cabecera() los descarta), pero
     * Webklex necesita descargar el cuerpo para poder resolver has_attachments,
     * por eso no se usa setFetchBody(false) aqui.
     * @return array<int, array> ver cabecera()
     */
    public function mensajes(string $folder, int $page = 1, int $perPage = 25): array
    {
        $messages = $this->folder($folder)->query()->all()
            ->setFetchOrderDesc()
            ->limit(max(1, $perPage), max(1, $page))
            ->get();

        $result = [];
        foreach ($messages as $message) {
            $result[] = $this->cabecera($message);
        }
        return $result;
    }

    /**
     * Cabeceras de mensajes con UID mayor al indicado (para sincronizacion incremental).
     */
    public function mensajesDesdeUid(string $folder, int $sinceUid): array
    {
        $query = $this->folder($folder)->query()->all();
        $messages = $sinceUid > 0 ? $query->getByUidGreater($sinceUid) : $query->setFetchOrderDesc()->limit(50)->get();

        $result = [];
        foreach ($messages as $message) {
            $result[] = $this->cabecera($message);
        }
        return $result;
    }

    /**
     * Mensaje completo por UID: cabecera + cuerpos + lista de adjuntos.
     */
    public function mensaje(string $folder, int $uid): array
    {
        $message = $this->mensajeImap($folder, $uid);

        $adjuntos = [];
        foreach ($message->getAttachments()->values() as $i => $attachment) {
            $adjuntos[] = [
                'index' => $i,
                'name'  => (string)($attachment->name ?: $attachment->filename ?: 'adjunto-' . $i),
                'mime'  => (string)($attachment->getMimeType() ?: 'application/octet-stream'),
                'size'  => (int)$attachment->size,
            ];
        }

        return $this->cabecera($message) + [
            'html'     => $message->getHTMLBody(),
            'text'     => $message->getTextBody(),
            'to'       => $this->direcciones($message, 'to'),
            'cc'       => $this->direcciones($message, 'cc'),
            'adjuntos' => $adjuntos,
        ];
    }

    /**
     * Devuelve un adjunto por UID + indice: [name, mime, content].
     */
    public function adjunto(string $folder, int $uid, int $index): array
    {
        $message = $this->mensajeImap($folder, $uid);
        $attachment = $message->getAttachments()->values()[$index] ?? null;
        if ($attachment === null) {
            throw new RuntimeException('El adjunto solicitado no existe.');
        }

        return [
            'name'    => (string)($attachment->name ?: $attachment->filename ?: 'adjunto'),
            'mime'    => (string)($attachment->getMimeType() ?: 'application/octet-stream'),
            'content' => (string)$attachment->content,
        ];
    }

    /** Marca un mensaje como leido o no leido. */
    public function marcarLeido(string $folder, int $uid, bool $leido = true): void
    {
        $message = $this->mensajeImap($folder, $uid);
        $leido ? $message->setFlag('Seen') : $message->unsetFlag('Seen');
    }

    /** Mueve un mensaje a otra carpeta (p. ej. Papelera). */
    public function mover(string $folder, int $uid, string $destino): void
    {
        $this->mensajeImap($folder, $uid)->move($destino, true);
    }

    /** Elimina un mensaje: lo mueve a la carpeta de papelera del servidor y expunge. */
    public function eliminar(string $folder, int $uid): void
    {
        $this->mensajeImap($folder, $uid)->delete(true, $this->carpetaPapelera(), true);
    }

    /**
     * Detecta la carpeta de papelera del proveedor (Trash / Papelera / Deleted Items).
     * Cae a 'INBOX.Trash' (esquema cPanel/Dovecot mas comun) si no encuentra ninguna.
     */
    public function carpetaPapelera(): string
    {
        foreach ($this->carpetas() as $f) {
            if (preg_match('/(trash|papelera|deleted)/i', $f['path'])) {
                return $f['path'];
            }
        }
        return 'INBOX.Trash';
    }

    /** Detecta la carpeta de enviados del proveedor. */
    public function carpetaEnviados(): string
    {
        foreach ($this->carpetas() as $f) {
            if (preg_match('/(sent|enviados)/i', $f['path'])) {
                return $f['path'];
            }
        }
        return 'INBOX.Sent';
    }

    /** Guarda una copia de un correo ya enviado (RFC 822 crudo) en la carpeta de Enviados. */
    public function guardarEnviado(string $rawMessage): void
    {
        $folder = $this->folder($this->carpetaEnviados());
        $folder->appendMessage($rawMessage, ['\\Seen']);
    }

    // ------------------------------------------------------------------

    private function folder(string $path): \Webklex\PHPIMAP\Folder
    {
        $folder = $this->client()->getFolderByPath($path);
        if ($folder === null) {
            throw new RuntimeException("La carpeta '{$path}' no existe en el buzon.");
        }
        return $folder;
    }

    private function mensajeImap(string $folder, int $uid): Message
    {
        try {
            return $this->folder($folder)->query()->getMessageByUid($uid);
        } catch (Throwable $e) {
            throw new RuntimeException('No se encontro el mensaje UID ' . $uid . ' en ' . $folder, 0, $e);
        }
    }

    /** Normaliza un mensaje Webklex a un array de cabecera plano. */
    private function cabecera(Message $message): array
    {
        $from = $message->from?->first();
        $date = null;
        try {
            $date = $message->date?->toDate()?->format('Y-m-d H:i:s');
        } catch (Throwable) {
            // Fecha ilegible en la cabecera: se deja nula.
        }

        return [
            'uid'             => (int)(string)$message->uid,
            'message_id'      => mb_substr((string)$message->message_id, 0, 255),
            'subject'         => mb_substr($this->decodeHeader(trim((string)$message->subject)), 0, 500),
            'from_email'      => mb_substr((string)($from->mail ?? ''), 0, 190),
            'from_name'       => mb_substr($this->decodeHeader(trim((string)($from->personal ?? ''))), 0, 190),
            'date'            => $date,
            'is_seen'         => $message->hasFlag('Seen') ? 1 : 0,
            'has_attachments' => $message->hasAttachments() ? 1 : 0,
        ];
    }

    /**
     * Decodifica encabezados MIME (=?UTF-8?B?...?=) que el servidor a veces
     * entrega sin decodificar. Es un no-op sobre texto ya plano.
     */
    private function decodeHeader(string $value): string
    {
        if ($value === '' || !str_contains($value, '=?')) {
            return $value;
        }
        $decoded = @iconv_mime_decode($value, 0, 'UTF-8');
        return $decoded !== false ? $decoded : $value;
    }

    /** @return array<int, string> direcciones formateadas "Nombre <correo>" */
    private function direcciones(Message $message, string $field): array
    {
        $result = [];
        $attribute = $message->get($field);
        if ($attribute === null) {
            return $result;
        }
        foreach ($attribute->all() as $address) {
            $mail = (string)($address->mail ?? '');
            $name = $this->decodeHeader(trim((string)($address->personal ?? '')));
            if ($mail !== '') {
                $result[] = $name !== '' ? $name . ' <' . $mail . '>' : $mail;
            }
        }
        return $result;
    }
}
