# AI_COOKBOOK.md (ONE MONOLITHIC FILE)
## ESN UNIZA – VAII Semester Project Cookbook (VAIIČKO + MariaDB + PhpStorm + GitHub Copilot)

Author: Jozef Jelšík  
Course: VAII – Development of Applications for Internet and Intranet  
Framework: VAIIČKO (server-rendered MVC)  
Database: MariaDB  
IDE: PhpStorm + GitHub Copilot Premium  
Delivery mode: Deadline / ship-fast (automated tests skipped)

===============================================================================

## 0) PURPOSE: why this file exists

This is the **single source of truth** for how we implement this project.
It is written as a **cookbook**: strict rules + concrete patterns + step-by-step recipes +
“copy-paste prompts” for AI.

Goal: ship a project that is:
- compliant with VAII requirements,
- consistent with VAIIČKO patterns,
- secure enough to defend (authorization, validation, SQL injection protection),
- implementable quickly with AI assistance,
- understandable during defense.

-------------------------------------------------------------------------------

## 0.1 Mandatory footer to add to EVERY AI prompt

Always append this to Copilot Chat / any AI agent prompt:

Follow the architecture, rules, patterns, and constraints defined in `docs/AI_COOKBOOK.md`.
Do not introduce new frameworks or dependencies.
Use the existing Treasury module as the reference for style and structure.
If you generate code, add the required AI-GENERATED comment header.

===============================================================================

## 1) VAII HARD REQUIREMENTS (CHECKLIST)

This project MUST satisfy all of the following:

1) Architecture and pages
- [ ] MVC (or equivalent separation of layers)
- [ ] Minimum 5 dynamic pages
- [ ] A section accessible only after login
- [ ] README.md with installation + run instructions
- [ ] Git repository shows continuous progress (multiple commits)

2) Database and CRUD
- [ ] Minimum 3 meaningful DB entities (excluding `users`)
- [ ] At least one relationship 1:N or M:N
- [ ] CRUD for at least 2 entities (Create/Read/Update/Delete in GUI)

3) Frontend
- [ ] Minimum 50 lines of custom JavaScript (meaningful, used)
- [ ] Minimum 20 custom CSS rules (not Bootstrap)
- [ ] Responsive design

4) Features
- [ ] 2 meaningful AJAX interactions
- [ ] File upload + file management (delete/remove + storage rules)

5) Security and quality
- [ ] Server-side validation for all inputs
- [ ] Client-side validation (HTML5/JS)
- [ ] SQL injection protection (prepared statements)
- [ ] Passwords never stored as plaintext
- [ ] Role-based authorization (server-side)

6) AI usage (mandatory)
- [ ] You must understand everything you submit (defense)
- [ ] AI-generated parts must be clearly marked by comments
- [ ] External code sources must be referenced in comments

===============================================================================

## 2) ABSOLUTE RULES FOR AI USAGE (NON-NEGOTIABLE)

## 2.1 Mandatory AI code labeling (required everywhere)

Every AI-generated or AI-assisted portion must be explicitly marked.

A) PHP (new file OR above generated block)
// AI-GENERATED: <short description> (GitHub Copilot / ChatGPT), YYYY-MM-DD

B) JS / CSS / SQL (new file OR above generated block)
/* AI-GENERATED: <short description> (GitHub Copilot / ChatGPT), YYYY-MM-DD */

If you copy code from anywhere external, add:
// SOURCE: <link or short source description>

## 2.2 AI must NOT change project architecture

AI must NOT:
- introduce Laravel/Symfony patterns,
- add new frameworks or dependencies,
- redesign routing,
- introduce new DB access layers,
- refactor the whole app.

AI must:
- follow VAIIČKO MVC patterns,
- copy existing project style,
- use Treasury module as the reference.

## 2.3 Security is ALWAYS enforced server-side

UI hiding is not security. Every data-changing endpoint must:
- requireLogin
- requireRole (when needed)
- validate inputs server-side
- use prepared statements
- protect file uploads with allowlist rules

===============================================================================

## 3) PROJECT ARCHITECTURE (VAIIČKO MVC)

## 3.1 Layers

Controllers:
- handle requests (GET/POST),
- enforce authorization,
- validate inputs,
- call repositories,
- render views or return JSON for AJAX,
- set flash messages.

Repositories:
- contain only DB logic,
- use prepared statements,
- return arrays/objects for controllers,
- no HTML, no redirects, no $_POST/$_GET reads.

Views:
- render HTML,
- show forms/lists/detail pages,
- show validation errors + flash messages,
- minimal PHP logic (loops/conditions),
- never query DB directly.

Database runtime notes:
- The database container runs only when Docker profile `localdb` is enabled.
- Development startup command:
  docker compose --profile localdb up -d --build
- Web-only startup (without DB):
  docker compose up -d


## 3.2 Golden reference module

The Treasury module already exists and is our “golden pattern”.
All new modules must mimic Treasury:
- controller action style,
- repository style,
- views + layout usage,
- error rendering,
- flash messages.

===============================================================================

## 4) ROLES & AUTHORIZATION MODEL

## 4.1 Roles (minimum)

- member: read access, can propose transactions, can view knowledge base, can view ESNcards list
- treasurer: approve/reject transactions, manage ESNcards (CRUD)
- admin: manage knowledge base (articles + attachments), upload/delete attachments

## 4.2 Authorization rules (server-side)

Rules per module:

Treasury:
- member: view list, create proposal
- treasurer/admin: approve/reject, balance adjustments, close period

ESNcards:
- member: view list
- treasurer/admin: create/edit/assign/delete

Knowledge Base (Semester Manual):
- member: view list + article details + download attachments
- admin: create/edit/delete articles + upload/delete attachments

Implementation rule:
- Authorization must be checked in controller actions.
- AJAX endpoints must be protected too.

===============================================================================

## 5) DATABASE (MariaDB) — ENTITY PLAN

Important note:
- The database schema is fully implemented.
- Treasury, ESNcards, and Knowledge Base tables already exist.
- This cookbook describes how the existing schema is used and extended at code level.

## 5.1 Entity list (core scope)

Existing (Treasury):
- transactions (already implemented in code + DB)
- optional: periods/cashbox (if exists)

New (must implement):
- esncards
- knowledge_articles
- attachments

These guarantee:
- >= 3 meaningful entities excluding users,
- 1:N relationship (knowledge_articles -> attachments),
- CRUD for >= 2 entities (esncards + knowledge_articles),
- file management (attachments).

## 5.2  Implemented schemas (authoritative)

Adjust names to match project naming conventions.

A) esncards
- id INT PK AUTO_INCREMENT
- card_number VARCHAR(64) NOT NULL UNIQUE
- status ENUM('free','assigned','blocked') NOT NULL DEFAULT 'free'
- assigned_to_name VARCHAR(255) NULL
- assigned_to_email VARCHAR(255) NULL
- assigned_at DATETIME NULL
- created_at DATETIME NOT NULL
- updated_at DATETIME NOT NULL

B) knowledge_articles
- id INT PK AUTO_INCREMENT
- title VARCHAR(255) NOT NULL
- category VARCHAR(255) NULL
- difficulty ENUM('easy','medium','hard') NULL
- content TEXT NOT NULL
- created_by_user_id INT NULL
- created_at DATETIME NOT NULL
- updated_at DATETIME NOT NULL

C) attachments 

id INT PK AUTO_INCREMENT
article_id INT NOT NULL (FK -> knowledge_articles.id ON DELETE CASCADE)
file_path VARCHAR(512) NULL      -- stored file path relative to public/, e.g. "uploads/manual/uuid.pdf"
url VARCHAR(512) NULL            -- optional external link
description VARCHAR(255) NULL    -- optional label
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

Indexes:
attachments(article_id)

Constraint:
(file_path IS NOT NULL AND file_path <> '') OR (url IS NOT NULL AND url <> '')


Indexes:
- attachments(article_id)
- esncards(card_number UNIQUE)

## 5.3 Treasury money handling (must stay safe)

- amount: DECIMAL(10,2)
- amount > 0 always
- withdrawal cannot exceed current balance (server-side)
- status values restricted (pending/approved/rejected or your existing enum)



The database schema is already fully implemented and provisioned via Docker.

- MariaDB runs in a Docker container (`db` service, profile: `localdb`)
- Schema is created automatically from SQL init scripts
- Location of SQL init files:
  - `docker/sql/000_schema.sql` (full schema)
  - optional seed scripts (e.g. `010_seed_roles.sql`)

Rules:
- Database tables MUST NOT be created manually in phpMyAdmin
- Any schema change MUST be done by editing SQL files in `docker/sql/`
- After schema change, database must be recreated using:
  `docker compose --profile localdb down --volumes`
  `docker compose --profile localdb up -d --build`

This guarantees:
- reproducible setup,
- clean evaluation,
- Docker-based deployment (bonus points).

===============================================================================

## DATABASE CHANGE POLICY

- The database schema is treated as immutable during feature implementation.
- Application code must adapt to the schema, not vice versa.
- Any schema change must be:
  - justified (new feature),
  - implemented via SQL migration in `docker/sql/`,
  - followed by full DB recreation in development.


## 6) ROUTING (rules + recommended endpoints)

We follow consistent MVC endpoints like Treasury.

Suggested endpoints:

Auth:
- GET  /login   -> show login form
- POST /login   -> login submit
- GET  /logout  -> logout

Treasury:
- GET  /treasury
- GET  /treasury/new
- POST /treasury/store
- GET  /treasury/edit/{id}
- POST /treasury/update/{id}
- GET/POST /treasury/delete/{id}
- POST /treasury/status/{id}   (AJAX approve/reject)

ESNcards:
- GET  /esncards
- GET  /esncards/new
- POST /esncards/store
- GET  /esncards/edit/{id}
- POST /esncards/update/{id}
- GET/POST /esncards/delete/{id}

Knowledge Base:
- GET  /manual
- GET  /manual/new
- POST /manual/store
- GET  /manual/edit/{id}
- POST /manual/update/{id}
- GET/POST /manual/delete/{id}
- GET  /manual/{id}  (article detail)
- POST /manual/{id}/attachments/upload   (AJAX upload)
- GET/POST /manual/{id}/attachments/delete/{attId} (delete attachment)

All “write” actions must require login; admin/treasurer requirements apply per module.

===============================================================================

## 7) MINIMUM DYNAMIC PAGES (5+)

We count these as dynamic pages:

1) Home dashboard (cards + modules)
2) Treasury list
3) Treasury create/edit form
4) ESNcards list
5) ESNcards create/edit form
6) Manual list
7) Manual detail with attachments

We need at least 5; we will have 6–7.

===============================================================================

## 8) FRONTEND REQUIREMENTS (JS + CSS)

## 8.1 JavaScript: 50+ custom lines (meaningful)

Single file recommended:
- public/js/app.js

Must include:
- AJAX #1: Treasury approve/reject status (fetch JSON + DOM update)
- AJAX #2: Attachment upload (fetch FormData + DOM update)
- helper functions: toast/alert, disable buttons, error rendering
- error handling (HTTP errors + JSON ok:false)

Definition: custom JS lines must be project-written and used.

## 8.2 CSS: 20+ custom rules (not Bootstrap)

Single file recommended:
- public/css/custom.css

Include at least 20 rules such as:
- status badges: pending/approved/rejected
- module cards styling
- tables (striping, hover)
- buttons variants (esn theme)
- responsive tweaks via media queries
- upload UI styling

===============================================================================

## 9) VALIDATION RULES (CLIENT + SERVER)

## 9.1 Server-side validation (mandatory)

For every endpoint (form or AJAX), validate:
- required fields
- allowed values (enum)
- numeric ranges (amount > 0, etc.)
- string length limits
- email format
- unique constraints where required (card_number)
- file upload: allowlist mime + extension, size limit, safe filenames
- authorization checks

For AJAX, return consistent JSON errors:
- ok: false
- message: "human readable"
- fields: { fieldName: ["error1","error2"] } (optional)

## 9.2 Client-side validation

Use HTML5 + minimal JS:
- required
- type=email
- pattern for card_number
- prevent submit if invalid
- show inline errors if possible

Client validation never replaces server validation.

===============================================================================

## 10) FILE UPLOAD + MANAGEMENT (mandatory)

## 10.1 Storage directory

Use:
- public/uploads/manual/

Ensure directory exists and is writable.
Never store uploads outside public unless you implement download proxy logic.

## 10.2 Allowed file types (allowlist)

Recommended allowlist:
- PDF: application/pdf
- DOCX: application/vnd.openxmlformats-officedocument.wordprocessingml.document
- PNG: image/png
- JPEG: image/jpeg

Max size:
- 10 MB (or smaller if required)

## 10.3 Naming and sanitization

When storing:
- keep original name in DB (filename_original)
- generate stored name: UUID + "." + ext (filename_stored)
- never trust user filename as filesystem name
- remove path traversal sequences

## 10.4 Delete behavior

On delete attachment:
- delete DB record
- delete file from disk if exists
- require admin role

===============================================================================

## 11) AJAX REQUIREMENTS (2 meaningful uses)

We implement these two (fastest + highest VAII value):

AJAX #1 Treasury: approve/reject transaction
- UI buttons on treasury list
- fetch POST to /treasury/status/{id}
- JSON response
- update status badge without reload

AJAX #2 Knowledge base: attachment upload
- upload form on article detail (admin)
- fetch FormData to /manual/{id}/attachments/upload
- JSON response
- append attachment to list without reload

Both endpoints must:
- be authorized server-side
- validate input
- return JSON ok:true/false

===============================================================================

## 12) GIT WORKFLOW (fast + safe)

Commit strategy:
- small commits, one feature per commit
- clear messages
  Examples:
- auth: add roles + login
- esncards: add table + repository
- esncards: add controller + views
- manual: add articles CRUD
- manual: add attachments upload/delete
- ajax: treasury approve/reject
- ajax: manual upload
- ui: custom css + app.js
- docs: add README + cookbook

Avoid a single huge commit.

===============================================================================

## 13) DEADLINE SCOPE (what we build vs skip)

Build (core):
- login + roles + server-side authorization
- Treasury (existing) + AJAX approve/reject
- ESNcards CRUD
- Manual: articles CRUD + attachments upload/delete + AJAX upload
- custom JS 50+ lines
- custom CSS 20+ rules
- README.md

Skip:
- automated tests
- Polls module (optional if time remains)
- advanced reporting

===============================================================================

## 14) STEP-BY-STEP RECIPES (copy-paste tasks)

Each recipe includes:
- goal
- steps
- definition of done (DONE)

-------------------------------------------------------------------------------

RECIPE A — Auth + Roles (login + guards)

Goal:
- users can log in
- session persists
- roles exist
- protected actions require correct role

Steps:
1) DB:
   - roles and users tables already exist
   - insert initial roles (member, treasurer, admin) via seed SQL
   - create at least one admin user for development/testing


2) Auth controller:
  - GET /login: show login form
  - POST /login: verify credentials (password_verify)
  - GET /logout: destroy session

3) Guards:
  - requireLogin()
  - requireRole([...])

4) UI:
  - navbar shows Login when logged out
  - navbar shows Logout + current user when logged in

DONE:
- can login and logout
- protected page is blocked when logged out
- role restrictions enforced server-side

-------------------------------------------------------------------------------

RECIPE B — ESNcards CRUD (second CRUD entity)

Goal:
- full CRUD for ESNcards with validation and authorization

Steps:
1) DB table: esncards

2) Repository: EsncardRepository
  - findAll(search)
  - findById(id)
  - create(data)
  - update(id, data)
  - delete(id)

3) Controller: EsncardsController
  - index (member+)
  - new/store (treasurer/admin)
  - edit/update (treasurer/admin)
  - delete (treasurer/admin)

4) Views:
  - index: list + search by card_number + status
  - new: create form
  - edit: edit/assign form

5) Validation rules:
  - card_number required, unique
  - status allowed values
  - assigned_to_email must be valid email if provided
  - if status=assigned: require assigned_to_name + assigned_to_email + assigned_at

6) Client-side validation:
  - required + pattern for card_number
  - email input type

DONE:
- you can create/edit/delete ESNcards via UI
- member can only view list
- treasurer/admin can manage
- invalid inputs rejected server-side

-------------------------------------------------------------------------------

RECIPE C — Manual / Knowledge base: Articles CRUD (third entity)

Goal:
- manage internal knowledge articles (semester manual replacement)

Steps:
1) DB: knowledge_articles

2) Repository: ManualRepository (articles part)
  - findAllArticles()
  - findArticleById(id)
  - createArticle(data)
  - updateArticle(id, data)
  - deleteArticle(id)

3) Controller: ManualController (articles part)
  - index (member+)
  - show/article detail (member+)
  - new/store (admin)
  - edit/update (admin)
  - delete (admin)

4) Views:
  - index: list cards/table with title/category/difficulty
  - show: title + content
  - new/edit: form

5) Validation:
  - title required length
  - content required
  - difficulty in allowed values if used

DONE:
- admin can CRUD articles
- member can view list and detail
- validation works server-side

-------------------------------------------------------------------------------

RECIPE D — Attachments: upload + list + delete (file management)

Goal:
- implement file upload and management for article attachments (1:N relationship)

Steps:
1) DB: attachments with FK article_id -> knowledge_articles.id (ON DELETE CASCADE)

2) Storage:
  - create public/uploads/manual/
  - ensure write permissions

3) Repository: ManualRepository (attachments part)
  - listAttachments(articleId)
  - addAttachment(articleId, data)
  - deleteAttachment(attId) returns stored_filename

4) Controller: ManualController (attachments part)
  - upload endpoint: admin only
  - delete endpoint: admin only
  - show page lists attachments for article

5) Upload validation:
  - allowlist file types (pdf/docx/png/jpg)
  - max size 10MB
  - generate stored filename (uuid + ext)
  - store filename_original + filename_stored + mime + size_bytes in DB

6) Delete behavior:
  - delete DB record
  - delete file from disk if exists

DONE:
- attachments are uploaded and shown on article detail
- attachments can be deleted (admin only)
- no orphan files remain

-------------------------------------------------------------------------------

RECIPE E — AJAX #1 Treasury approve/reject (mandatory AJAX #1)

Goal:
- approve/reject transactions without reload

Steps:
1) TreasuryController: add AJAX action (setStatusJson)
  - requireRole treasurer/admin
  - validate status allowed values
  - update transaction status in repository
  - return JSON ok:true/false

2) Treasury list view:
  - add approve/reject buttons for pending transactions
  - add data-id attributes
  - include status badge element with an id/class for DOM update

3) public/js/app.js:
  - add fetch handler for approve/reject buttons
  - update badge + disable buttons on success
  - show error message on failure

DONE:
- clicking approve/reject updates UI without full reload
- server rejects unauthorized roles
- invalid status rejected

-------------------------------------------------------------------------------

RECIPE F — AJAX #2 Attachment upload (mandatory AJAX #2)

Goal:
- upload attachment without reload and append it to list

Steps:
1) ManualController: uploadAttachmentJson
  - requireRole admin
  - read file from request
  - validate allowlist + size
  - save file
  - insert DB record
  - return JSON with attachment info including download URL

2) Manual show view:
  - show upload form only for admin
  - show list container for attachments

3) public/js/app.js:
  - intercept submit, send FormData via fetch
  - append new attachment item to list without reload
  - show error feedback

DONE:
- upload works and updates list live
- file stored + DB record created
- server rejects non-admin roles

-------------------------------------------------------------------------------

RECIPE G — Custom JS 50+ lines + Custom CSS 20+ rules

Goal:
- satisfy VAII JS and CSS requirements with real UI usage

Steps:
1) public/js/app.js
  - include both AJAX features
  - add helpers:
    - request helper
    - toast/alert helper
    - renderFieldErrors helper
    - disableButtons helper
  - ensure 50+ meaningful lines

2) public/css/custom.css
  - add at least 20 rules, for example:
    - .badge-status-pending / approved / rejected
    - .module-card
    - .esn-table styling
    - .esn-btn variants
    - .upload-box
    - @media responsive tweaks
  - ensure it is loaded in the layout

DONE:
- app contains 50+ JS lines that are executed/used
- app contains 20+ custom CSS rules (not Bootstrap)

-------------------------------------------------------------------------------

RECIPE H — README.md (mandatory)

Goal:
- make project runnable by evaluator

README must include:
- what the app is
- requirements (PHP, MariaDB)
- how to configure environment (.env)
- how to create DB tables (SQL scripts)
- seed credentials:
  - admin@local / admin123 (or your real seed)
- where uploads are stored (public/uploads/manual/)
- note about AI code labeling

DONE:
- evaluator can run the app following README

===============================================================================

## 15) COPY-PASTE PROMPT TEMPLATES FOR COPILOT

Use these templates in Copilot Chat; fill placeholders.

-------------------------------------------------------------------------------

PROMPT 1 — Generate a new module CRUD (Repo + Controller + Views)

Implement the <MODULE_NAME> module following `docs/AI_COOKBOOK.md`.
Use the existing Treasury module as the reference for structure and style.
Framework is VAIIČKO MVC, server-rendered. Database is MariaDB. 
This framework is in Framework directory: never change it.

Create:
- Repository: <PATH>/App/Repositories/<Name>Repository.php
- Controller: <PATH>/App/Controllers/<Name>Controller.php
- Views: <PATH>/App/Views/<Name>/index.view.php, new.view.php, edit.view.php, show.view.php (if needed)
- Update routing in App/config/routes.php with endpoints consistent with Treasury
- Add server-side authorization by role as defined in the cookbook
- Add server-side validation and HTML5 client validation
- Use prepared statements only
- Add required AI-GENERATED comment headers to created/modified files
  Return only the code changes.

-------------------------------------------------------------------------------

PROMPT 2 — Implement AJAX endpoint

Implement AJAX feature <FEATURE_NAME> following `docs/AI_COOKBOOK.md`:
- Add backend endpoint: <METHOD> <URL>
- Enforce requireLogin + requireRole
- Validate input server-side (allowed values)
- Update repository using prepared statements
- Return JSON { ok:true, ... } or { ok:false, message, fields }
- Update the relevant view to include buttons/form with data attributes
- Update public/js/app.js to call fetch and update DOM without reload
- Add AI-GENERATED headers

-------------------------------------------------------------------------------

PROMPT 3 — Implement file upload (Attachments)

Implement attachment upload for manual articles following `docs/AI_COOKBOOK.md`:
- Storage: public/uploads/manual/
- Allowed types: pdf, docx, png, jpg
- Max size: 10MB
- Store original name + stored name + mime + size in DB
- Add delete attachment endpoint (delete DB + file)
- Add AJAX upload endpoint returning JSON and append in DOM
- Server-side admin authorization
- Add AI-GENERATED headers

-------------------------------------------------------------------------------

## 16) DEFENSE PREP CHECKLIST (what you must be able to explain)

You must be able to explain and show in code:
- MVC flow for one request (controller -> repo -> view)
- How login works (sessions, password hashing)
- How role checks work (server-side guards)
- How prepared statements prevent SQL injection
- How form validation works (server + client)
- How each AJAX feature works (fetch, endpoint, JSON, DOM update)
- How file uploads are secured (allowlist, size, naming)
- DB entities and their relationships (why meaningful, why normalized)

===============================================================================

END OF FILE
