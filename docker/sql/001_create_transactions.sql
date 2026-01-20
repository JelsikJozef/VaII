-- =========================================================
-- ESN UNIZA - Database schema (MariaDB / MySQL)
-- Based on Checkpoint 1 entities and relations
-- Charset: utf8mb4
-- Engine: InnoDB
-- =========================================================

-- (Optional) Create database + set defaults
-- CREATE DATABASE IF NOT EXISTS esn_uniza
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE esn_uniza;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------
-- Drop tables (reverse dependency order)
-- ---------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS poll_votes;
DROP TABLE IF EXISTS poll_options;
DROP TABLE IF EXISTS polls;

DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS checklist_items;
DROP TABLE IF EXISTS knowledge_articles;

DROP TABLE IF EXISTS esncards;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS cashboxes;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Roles
-- ---------------------------------------------------------
CREATE TABLE roles (
                       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                       name VARCHAR(50) NOT NULL, -- (member, treasurer, admin)
                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       PRIMARY KEY (id),
                       UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Users
-- (role 1:N users) per Checkpoint 1
-- ---------------------------------------------------------
CREATE TABLE users (
                       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                       name VARCHAR(150) NOT NULL,
                       email VARCHAR(255) NOT NULL,
                       password_hash VARCHAR(255) NOT NULL,
                       role_id INT UNSIGNED NOT NULL,
                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                       PRIMARY KEY (id),
                       UNIQUE KEY uq_users_email (email),
                       KEY idx_users_role_id (role_id),

                       CONSTRAINT fk_users_role
                           FOREIGN KEY (role_id)
                               REFERENCES roles(id)
                               ON UPDATE CASCADE
                               ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Cashbox (accounting period / semester)
-- (cashbox 1:N transactions) per Checkpoint 1
-- ---------------------------------------------------------
CREATE TABLE cashboxes (
                           id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                           name VARCHAR(150) NOT NULL, -- e.g. "Zimný semester 2025/26"
                           start_date DATE NOT NULL,
                           end_date DATE NULL,
                           initial_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                           final_balance DECIMAL(12,2) NULL DEFAULT NULL,
                           is_closed TINYINT(1) NOT NULL DEFAULT 0,
                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                           PRIMARY KEY (id),
                           KEY idx_cashboxes_dates (start_date, end_date),
                           KEY idx_cashboxes_is_closed (is_closed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Transactions
-- Attributes per Checkpoint 1: cashbox_id, type, amount, description, status,
-- created_by, approved_by, created_at
-- ---------------------------------------------------------
CREATE TABLE transactions (
                              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                              cashbox_id INT UNSIGNED NOT NULL,

                              type ENUM('deposit','withdrawal') NOT NULL,
                              amount DECIMAL(12,2) NOT NULL,
                              description VARCHAR(500) NOT NULL,

                              status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',

                              created_by INT UNSIGNED NOT NULL,
                              approved_by INT UNSIGNED NULL,

                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              approved_at DATETIME NULL DEFAULT NULL,

                              PRIMARY KEY (id),

                              KEY idx_transactions_cashbox_id (cashbox_id),
                              KEY idx_transactions_status (status),
                              KEY idx_transactions_created_by (created_by),
                              KEY idx_transactions_approved_by (approved_by),
                              KEY idx_transactions_created_at (created_at),

                              CONSTRAINT fk_transactions_cashbox
                                  FOREIGN KEY (cashbox_id)
                                      REFERENCES cashboxes(id)
                                      ON UPDATE CASCADE
                                      ON DELETE RESTRICT,

                              CONSTRAINT fk_transactions_created_by
                                  FOREIGN KEY (created_by)
                                      REFERENCES users(id)
                                      ON UPDATE CASCADE
                                      ON DELETE RESTRICT,

                              CONSTRAINT fk_transactions_approved_by
                                  FOREIGN KEY (approved_by)
                                      REFERENCES users(id)
                                      ON UPDATE CASCADE
                                      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- ESNcards
-- Per Checkpoint 1: card_number, assigned_to_name, assigned_to_email, assigned_at, status
-- (Optionally can be linked to users later)
-- ---------------------------------------------------------
CREATE TABLE esncards (
                          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                          card_number VARCHAR(50) NOT NULL,

                          status ENUM('available','assigned','inactive') NOT NULL DEFAULT 'available',

                          assigned_to_name VARCHAR(150) NULL,
                          assigned_to_email VARCHAR(255) NULL,
                          assigned_at DATETIME NULL,

                          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                          PRIMARY KEY (id),
                          UNIQUE KEY uq_esncards_card_number (card_number),
                          KEY idx_esncards_status (status),
                          KEY idx_esncards_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- KnowledgeArticle (wiki/process)
-- Per Checkpoint 1: title, category, difficulty, content, created_by, updated_by
-- ---------------------------------------------------------
CREATE TABLE knowledge_articles (
                                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                    title VARCHAR(200) NOT NULL,
                                    category VARCHAR(100) NULL,

                                    difficulty ENUM('easy','medium','hard') NULL,

                                    content MEDIUMTEXT NOT NULL,

                                    created_by INT UNSIGNED NOT NULL,
                                    updated_by INT UNSIGNED NULL,

                                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                                    PRIMARY KEY (id),
                                    KEY idx_articles_category (category),
                                    KEY idx_articles_difficulty (difficulty),
                                    KEY idx_articles_created_by (created_by),
                                    KEY idx_articles_updated_by (updated_by),

                                    CONSTRAINT fk_articles_created_by
                                        FOREIGN KEY (created_by)
                                            REFERENCES users(id)
                                            ON UPDATE CASCADE
                                            ON DELETE RESTRICT,

                                    CONSTRAINT fk_articles_updated_by
                                        FOREIGN KEY (updated_by)
                                            REFERENCES users(id)
                                            ON UPDATE CASCADE
                                            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- ChecklistItem (article 1:N checklist items)
-- Per Checkpoint 1: article_id, text, order_index
-- ---------------------------------------------------------
CREATE TABLE checklist_items (
                                 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                 article_id INT UNSIGNED NOT NULL,
                                 text VARCHAR(300) NOT NULL,
                                 order_index INT UNSIGNED NOT NULL DEFAULT 1,

                                 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                                 PRIMARY KEY (id),
                                 KEY idx_checklist_article_id (article_id),
                                 KEY idx_checklist_order (article_id, order_index),

                                 CONSTRAINT fk_checklist_article
                                     FOREIGN KEY (article_id)
                                         REFERENCES knowledge_articles(id)
                                         ON UPDATE CASCADE
                                         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Attachment (article 1:N attachments)
-- Per Checkpoint 1: article_id, file_path or url, description
-- ---------------------------------------------------------
CREATE TABLE attachments (
                             id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                             article_id INT UNSIGNED NOT NULL,

    -- store either uploaded file path or external URL
                             file_path VARCHAR(512) NULL,
                             url VARCHAR(512) NULL,

                             description VARCHAR(255) NULL,

                             created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                             PRIMARY KEY (id),
                             KEY idx_attachments_article_id (article_id),

                             CONSTRAINT fk_attachments_article
                                 FOREIGN KEY (article_id)
                                     REFERENCES knowledge_articles(id)
                                     ON UPDATE CASCADE
                                     ON DELETE CASCADE,

    -- Ensure at least one of file_path/url is present
                             CONSTRAINT chk_attachment_path_or_url
                                 CHECK (
                                     (file_path IS NOT NULL AND file_path <> '')
                                         OR (url IS NOT NULL AND url <> '')
                                     )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Polls, PollOptions, PollVotes
-- Per Checkpoint 1: Poll(question, created_by, created_at, is_active),
-- PollOption(poll_id, text), PollVote(poll_id, option_id, user_id)
-- Ensure: user votes only once per poll
-- ---------------------------------------------------------
CREATE TABLE polls (
                       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                       question VARCHAR(300) NOT NULL,
                       created_by INT UNSIGNED NOT NULL,
                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       is_active TINYINT(1) NOT NULL DEFAULT 1,

                       PRIMARY KEY (id),
                       KEY idx_polls_created_by (created_by),
                       KEY idx_polls_is_active (is_active),

                       CONSTRAINT fk_polls_created_by
                           FOREIGN KEY (created_by)
                               REFERENCES users(id)
                               ON UPDATE CASCADE
                               ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Use composite unique to allow enforcing "option belongs to poll" in votes
CREATE TABLE poll_options (
                              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                              poll_id INT UNSIGNED NOT NULL,
                              text VARCHAR(200) NOT NULL,

                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                              PRIMARY KEY (id),
                              UNIQUE KEY uq_poll_options_id_poll (id, poll_id),
                              KEY idx_poll_options_poll_id (poll_id),

                              CONSTRAINT fk_poll_options_poll
                                  FOREIGN KEY (poll_id)
                                      REFERENCES polls(id)
                                      ON UPDATE CASCADE
                                      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE poll_votes (
                            id INT UNSIGNED NOT NULL AUTO_INCREMENT,

                            poll_id INT UNSIGNED NOT NULL,
                            option_id INT UNSIGNED NOT NULL,
                            user_id INT UNSIGNED NOT NULL,

                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                            PRIMARY KEY (id),

    -- One vote per user per poll
                            UNIQUE KEY uq_poll_votes_poll_user (poll_id, user_id),

                            KEY idx_poll_votes_poll_id (poll_id),
                            KEY idx_poll_votes_option_id (option_id),
                            KEY idx_poll_votes_user_id (user_id),

                            CONSTRAINT fk_poll_votes_poll
                                FOREIGN KEY (poll_id)
                                    REFERENCES polls(id)
                                    ON UPDATE CASCADE
                                    ON DELETE CASCADE,

    -- Enforce option belongs to the same poll
                            CONSTRAINT fk_poll_votes_option_poll
                                FOREIGN KEY (option_id, poll_id)
                                    REFERENCES poll_options(id, poll_id)
                                    ON UPDATE CASCADE
                                    ON DELETE CASCADE,

                            CONSTRAINT fk_poll_votes_user
                                FOREIGN KEY (user_id)
                                    REFERENCES users(id)
                                    ON UPDATE CASCADE
                                    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
