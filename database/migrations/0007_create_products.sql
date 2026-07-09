CREATE TABLE IF NOT EXISTS products (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    sku               VARCHAR(60)    NULL UNIQUE,
    name              VARCHAR(180)   NOT NULL,
    slug              VARCHAR(200)   NOT NULL UNIQUE,
    short_description VARCHAR(300)   NULL,
    description       LONGTEXT       NULL,
    price             DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    sale_price        DECIMAL(10,2)  NULL,
    stock             INT            NULL,
    cover_image_id    INT            NULL,
    is_featured       TINYINT(1)     NOT NULL DEFAULT 0,
    status            ENUM('draft','published') NOT NULL DEFAULT 'draft',
    meta_title        VARCHAR(255)   NULL,
    meta_description  VARCHAR(255)   NULL,
    created_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME       NULL,
    CONSTRAINT fk_products_cover FOREIGN KEY (cover_image_id) REFERENCES files(id) ON DELETE SET NULL,
    INDEX idx_products_status   (status, is_featured),
    INDEX idx_products_slug     (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_category (
    product_id  INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (product_id, category_id),
    CONSTRAINT fk_pc_product  FOREIGN KEY (product_id)  REFERENCES products(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    file_id    INT          NOT NULL,
    position   INT          NOT NULL DEFAULT 0,
    is_cover   TINYINT(1)   NOT NULL DEFAULT 0,
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pi_file    FOREIGN KEY (file_id)    REFERENCES files(id)    ON DELETE CASCADE,
    INDEX idx_pi_product (product_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
