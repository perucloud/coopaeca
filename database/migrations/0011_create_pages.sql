CREATE TABLE IF NOT EXISTS pages (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(180) NOT NULL,
    slug             VARCHAR(200) NOT NULL UNIQUE,
    template         VARCHAR(60)  NOT NULL DEFAULT 'default',
    status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    author_id        INT          NULL,
    published_at     DATETIME     NULL,
    meta_title       VARCHAR(255) NULL,
    meta_description VARCHAR(255) NULL,
    meta_keywords    VARCHAR(255) NULL,
    og_image_id      INT          NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL,
    CONSTRAINT fk_pages_author   FOREIGN KEY (author_id)   REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pages_og_image FOREIGN KEY (og_image_id) REFERENCES files(id) ON DELETE SET NULL,
    INDEX idx_pages_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_blocks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    page_id      INT          NOT NULL,
    block_type   VARCHAR(60)  NOT NULL,
    position     INT          NOT NULL DEFAULT 0,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    content_json JSON         NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NULL,
    CONSTRAINT fk_pb_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_pb_page_pos (page_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
