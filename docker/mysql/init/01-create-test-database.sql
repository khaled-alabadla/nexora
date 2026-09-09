-- Runs once on first MySQL container start (empty data dir).
-- The primary database is created from MYSQL_DATABASE; this adds the
-- dedicated test schema (see ADR-0005) and grants the app user access.
CREATE DATABASE IF NOT EXISTS `nexora_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

GRANT ALL PRIVILEGES ON `nexora_test`.* TO 'nexora'@'%';
GRANT ALL PRIVILEGES ON `nexora`.*      TO 'nexora'@'%';
FLUSH PRIVILEGES;
