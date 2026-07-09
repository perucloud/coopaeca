CREATE TABLE IF NOT EXISTS contact_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(160) NOT NULL,
    phone          VARCHAR(40)  NULL,
    subject        VARCHAR(200) NULL,
    message        TEXT         NOT NULL,
    source_page_id INT          NULL,
    status         ENUM('new','read','answered','archived') NOT NULL DEFAULT 'new',
    ip             VARCHAR(45)  NULL,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cm_page FOREIGN KEY (source_page_id) REFERENCES pages(id) ON DELETE SET NULL,
    INDEX idx_cm_status  (status, created_at),
    INDEX idx_cm_email   (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
