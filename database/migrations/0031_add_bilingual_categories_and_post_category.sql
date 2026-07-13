-- Completa el patrón _en existente sin alterar slugs, IDs ni relaciones.
ALTER TABLE categories
    ADD COLUMN name_en VARCHAR(120) NULL AFTER name;

ALTER TABLE posts
    ADD COLUMN category_en VARCHAR(100) NULL AFTER category;
