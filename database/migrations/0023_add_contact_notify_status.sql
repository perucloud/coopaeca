-- Notificacion por correo del formulario de contacto: permite reintentos
-- automaticos sin perder nunca el mensaje (ya guardado en esta misma fila).
ALTER TABLE contact_messages
    ADD COLUMN notify_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN notify_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER notify_status,
    ADD COLUMN notify_last_attempt_at DATETIME NULL AFTER notify_attempts,
    ADD COLUMN notify_sent_at DATETIME NULL AFTER notify_last_attempt_at,
    ADD COLUMN notify_error VARCHAR(255) NULL AFTER notify_sent_at,
    ADD INDEX idx_cm_notify (notify_status, notify_last_attempt_at);
