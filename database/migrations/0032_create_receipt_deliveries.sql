-- Historial auditable de envios y reenvios de comprobantes de venta.
-- El correo admite ejecucion automatica o manual; WhatsApp es exclusivamente manual.
CREATE TABLE IF NOT EXISTS receipt_deliveries (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    sale_id          INT NOT NULL,
    receipt_file_id  INT NULL,
    channel          ENUM('email', 'whatsapp') NOT NULL,
    delivery_mode    ENUM('automatic', 'manual') NOT NULL,
    purpose          ENUM('initial', 'resend') NOT NULL DEFAULT 'initial',
    -- "prepared" significa que el administrador abrio/preparo el canal manual.
    -- No equivale a entrega confirmada (WhatsApp Web no devuelve acuse al sistema).
    status           ENUM('pending', 'prepared', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    recipient        VARCHAR(255) NULL,
    error_message    TEXT NULL,
    initiated_by     INT NULL,
    attempted_at     DATETIME NULL,
    sent_at          DATETIME NULL,
    failed_at        DATETIME NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_receipt_deliveries_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_receipt_deliveries_file
        FOREIGN KEY (receipt_file_id) REFERENCES files(id) ON DELETE SET NULL,
    CONSTRAINT fk_receipt_deliveries_user
        FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_receipt_deliveries_channel_mode
        CHECK (channel = 'email' OR delivery_mode = 'manual'),

    INDEX idx_receipt_deliveries_sale_created (sale_id, created_at),
    INDEX idx_receipt_deliveries_sale_channel_status (sale_id, channel, status),
    INDEX idx_receipt_deliveries_status_created (status, created_at),
    INDEX idx_receipt_deliveries_file (receipt_file_id),
    INDEX idx_receipt_deliveries_user (initiated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
