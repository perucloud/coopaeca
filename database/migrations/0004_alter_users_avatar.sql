ALTER TABLE users
    ADD COLUMN avatar_id INT NULL AFTER role_id,
    ADD CONSTRAINT fk_users_avatar FOREIGN KEY (avatar_id) REFERENCES files(id) ON DELETE SET NULL;
