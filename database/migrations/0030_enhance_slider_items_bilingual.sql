-- Modulo HERO / SLIDERS administrable: textos bilingues y badge por slide.
-- El hero del landing usa titulo, texto (subtitle) y badge; se agregan las
-- variantes _en siguiendo el patron de 0025_add_bilingual_landing_content.
ALTER TABLE slider_items
    ADD COLUMN title_en VARCHAR(255) NULL AFTER title,
    ADD COLUMN subtitle_en VARCHAR(255) NULL AFTER subtitle,
    ADD COLUMN badge VARCHAR(255) NULL AFTER subtitle_en,
    ADD COLUMN badge_en VARCHAR(255) NULL AFTER badge,
    ADD COLUMN button_text_en VARCHAR(60) NULL AFTER button_text;

-- Slider unico del hero del landing (los items cuelgan de el).
INSERT IGNORE INTO sliders (id, name, slug) VALUES (1, 'Hero principal', 'hero');
