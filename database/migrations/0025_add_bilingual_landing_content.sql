ALTER TABLE products
    ADD COLUMN name_en VARCHAR(180) NULL AFTER name,
    ADD COLUMN short_description_en VARCHAR(300) NULL AFTER short_description,
    ADD COLUMN description_en LONGTEXT NULL AFTER description,
    ADD COLUMN origin_en VARCHAR(255) NULL AFTER origin,
    ADD COLUMN variety_en VARCHAR(255) NULL AFTER variety,
    ADD COLUMN fermentation_en VARCHAR(255) NULL AFTER fermentation,
    ADD COLUMN altitude_en VARCHAR(255) NULL AFTER altitude,
    ADD COLUMN certification_en VARCHAR(255) NULL AFTER certification,
    ADD COLUMN presentation_en VARCHAR(255) NULL AFTER presentation,
    ADD COLUMN meta_title_en VARCHAR(255) NULL AFTER meta_title,
    ADD COLUMN meta_description_en VARCHAR(255) NULL AFTER meta_description;

ALTER TABLE services
    ADD COLUMN name_en VARCHAR(180) NULL AFTER name,
    ADD COLUMN short_description_en VARCHAR(300) NULL AFTER short_description,
    ADD COLUMN description_en LONGTEXT NULL AFTER description,
    ADD COLUMN meta_title_en VARCHAR(255) NULL AFTER meta_title,
    ADD COLUMN meta_description_en VARCHAR(255) NULL AFTER meta_description;

ALTER TABLE posts
    ADD COLUMN title_en VARCHAR(200) NULL AFTER title,
    ADD COLUMN excerpt_en VARCHAR(300) NULL AFTER excerpt,
    ADD COLUMN content_en LONGTEXT NULL AFTER content,
    ADD COLUMN meta_title_en VARCHAR(255) NULL AFTER meta_title,
    ADD COLUMN meta_description_en VARCHAR(255) NULL AFTER meta_description,
    ADD COLUMN meta_keywords_en VARCHAR(255) NULL AFTER meta_keywords;
