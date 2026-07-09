CREATE TABLE IF NOT EXISTS posts (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    author_id         INT          NOT NULL,
    title             VARCHAR(200) NOT NULL,
    slug              VARCHAR(220) NOT NULL UNIQUE,
    excerpt           VARCHAR(300) NULL,
    content           LONGTEXT     NULL,
    featured_image_id INT          NULL,
    status            ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft',
    published_at      DATETIME     NULL,
    views_count       INT          NOT NULL DEFAULT 0,
    meta_title        VARCHAR(255) NULL,
    meta_description  VARCHAR(255) NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL,
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id)         REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_image  FOREIGN KEY (featured_image_id) REFERENCES files(id) ON DELETE SET NULL,
    INDEX idx_posts_status (status, published_at),
    INDEX idx_posts_slug   (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_category (
    post_id     BIGINT NOT NULL,
    category_id INT    NOT NULL,
    PRIMARY KEY (post_id, category_id),
    CONSTRAINT fk_postcat_post FOREIGN KEY (post_id)     REFERENCES posts(id)      ON DELETE CASCADE,
    CONSTRAINT fk_postcat_cat  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_tag (
    post_id BIGINT NOT NULL,
    tag_id  INT    NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    CONSTRAINT fk_posttag_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_posttag_tag  FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
