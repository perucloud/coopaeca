ALTER TABLE ubigeo
    ADD COLUMN department_reniec_code VARCHAR(2) NULL AFTER department_name,
    ADD COLUMN province_reniec_code VARCHAR(4) NULL AFTER province_name,
    ADD COLUMN district_reniec_code VARCHAR(6) NULL AFTER district_name;

ALTER TABLE ubigeo
    ADD INDEX idx_ubigeo_district_reniec (district_reniec_code);
