-- Kanban seed data
-- This script inserts a few sample rows for the Doctrine-generated table `task`.
-- If your table name is `tasks` (legacy PHP backend), use the second block below.

-- ============================
-- Block A: Doctrine table `task`
-- ============================
-- Adjust database/schema name if needed before running (MySQL example):
-- USE kanban;

INSERT INTO task (title, status, sort_order, created_at) VALUES
('Installer Angular + Bootstrap', 'todo', 0, CURRENT_TIMESTAMP),
('Créer entité Task + migrations', 'doing', 0, CURRENT_TIMESTAMP),
('Brancher le service TaskService', 'doing', 1, CURRENT_TIMESTAMP),
('Tester CORS / Proxy', 'done', 0, CURRENT_TIMESTAMP),
('Implémenter Drag & Drop', 'todo', 1, CURRENT_TIMESTAMP),
('Persister le reorder', 'todo', 2, CURRENT_TIMESTAMP);

-- ============================
-- Block B: Legacy PHP table `tasks` (uncomment if needed)
-- ============================
-- INSERT INTO tasks (title, status, sort_order, created_at) VALUES
-- ('Installer Angular + Bootstrap', 'todo', 0, CURRENT_TIMESTAMP),
-- ('Créer entité Task + migrations', 'doing', 0, CURRENT_TIMESTAMP),
-- ('Brancher le service TaskService', 'doing', 1, CURRENT_TIMESTAMP),
-- ('Tester CORS / Proxy', 'done', 0, CURRENT_TIMESTAMP),
-- ('Implémenter Drag & Drop', 'todo', 1, CURRENT_TIMESTAMP),
-- ('Persister le reorder', 'todo', 2, CURRENT_TIMESTAMP);
