# README

<!-- AI-GENERATED: Project overview and auth setup notes (GitHub Copilot / ChatGPT), 2026-01-18 -->

## Overview
This project is a lightweight VAIIČKO-style MVC PHP application with session-based authentication, role checks, and a treasury module as reference. Auth uses a DB-backed authenticator, prepared statements, and password hashing. Navbar reflects login state and server-side guards protect treasury routes.

## Quick start
```bash
# install dependencies (if any) and run in your PHP-capable environment
php -S localhost:8000 -t public
```
Then open http://localhost:8000/ in a browser.

## Auth credentials (seeded)
- Email: `admin@local`
- Password: `admin123`
(Seeds are in `docker/sql/010_seed_roles.sql` and `docker/sql/011_seed_admin_user.sql`; ensure they are applied to your DB.)

## Key paths
- Routes: `App/config/routes.php`
- Auth controller: `App/Controllers/AuthController.php`
- Login view: `App/Views/Auth/login.view.php`
- Layout (navbar): `App/Views/Layouts/root.layout.view.php`
- Auth implementation: `App/Auth/DbAuthenticator.php`, `App/Auth/UserIdentity.php`, `App/Repositories/UserRepository.php`

## Notes
- Use prepared statements for DB access (already in repositories).
- Passwords are hashed via `password_hash`; verification uses `password_verify`.
- Guards: `requireLogin()` / `requireRole()` in `Framework/Core/BaseController.php`.
