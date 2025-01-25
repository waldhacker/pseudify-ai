-- https://dev.mysql.com/doc/refman/5.5/en/create-database.html
-- https://dev.mysql.com/doc/refman/5.5/en/charset-charsets.html
CREATE DATABASE IF NOT EXISTS pseudify_utf8mb4 CHARACTER SET = 'utf8mb4' COLLATE = 'utf8mb4_general_ci';

-- https://dev.mysql.com/doc/refman/5.5/en/grant.html
GRANT SELECT, UPDATE ON pseudify_utf8mb4.* TO 'pseudify'@'%' IDENTIFIED BY 'P53ud1fy(!)w4ldh4ck3r';

FLUSH PRIVILEGES;
