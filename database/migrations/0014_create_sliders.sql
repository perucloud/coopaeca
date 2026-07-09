CREATE TABLE IF NOT EXISTS sliders (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS slider_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slider_id   INT          NOT NULL,
    image_id    INT          NOT NULL,
    title       VARCHAR(255) NULL,
    subtitle    VARCHAR(255) NULL,
    button_text VARCHAR(60)  NULL,
    button_url  VARCHAR(255) NULL,
    position    INT          NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    starts_at   DATETIME     NULL,
    ends_at     DATETIME     NULL,
    CONSTRAINT fk_si_slider FOREIGN KEY (slider_id) REFERENCES sliders(id) ON DELETE CASCADE,
    CONSTRAINT fk_si_image  FOREIGN KEY (image_id)  REFERENCES files(id)   ON DELETE CASCADE,
    INDEX idx_si_slider_pos (slider_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
