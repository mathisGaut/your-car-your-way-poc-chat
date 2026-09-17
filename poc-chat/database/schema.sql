-- Script de création de la BDD du POC Chat (Your Car Your Way)
-- PostgreSQL — schéma minimal + données de démonstration
--
-- En conditions normales, Docker exécute les migrations Laravel
-- (backend/database/migrations) et le seeder (ChatDemoSeeder).
-- Ce fichier est l'équivalent SQL, déjà seedé, pour lecture ou import manuel :
--   psql -U ycyw -d ycyw_chat -f database/schema.sql

BEGIN;

DROP TABLE IF EXISTS messages CASCADE;
DROP TABLE IF EXISTS conversation_user CASCADE;
DROP TABLE IF EXISTS conversations CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE conversations (
    id BIGSERIAL PRIMARY KEY,
    status VARCHAR(255) NOT NULL DEFAULT 'open',
    user_id BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE conversation_user (
    id BIGSERIAL PRIMARY KEY,
    conversation_id BIGINT NOT NULL REFERENCES conversations (id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE (conversation_id, user_id)
);

CREATE TABLE messages (
    id BIGSERIAL PRIMARY KEY,
    content TEXT NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    conversation_id BIGINT NOT NULL REFERENCES conversations (id) ON DELETE CASCADE,
    sender_id BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE
);

-- Mot de passe : "password" (hash bcrypt Laravel, non utilisé par le POC)
INSERT INTO users (id, name, email, password, created_at, updated_at) VALUES
    (1, 'Utilisateur 1', 'utilisateur1@ycyw.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
    (2, 'Utilisateur 2', 'utilisateur2@ycyw.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

INSERT INTO conversations (id, status, user_id, created_at, updated_at) VALUES
    (1, 'open', 1, NOW(), NOW());

INSERT INTO conversation_user (id, conversation_id, user_id) VALUES
    (1, 1, 1),
    (2, 1, 2);

INSERT INTO messages (id, content, sent_at, conversation_id, sender_id) VALUES
    (1, 'Bonjour', NOW() - INTERVAL '2 minutes', 1, 1),
    (2, 'Bonjour, comment allez-vous ?', NOW() - INTERVAL '1 minute', 1, 2);

SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users));
SELECT setval(pg_get_serial_sequence('conversations', 'id'), (SELECT MAX(id) FROM conversations));
SELECT setval(pg_get_serial_sequence('conversation_user', 'id'), (SELECT MAX(id) FROM conversation_user));
SELECT setval(pg_get_serial_sequence('messages', 'id'), (SELECT MAX(id) FROM messages));

COMMIT;
