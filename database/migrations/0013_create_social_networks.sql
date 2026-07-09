CREATE TABLE IF NOT EXISTS social_networks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    platform     VARCHAR(40)  NOT NULL,
    url          VARCHAR(255) NOT NULL,
    icon_file_id INT          NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    position     INT          NOT NULL DEFAULT 0,
    CONSTRAINT fk_sn_icon FOREIGN KEY (icon_file_id) REFERENCES files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
