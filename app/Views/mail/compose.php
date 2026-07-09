<style>
    .compose-form { display: flex; flex-direction: column; gap: 12px; max-width: 900px; }
    .compose-row { display: flex; gap: 10px; align-items: center; }
    .compose-row label { width: 70px; flex-shrink: 0; opacity: .75; font-size: .9rem; }
    .compose-row input[type=text], .compose-row input[type=email] { flex: 1; }
    .compose-cc-toggle { font-size: .85rem; cursor: pointer; text-decoration: underline; opacity: .7; }
    #composeBody { min-height: 320px; border: 1px solid rgba(125,125,125,.3); border-radius: 8px; padding: 12px; background: #fff; color: #111; overflow-y: auto; }
    .compose-attachments { display: flex; flex-wrap: wrap; gap: 8px; }
    .compose-attachment-chip { display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 20px; background: rgba(125,125,125,.15); font-size: .85rem; }
    .compose-attachment-chip button { border: none; background: none; cursor: pointer; opacity: .6; line-height: 1; }
    .compose-attachment-chip button:hover { opacity: 1; }
    .compose-status { font-size: .8rem; opacity: .6; }
    .compose-actions { display: flex; gap: 10px; align-items: center; }
</style>

<section class="card">
    <div class="section-title row">
        <div>
            <h2>Redactar correo</h2>
            <span><?= e($cuenta['email']) ?></span>
        </div>
        <a class="button" href="<?= e(url('/dashboard/mail?account=' . (int)$cuenta['id'])) ?>">&larr; Volver a la bandeja</a>
    </div>

    <form class="compose-form" id="composeForm" method="post" action="<?= e(url('/dashboard/mail/compose/send')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="account" value="<?= (int)$cuenta['id'] ?>">
        <input type="hidden" name="draft_id" id="draftId" value="<?= (int)($borrador['id'] ?? 0) ?>">
        <input type="hidden" name="mode" value="<?= e($prefill['mode']) ?>">
        <input type="hidden" name="in_reply_to_folder" value="<?= e((string)($prefill['in_reply_to_folder'] ?? '')) ?>">
        <input type="hidden" name="in_reply_to_uid" value="<?= e((string)($prefill['in_reply_to_uid'] ?? '')) ?>">

        <div class="compose-row">
            <label for="to">Para</label>
            <input type="text" id="to" name="to" value="<?= e($prefill['to']) ?>" placeholder="correo@ejemplo.com, otro@ejemplo.com" required>
            <span class="compose-cc-toggle" id="ccToggle">CC/CCO</span>
        </div>
        <div class="compose-row" id="ccRow" style="<?= $prefill['cc'] ? '' : 'display:none' ?>">
            <label for="cc">CC</label>
            <input type="text" id="cc" name="cc" value="<?= e($prefill['cc']) ?>">
        </div>
        <div class="compose-row" id="bccRow" style="<?= $prefill['bcc'] ? '' : 'display:none' ?>">
            <label for="bcc">CCO</label>
            <input type="text" id="bcc" name="bcc" value="<?= e($prefill['bcc']) ?>">
        </div>
        <div class="compose-row">
            <label for="subject">Asunto</label>
            <input type="text" id="subject" name="subject" value="<?= e($prefill['subject']) ?>">
        </div>

        <div id="composeBody" contenteditable="true"><?= $prefill['body'] /* contenido propio (citado) o de un borrador ya sanitizado al leerse */ ?></div>
        <textarea id="bodyHidden" style="display:none"></textarea>

        <div class="compose-attachments" id="attachmentsList">
            <?php foreach ($adjuntos as $a): ?>
            <span class="compose-attachment-chip" data-id="<?= (int)$a['id'] ?>">
                <?= icon('paperclip') ?> <?= e($a['original_name']) ?>
                <button type="button" class="attachment-remove" data-id="<?= (int)$a['id'] ?>" title="Quitar">&times;</button>
            </span>
            <?php endforeach; ?>
        </div>

        <div class="compose-row">
            <label for="fileInput" class="button ghost" style="cursor:pointer">
                <?= icon('paperclip') ?> Adjuntar archivo
            </label>
            <input type="file" id="fileInput" style="display:none" multiple>
            <span class="compose-status" id="composeStatus">&nbsp;</span>
        </div>

        <div class="compose-actions">
            <button class="button primary" type="submit"><?= icon('mail') ?> Enviar</button>
            <button class="button ghost" type="button" id="discardBtn">Descartar</button>
        </div>
    </form>
</section>

<script>
(function () {
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    var accountId = <?= (int)$cuenta['id'] ?>;
    var draftIdInput = document.getElementById('draftId');
    var statusEl = document.getElementById('composeStatus');
    var form = document.getElementById('composeForm');
    var body = document.getElementById('composeBody');
    var bodyHidden = document.getElementById('bodyHidden');

    function draftId() { return parseInt(draftIdInput.value || '0', 10); }

    function post(url, params) {
        params.set('_csrf', csrf);
        params.set('account', accountId);
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
        }).then(function (r) { return r.json(); });
    }

    // Autoguardado de borrador: cambios en campos de texto, con debounce
    var timer = null;
    function programarAutoguardado() {
        statusEl.textContent = 'Escribiendo...';
        clearTimeout(timer);
        timer = setTimeout(guardarBorrador, 1200);
    }

    function guardarBorrador() {
        var params = new URLSearchParams();
        params.set('draft_id', draftId());
        params.set('to', document.getElementById('to').value);
        params.set('cc', document.getElementById('cc').value);
        params.set('bcc', document.getElementById('bcc').value);
        params.set('subject', document.getElementById('subject').value);
        params.set('body', body.innerHTML);
        params.set('mode', form.mode.value);
        params.set('in_reply_to_folder', form.in_reply_to_folder.value);
        params.set('in_reply_to_uid', form.in_reply_to_uid.value);

        post(<?= json_encode(url('/dashboard/mail/compose/draft')) ?>, params).then(function (data) {
            if (data.ok) {
                draftIdInput.value = data.draft_id;
                statusEl.textContent = 'Borrador guardado';
            } else {
                statusEl.textContent = 'No se pudo guardar el borrador';
            }
        }).catch(function () { statusEl.textContent = 'Sin conexion, no se guardo el borrador'; });
    }

    ['to', 'cc', 'bcc', 'subject'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', programarAutoguardado);
    });
    body.addEventListener('input', programarAutoguardado);

    document.getElementById('ccToggle').addEventListener('click', function () {
        document.getElementById('ccRow').style.display = '';
        document.getElementById('bccRow').style.display = '';
    });

    // Subida de adjuntos: cada archivo se sube de inmediato y queda ligado al borrador
    document.getElementById('fileInput').addEventListener('change', function (e) {
        Array.prototype.forEach.call(e.target.files, function (file) {
            var data = new FormData();
            data.append('_csrf', csrf);
            data.append('account', accountId);
            data.append('draft_id', draftId());
            data.append('mode', form.mode.value);
            data.append('file', file);

            statusEl.textContent = 'Subiendo ' + file.name + '...';
            fetch(<?= json_encode(url('/dashboard/mail/compose/attachment')) ?>, {
                method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: data,
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (!res.ok) { statusEl.textContent = res.error || 'Error al subir adjunto'; return; }
                draftIdInput.value = res.draft_id;
                statusEl.textContent = 'Adjunto agregado';
                var chip = document.createElement('span');
                chip.className = 'compose-attachment-chip';
                chip.dataset.id = res.attachment.id;
                chip.innerHTML = <?= json_encode(icon('paperclip')) ?> + ' ' + res.attachment.name +
                    ' <button type="button" class="attachment-remove" data-id="' + res.attachment.id + '">&times;</button>';
                document.getElementById('attachmentsList').appendChild(chip);
            }).catch(function () { statusEl.textContent = 'Error de red al subir adjunto'; });
        });
        e.target.value = '';
    });

    document.getElementById('attachmentsList').addEventListener('click', function (e) {
        if (!e.target.classList.contains('attachment-remove')) return;
        var id = e.target.dataset.id;
        var params = new URLSearchParams();
        params.set('attachment_id', id);
        post(<?= json_encode(url('/dashboard/mail/compose/attachment/delete')) ?>, params).then(function () {
            e.target.closest('.compose-attachment-chip').remove();
        });
    });

    document.getElementById('discardBtn').addEventListener('click', function () {
        if (!confirm('¿Descartar este borrador?')) return;
        var params = new URLSearchParams();
        params.set('draft_id', draftId());
        var f = document.createElement('form');
        f.method = 'post';
        f.action = <?= json_encode(url('/dashboard/mail/compose/discard')) ?>;
        f.innerHTML = '<input type="hidden" name="_csrf" value="' + csrf + '">' +
            '<input type="hidden" name="draft_id" value="' + draftId() + '">' +
            '<input type="hidden" name="account" value="' + accountId + '">';
        document.body.appendChild(f);
        f.submit();
    });

    form.addEventListener('submit', function () {
        bodyHidden.value = body.innerHTML;
        bodyHidden.name = 'body';
    });
})();
</script>
