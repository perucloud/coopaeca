-- Clave de plataforma para resolver el icono de marca correcto (facebook, instagram, etc.)
-- sin depender de coincidir por texto libre contra la etiqueta visible.
ALTER TABLE social_networks
    ADD COLUMN platform_key VARCHAR(30) NULL AFTER platform;

UPDATE social_networks SET platform_key = 'facebook'  WHERE LOWER(platform) LIKE '%facebook%'  AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'instagram' WHERE LOWER(platform) LIKE '%instagram%' AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'linkedin'  WHERE LOWER(platform) LIKE '%linkedin%'  AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'twitter'   WHERE (LOWER(platform) LIKE '%twitter%' OR platform = 'X') AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'youtube'   WHERE LOWER(platform) LIKE '%youtube%'   AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'tiktok'    WHERE LOWER(platform) LIKE '%tiktok%'    AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'whatsapp'  WHERE LOWER(platform) LIKE '%whatsapp%'  AND platform_key IS NULL;
UPDATE social_networks SET platform_key = 'otro'      WHERE platform_key IS NULL;
