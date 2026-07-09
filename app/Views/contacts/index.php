<style>
    .contacts-head {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
        background: var(--panel); border: 1px solid var(--line); border-radius: 10px;
        padding: 22px; box-shadow: 0 10px 28px rgba(20, 32, 52, .05); margin-bottom: 16px;
    }
    .contacts-head h2 { margin: 4px 0 6px; font-size: 24px; }
    .contacts-head p { margin: 0; color: var(--muted); line-height: 1.55; }
    .contacts-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .contacts-stat {
        display: grid; grid-template-columns: 42px 1fr; grid-template-areas: "icon label" "icon value";
        align-items: center; gap: 2px 12px; background: var(--panel); border: 1px solid var(--line);
        border-radius: 10px; padding: 16px; box-shadow: 0 10px 26px rgba(20, 32, 52, .04);
    }
    .contacts-stat > .icon { grid-area: icon; width: 42px; height: 42px; padding: 10px; border-radius: 10px; }
    .contacts-stat span { grid-area: label; color: var(--muted); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
    .contacts-stat strong { grid-area: value; font-size: 20px; color: var(--text); }
    .contacts-stat.st-total > .icon { background: #eef4ff; color: var(--primary); }
    .contacts-stat.st-new > .icon { background: #eff6ff; color: #2563eb; }
    .contacts-stat.st-sent > .icon { background: #ecfdf5; color: #059669; }
    .contacts-stat.st-pending > .icon { background: #fffbeb; color: #d97706; }

    .msg-preview { max-width: 280px; color: var(--muted); }
    .badge.notify-sent { background: #ecfdf5; color: #059669; }
    .badge.notify-pending { background: #fffbeb; color: #b45309; }
    .badge.notify-failed { background: #fef2f2; color: #dc2626; cursor: help; }
    [data-theme="dark"] .badge.notify-sent { background: #052e1f; color: #34d399; }
    [data-theme="dark"] .badge.notify-pending { background: #422006; color: #fbbf24; }
    [data-theme="dark"] .badge.notify-failed { background: #450a0a; color: #f87171; }

    @media (max-width: 900px) { .contacts-stats-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<section class="contacts-head">
    <div>
        <span class="eyebrow">Bandeja</span>
        <h2>Contáctenos</h2>
        <p>Mensajes recibidos desde el formulario del landing page, con notificación automática por correo.</p>
    </div>
</section>

<section class="contacts-stats-grid">
    <article class="contacts-stat st-total">
        <?= icon('mail') ?>
        <span>Total</span>
        <strong><?= (int)$stats['total'] ?></strong>
    </article>
    <article class="contacts-stat st-new">
        <?= icon('bell') ?>
        <span>Nuevos</span>
        <strong><?= (int)$stats['nuevos'] ?></strong>
    </article>
    <article class="contacts-stat st-sent">
        <?= icon('check-circle') ?>
        <span>Notificados</span>
        <strong><?= (int)$stats['enviados'] ?></strong>
    </article>
    <article class="contacts-stat st-pending">
        <?= icon('refresh') ?>
        <span>Por notificar</span>
        <strong><?= (int)$stats['pendientes'] ?></strong>
    </article>
</section>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Contacto</th><th>Asunto</th><th>Mensaje</th><th>Estado</th><th>Notificación</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Contacto">
                        <strong><?= e($item['name']) ?></strong><br>
                        <span class="text-muted"><?= e($item['email']) ?><?= $item['phone'] ? ' · ' . e($item['phone']) : '' ?></span>
                    </td>
                    <td data-label="Asunto"><?= e($item['subject'] ?? '-') ?></td>
                    <td class="msg-preview" data-label="Mensaje"><?= e(strlen($item['message']) > 90 ? substr($item['message'], 0, 90) . '...' : $item['message']) ?></td>
                    <td data-label="Estado"><span class="badge <?= $item['status'] === 'new' ? 'ok' : 'muted' ?>"><?= e($item['status']) ?></span></td>
                    <td data-label="Notificación">
                        <?php if ($item['notify_status'] === 'sent'): ?>
                            <span class="badge notify-sent"><?= icon('check-circle') ?> Enviado</span>
                        <?php elseif ($item['notify_status'] === 'failed'): ?>
                            <span class="badge notify-failed" title="<?= e($item['notify_error'] ?? 'Error desconocido') ?>">Fallido (<?= (int)$item['notify_attempts'] ?>)</span>
                        <?php else: ?>
                            <span class="badge notify-pending">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Fecha"><?= e($item['created_at']) ?></td>
                    <td class="actions">
                        <form method="post" action="<?= e(url('/contacts/update')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach (['new', 'read', 'answered', 'archived'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php if ($item['notify_status'] !== 'sent'): ?>
                        <form method="post" action="<?= e(url('/contacts/retry')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button class="button small ghost" type="submit" title="Reintentar envio de notificacion"><?= icon('refresh') ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/contacts/delete')) ?>" data-confirm="¿Eliminar este mensaje?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button class="button small ghost" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="7" class="empty-state">
                    <div class="empty-icon"><?= icon('mail') ?></div>
                    <p>Sin mensajes registrados.</p>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
