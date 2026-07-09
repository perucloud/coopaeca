INSERT INTO roles (id, name, slug) VALUES
(1, 'Super Administrador', 'superadmin'),
(2, 'Administrador', 'admin'),
(3, 'Editor', 'editor')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
slug = VALUES(slug);

UPDATE users
SET
    name = 'Superadmin COOPAECA',
    email = 'admin@ccopaeca.org.pe',
    password_hash = '$2y$10$5uYyJMlDLefOeMOOyzVRO.oJuQ8oyKDBTnpBPIFIc2.wGQd784qbG',
    role_id = 1,
    active = 1,
    updated_at = NOW()
WHERE email = 'admin@example.com';

INSERT INTO users (name, email, password_hash, role_id, active)
SELECT
    'Superadmin COOPAECA',
    'admin@ccopaeca.org.pe',
    '$2y$10$5uYyJMlDLefOeMOOyzVRO.oJuQ8oyKDBTnpBPIFIc2.wGQd784qbG',
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@ccopaeca.org.pe'
);

UPDATE users
SET
    name = 'Superadmin COOPAECA',
    password_hash = '$2y$10$5uYyJMlDLefOeMOOyzVRO.oJuQ8oyKDBTnpBPIFIc2.wGQd784qbG',
    role_id = 1,
    active = 1,
    updated_at = NOW()
WHERE email = 'admin@ccopaeca.org.pe';
