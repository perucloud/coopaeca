ALTER TABLE services
    ADD COLUMN icon_name VARCHAR(60) NULL AFTER icon_file_id;

UPDATE services SET icon_name = 'package' WHERE icon_name IS NULL AND name LIKE '%Acopio%';
UPDATE services SET icon_name = 'activity' WHERE icon_name IS NULL AND name LIKE '%Ferment%';
UPDATE services SET icon_name = 'shield' WHERE icon_name IS NULL AND name LIKE '%Control%';
UPDATE services SET icon_name = 'layers' WHERE icon_name IS NULL AND name LIKE '%Transform%';
UPDATE services SET icon_name = 'share' WHERE icon_name IS NULL AND name LIKE '%Comercial%';
UPDATE services SET icon_name = 'users' WHERE icon_name IS NULL AND name LIKE '%Capacit%';
UPDATE services SET icon_name = 'layers' WHERE icon_name IS NULL;
