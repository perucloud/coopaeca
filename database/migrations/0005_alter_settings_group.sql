ALTER TABLE settings
    ADD COLUMN group_name VARCHAR(40) NULL AFTER setting_value;
