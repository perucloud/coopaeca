CREATE TABLE IF NOT EXISTS services (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    category_id       INT          NULL,
    name              VARCHAR(180) NOT NULL,
    slug              VARCHAR(200) NOT NULL UNIQUE,
    icon_file_id      INT          NULL,
    short_description VARCHAR(300) NULL,
    description       LONGTEXT     NULL,
    position          INT          NOT NULL DEFAULT 0,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    meta_title        VARCHAR(255) NULL,
    meta_description  VARCHAR(255) NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL,
    CONSTRAINT fk_srv_category FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_srv_icon     FOREIGN KEY (icon_file_id) REFERENCES files(id)      ON DELETE SET NULL,
    INDEX idx_services_active (is_active, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
