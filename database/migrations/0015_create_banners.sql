CREATE TABLE IF NOT EXISTS banners (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(150) NOT NULL,
    image_id   INT          NOT NULL,
    link_url   VARCHAR(255) NULL,
    zone       VARCHAR(40)  NOT NULL DEFAULT 'header',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    starts_at  DATETIME     NULL,
    ends_at    DATETIME     NULL,
    CONSTRAINT fk_ban_image FOREIGN KEY (image_id) REFERENCES files(id) ON DELETE CASCADE,
    INDEX idx_ban_zone (zone, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
