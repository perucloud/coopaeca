-- Fase 2 del webmail: borradores autoguardados para redactar/responder/reenviar
CREATE TABLE IF NOT EXISTS mail_drafts (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    account_id         INT            NOT NULL,
    to_addresses       VARCHAR(500)   NULL,
    cc_addresses       VARCHAR(500)   NULL,
    bcc_addresses      VARCHAR(500)   NULL,
    subject            VARCHAR(500)   NULL,
    body_html          LONGTEXT       NULL,
    in_reply_to_folder VARCHAR(190)   NULL,
    in_reply_to_uid    INT UNSIGNED   NULL,
    mode               ENUM('new','reply','reply_all','forward') NOT NULL DEFAULT 'new',
    created_at         TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME       NULL,
    CONSTRAINT fk_mail_drafts_account FOREIGN KEY (account_id) REFERENCES mail_accounts(id) ON DELETE CASCADE,
    INDEX idx_mail_drafts_account (account_id, updated_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adjuntos temporales subidos a un borrador antes de enviar
CREATE TABLE IF NOT EXISTS mail_draft_attachments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    draft_id      INT            NOT NULL,
    original_name VARCHAR(255)   NOT NULL,
    disk_path     VARCHAR(500)   NOT NULL,
    mime_type     VARCHAR(150)   NULL,
    size          INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mail_draft_attachments_draft FOREIGN KEY (draft_id) REFERENCES mail_drafts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
