CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('product','blog') NOT NULL,
    parent_id   INT          NULL,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL,
    description TEXT         NULL,
    image_id    INT          NULL,
    position    INT          NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_cat_image  FOREIGN KEY (image_id)  REFERENCES files(id)      ON DELETE SET NULL,
    UNIQUE  uq_cat_type_slug (type, slug),
    INDEX   idx_cat_type_parent (type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
