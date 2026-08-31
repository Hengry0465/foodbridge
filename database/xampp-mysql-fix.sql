-- Run in phpMyAdmin or MySQL CLI while skip-grant-tables is enabled in my.ini
-- Works with XAMPP MariaDB 10.4+

FLUSH PRIVILEGES;

UPDATE mysql.global_priv
SET Priv = JSON_SET(Priv, '$.plugin', 'mysql_native_password', '$.authentication_string', '')
WHERE User = 'root';

FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS asignment1
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

SELECT Host, User, JSON_EXTRACT(Priv, '$.plugin') AS plugin
FROM mysql.global_priv
WHERE User = 'root';
