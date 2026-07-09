CREATE TABLE IF NOT EXISTS page_views (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    page_id    INT          NULL,
    url        VARCHAR(255) NOT NULL,
    referrer   VARCHAR(255) NULL,
    ip_hash    CHAR(64)     NULL,
    user_agent VARCHAR(255) NULL,
    visited_at DATETIME     NOT NULL,
    CONSTRAINT fk_pv_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
    INDEX idx_pv_visited  (visited_at),
    INDEX idx_pv_page_day (page_id, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
