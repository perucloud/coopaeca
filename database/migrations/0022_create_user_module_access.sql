-- Permisos por usuario (independientes del rol): la presencia de una fila
-- (user_id, module) da acceso completo (ver/crear/editar/eliminar) a ese
-- modulo. El rol (admin/editor) ya no otorga permisos por si mismo, solo
-- define jerarquia de quien puede gestionar a quien.
CREATE TABLE IF NOT EXISTS user_module_access (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    module     VARCHAR(50)  NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_module_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_module_access (user_id, module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
