CREATE TABLE IF NOT EXISTS galleries (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(180) NOT NULL,
    slug           VARCHAR(200) NOT NULL UNIQUE,
    description    TEXT         NULL,
    cover_image_id INT          NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    position       INT          NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gal_cover FOREIGN KEY (cover_image_id) REFERENCES files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT          NOT NULL,
    file_id    INT          NOT NULL,
    caption    VARCHAR(255) NULL,
    position   INT          NOT NULL DEFAULT 0,
    CONSTRAINT fk_gi_gallery FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,
    CONSTRAINT fk_gi_file    FOREIGN KEY (file_id)    REFERENCES files(id)     ON DELETE CASCADE,
    INDEX idx_gi_gallery (gallery_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
