-- AI-GENERATED: Seed default admin user (GitHub Copilot / ChatGPT), 2026-01-18

INSERT INTO users (name, email, password_hash, role_id)
SELECT 'Admin User', 'admin@local', '$2y$12$nVIB.2RJb3/zHKEALi7j3uDq9XKgeYat3gajHAe4Ic1VeRDeBdzMm', r.id
FROM roles r
WHERE r.name = 'admin'
ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role_id = VALUES(role_id);
