-- AI-GENERATED: Seed base roles (GitHub Copilot / ChatGPT), 2026-01-18

INSERT INTO roles (name)
VALUES ('member'), ('treasurer'), ('admin')
ON DUPLICATE KEY UPDATE name = VALUES(name);
