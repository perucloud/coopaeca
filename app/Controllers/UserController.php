<?php

final class UserController extends Controller
{
    /** Modulos reales del sistema (los unicos con controlador y rutas propias). */
    private const MODULOS = [
        'posts'    => 'Noticias',
        'products' => 'Productos',
        'orders'   => 'Pedidos',
        'sales'    => 'Ventas',
        'inventory'=> 'Inventario',
        'payment_methods' => 'Metodos de pago',
        'services' => 'Servicios',
        'galleries'=> 'Galería',
        'social_networks' => 'Redes sociales',
        'pages'    => 'Nosotros',
        'contacts' => 'Contáctenos',
        'files'    => 'Media',
        'users'    => 'Usuarios',
        'settings' => 'Configuración',
    ];

    public function index(): void
    {
        $users = Database::connection()->query(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC'
        )->fetchAll();

        $modulosPorUsuario = [];
        foreach ($users as $u) {
            $modulosPorUsuario[(int)$u['id']] = $this->modulosDe((int)$u['id']);
        }

        render('users/index', [
            'title' => 'Usuarios',
            'users' => $users,
            'roles' => $this->roles(),
            'modulos' => self::MODULOS,
            'modulosAsignables' => $this->modulosAsignables(),
            'modulosPorUsuario' => $modulosPorUsuario,
        ]);
    }

    public function store(): void
    {
        $data = $this->validated(true);
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, active) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role_id'],
            $data['active'],
        ]);
        $id = (int)$pdo->lastInsertId();
        $this->sincronizarModulos($id, $data['modules']);

        activity('Creo usuario ' . $data['email'], 'users');
        flash('status', 'Usuario creado.');
        Response::redirect('/users');
    }

    public function update(): void
    {
        $id   = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        $this->ensureCanManageUser($item);
        $data = $this->validated(false, $id);

        $esUnoMismo = $id === (int)user()['id'];
        if ($esUnoMismo) {
            // Nadie edita su propio rol/estado/modulos: evita que se autoasigne mas acceso.
            $data['active'] = 1;
            $data['role_id'] = (int)$item['role_id'];
        }

        Database::connection()->prepare(
            'UPDATE users SET name = ?, email = ?, role_id = ?, active = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$data['name'], $data['email'], $data['role_id'], $data['active'], $id]);

        if ($data['password'] !== '') {
            Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($data['password'], PASSWORD_DEFAULT), $id]);
        }

        if (!$esUnoMismo) {
            $this->sincronizarModulos($id, $data['modules']);
        }

        activity('Actualizo usuario ' . $data['email'], 'users');
        flash('status', 'Usuario actualizado.');
        Response::redirect('/users');
    }

    public function toggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)user()['id']) {
            back_with_errors(['No puedes desactivar tu propio usuario.'], []);
        }
        $item = $this->find($id);
        $this->ensureCanManageUser($item);
        Database::connection()->prepare('UPDATE users SET active = ?, updated_at = NOW() WHERE id = ?')
            ->execute([(int)!$item['active'], $id]);
        activity('Cambio estado de usuario ' . $item['email'], 'users');
        Response::redirect('/users');
    }

    /** Guarda en user_module_access solo los modulos que el usuario actual tiene permitido otorgar. */
    private function sincronizarModulos(int $userId, array $modules): void
    {
        $permitidos = array_intersect($modules, $this->modulosAsignables());
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM user_module_access WHERE user_id = ?')->execute([$userId]);
        if ($permitidos) {
            $stmt = $pdo->prepare('INSERT INTO user_module_access (user_id, module) VALUES (?, ?)');
            foreach ($permitidos as $module) {
                $stmt->execute([$userId, $module]);
            }
        }
    }

    /** Modulos que el usuario en sesion puede otorgar a otros (superadmin: todos; resto: solo los suyos). */
    private function modulosAsignables(): array
    {
        if (is_superadmin()) {
            return array_keys(self::MODULOS);
        }
        return $this->modulosDe((int)user()['id']);
    }

    private function modulosDe(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT module FROM user_module_access WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'module');
    }

    private function roles(): array
    {
        $allowed = $this->assignableRoleSlugs();
        if (!$allowed) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = Database::connection()->prepare("SELECT * FROM roles WHERE slug IN ($placeholders) ORDER BY id");
        $stmt->execute($allowed);
        return $stmt->fetchAll();
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            Response::abort(404, 'Usuario no encontrado.');
        }
        return $item;
    }

    private function validated(bool $creating, int $ignoreId = 0): array
    {
        $name    = trim((string)($_POST['name'] ?? ''));
        $email   = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $roleId  = (int)($_POST['role_id'] ?? 0);
        $active  = !empty($_POST['active']) ? 1 : 0;
        $modules = array_values(array_intersect(
            array_map('strval', $_POST['modules'] ?? []),
            array_keys(self::MODULOS)
        ));

        $errors = [];
        if ($name === '') $errors[] = 'El nombre es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo invalido.';
        if ($creating && strlen($password) < 8) $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        if (!$creating && $password !== '' && strlen($password) < 8) $errors[] = 'La nueva contrasena debe tener al menos 8 caracteres.';
        if ($roleId <= 0) $errors[] = 'Selecciona un rol.';
        if ($roleId > 0 && !$this->canAssignRole($roleId)) {
            $errors[] = 'No puedes asignar ese rol con tu nivel de acceso.';
        }

        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $ignoreId]);
        if ($stmt->fetch()) $errors[] = 'El correo ya esta registrado.';

        if ($errors) {
            back_with_errors($errors, $_POST);
        }
        return compact('name', 'email', 'password', 'modules') + ['role_id' => $roleId, 'active' => $active];
    }

    private function assignableRoleSlugs(): array
    {
        return match (user()['role_slug'] ?? '') {
            'superadmin' => ['admin', 'editor'],
            'admin' => ['editor'],
            default => [],
        };
    }

    private function canAssignRole(int $roleId): bool
    {
        $allowed = $this->assignableRoleSlugs();
        if (!$allowed) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = Database::connection()->prepare("SELECT 1 FROM roles WHERE id = ? AND slug IN ($placeholders) LIMIT 1");
        $stmt->execute(array_merge([$roleId], $allowed));
        return (bool)$stmt->fetch();
    }

    private function ensureCanManageUser(array $item): void
    {
        $currentRole = user()['role_slug'] ?? '';
        $targetRole = $item['role_slug'] ?? '';

        if ($currentRole === 'superadmin' && $targetRole !== 'superadmin') {
            return;
        }

        if ($currentRole === 'admin' && $targetRole === 'editor') {
            return;
        }

        if ((int)$item['id'] === (int)user()['id']) {
            return;
        }

        Response::abort(403, 'No tienes permisos para gestionar este usuario.');
    }
}
