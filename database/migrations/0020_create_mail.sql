-- Modulo de webmail (Fase 1: lectura)
-- Cuentas IMAP de cada usuario del dashboard
CREATE TABLE IF NOT EXISTS mail_accounts (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT            NOT NULL,
    email              VARCHAR(190)   NOT NULL,
    display_name       VARCHAR(120)   NULL,
    imap_host          VARCHAR(190)   NOT NULL DEFAULT 'coopaeca.org.pe',
    imap_port          SMALLINT UNSIGNED NOT NULL DEFAULT 993,
    smtp_host          VARCHAR(190)   NOT NULL DEFAULT 'coopaeca.org.pe',
    smtp_port          SMALLINT UNSIGNED NOT NULL DEFAULT 465,
    password_encrypted TEXT           NOT NULL,
    signature          TEXT           NULL,
    is_default         TINYINT(1)     NOT NULL DEFAULT 0,
    created_at         TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mail_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_mail_accounts_user_email (user_id, email),
    INDEX idx_mail_accounts_user (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache local de cabeceras para listar el buzon sin conectar a IMAP
CREATE TABLE IF NOT EXISTS mail_cache (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id      INT            NOT NULL,
    folder          VARCHAR(190)   NOT NULL,
    uid             INT UNSIGNED   NOT NULL,
    message_id      VARCHAR(255)   NULL,
    subject         VARCHAR(500)   NULL,
    from_email      VARCHAR(190)   NULL,
    from_name       VARCHAR(190)   NULL,
    preview         VARCHAR(300)   NULL,
    has_attachments TINYINT(1)     NOT NULL DEFAULT 0,
    is_seen         TINYINT(1)     NOT NULL DEFAULT 0,
    date            DATETIME       NULL,
    CONSTRAINT fk_mail_cache_account FOREIGN KEY (account_id) REFERENCES mail_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uq_mail_cache_message (account_id, folder, uid),
    INDEX idx_mail_cache_list (account_id, folder, date DESC),
    INDEX idx_mail_cache_seen (account_id, folder, is_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
