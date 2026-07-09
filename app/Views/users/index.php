<style>
    .input-with-icon { position: relative; display: flex; }
    .input-with-icon input { width: 100%; padding-right: 40px; }
    .input-icon-toggle {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 30px; height: 30px; border: none; background: transparent;
        color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
        border-radius: 6px;
    }
    .input-icon-toggle:hover { background: rgba(125,125,125,.12); color: var(--text); }
</style>

<section class="card">
    <div class="section-title row">
        <div>
            <h2>Usuarios</h2>
            <span>Gestiona accesos, roles y módulos permitidos.</span>
        </div>
        <?php if ($modulosAsignables): ?>
        <button type="button" class="button primary" id="btnNuevoUsuario">
            <?= icon('users') ?> Nuevo usuario
        </button>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Módulos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $item):
                $misModulos = $modulosPorUsuario[(int)$item['id']] ?? [];
                $etiquetas = array_map(fn ($m) => $modulos[$m] ?? $m, $misModulos);
            ?>
                <tr>
                    <td data-label="Nombre"><?= e($item['name']) ?></td>
                    <td data-label="Correo"><?= e($item['email']) ?></td>
                    <td data-label="Rol"><span class="badge muted"><?= e($item['role_name']) ?></span></td>
                    <td data-label="Módulos">
                        <?php if ($item['role_name'] === 'Super Administrador'): ?>
                            <span class="badge accent">Todos</span>
                        <?php elseif ($etiquetas): ?>
                            <span class="badge muted" title="<?= e(implode(', ', $etiquetas)) ?>"><?= count($etiquetas) ?> módulo(s)</span>
                        <?php else: ?>
                            <span class="text-muted">Sin módulos</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Estado"><span class="badge <?= $item['active'] ? 'ok' : 'off' ?>"><?= $item['active'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="actions">
                        <?php if ($modulosAsignables): ?>
                        <button type="button" class="button small btn-editar-usuario"
                                data-id="<?= (int)$item['id'] ?>"
                                data-name="<?= e($item['name']) ?>"
                                data-email="<?= e($item['email']) ?>"
                                data-role-id="<?= (int)$item['role_id'] ?>"
                                data-active="<?= (int)$item['active'] ?>"
                                data-self="<?= (int)$item['id'] === (int)user()['id'] ? '1' : '0' ?>"
                                data-modules="<?= e(implode(',', $misModulos)) ?>">
                            Editar
                        </button>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/users/toggle')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button class="button small ghost" type="submit"><?= $item['active'] ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($modulosAsignables): ?>
<div class="modal-overlay" id="userModal" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="userModalTitle">Nuevo usuario</h3>
            <button type="button" class="modal-close" id="userModalClose">&times;</button>
        </div>
        <form method="post" id="userForm" action="<?= e(url('/users/store')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="uf_id">
            <div class="modal-body">
                <div id="userSelfNotice" class="alert" style="display:none">No puedes cambiar tu propio rol, módulos o estado.</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="uf_name">Nombre y apellidos *</label>
                        <input type="text" id="uf_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="uf_email">Correo *</label>
                        <input type="email" id="uf_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="uf_role">Rol *</label>
                        <select id="uf_role" name="role_id" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="uf_password" id="uf_password_label">Contraseña *</label>
                        <div class="input-with-icon">
                            <input type="password" id="uf_password" name="password" minlength="8">
                            <button type="button" class="input-icon-toggle" id="uf_password_toggle" title="Mostrar contraseña">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px">
                    <label>Módulos permitidos</label>
                    <span class="text-muted" style="display:block;margin-bottom:8px">El usuario solo vera en su menu los modulos marcados aqui.</span>
                    <div class="form-grid-3" id="uf_modules">
                        <?php foreach ($modulosAsignables as $mod): ?>
                        <label class="check-box">
                            <input type="checkbox" name="modules[]" value="<?= e($mod) ?>" data-module-checkbox>
                            <span><?= e($modulos[$mod] ?? $mod) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <label class="check-box" style="margin-top:16px" id="uf_active_wrap">
                    <input type="checkbox" name="active" value="1" id="uf_active" checked>
                    <span>Usuario activo</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="button ghost" id="userModalCancel">Cancelar</button>
                <button type="submit" class="button primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('userModalTitle');
    const selfNotice = document.getElementById('userSelfNotice');
    const passwordInput = document.getElementById('uf_password');
    const passwordLabel = document.getElementById('uf_password_label');
    const roleSelect = document.getElementById('uf_role');
    const activeInput = document.getElementById('uf_active');
    const moduleChecks = document.querySelectorAll('[data-module-checkbox]');

    function openModal() { modal.style.display = 'flex'; }
    function closeModal() { modal.style.display = 'none'; }

    function resetForm() {
        form.reset();
        document.getElementById('uf_id').value = '';
        form.action = <?= json_encode(url('/users/store')) ?>;
        title.textContent = 'Nuevo usuario';
        passwordInput.required = true;
        passwordLabel.textContent = 'Contraseña *';
        selfNotice.style.display = 'none';
        moduleChecks.forEach(c => { c.checked = false; });
        activeInput.checked = true;
        passwordInput.type = 'password';
        passwordToggle.innerHTML = eyeIcon;
        passwordToggle.title = 'Mostrar contraseña';
    }

    document.getElementById('btnNuevoUsuario').addEventListener('click', function () {
        resetForm();
        openModal();
    });

    document.querySelectorAll('.btn-editar-usuario').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetForm();
            const d = btn.dataset;
            title.textContent = 'Editar: ' + d.name;
            form.action = <?= json_encode(url('/users/update')) ?>;
            document.getElementById('uf_id').value = d.id;
            document.getElementById('uf_name').value = d.name;
            document.getElementById('uf_email').value = d.email;
            roleSelect.value = d.roleId;
            passwordInput.required = false;
            passwordLabel.textContent = 'Nueva contraseña (dejar vacía para mantener)';
            activeInput.checked = d.active === '1';

            const misModulos = d.modules ? d.modules.split(',') : [];
            moduleChecks.forEach(c => { c.checked = misModulos.includes(c.value); });

            if (d.self === '1') {
                // El backend ignora rol/estado/modulos al editarse a uno mismo;
                // solo avisamos y evitamos dejar el rol vacio (rompe la validacion).
                selfNotice.style.display = 'block';
                if (!roleSelect.value && roleSelect.options.length > 1) {
                    roleSelect.selectedIndex = 1;
                }
            }

            openModal();
        });
    });

    document.getElementById('userModalClose').addEventListener('click', closeModal);
    document.getElementById('userModalCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    // Mostrar/ocultar contraseña
    const passwordToggle = document.getElementById('uf_password_toggle');
    const eyeIcon = <?= json_encode(icon('eye')) ?>;
    const eyeOffIcon = <?= json_encode(icon('eye-off')) ?>;
    passwordToggle.addEventListener('click', function () {
        const seVaAMostrar = passwordInput.type === 'password';
        passwordInput.type = seVaAMostrar ? 'text' : 'password';
        passwordToggle.innerHTML = seVaAMostrar ? eyeOffIcon : eyeIcon;
        passwordToggle.title = seVaAMostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
    });
})();
</script>
<?php endif; ?>
