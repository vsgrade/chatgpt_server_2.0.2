<?php
require "db.php";
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255)
)");
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_key TEXT,
    proxy TEXT,
    proxy_type VARCHAR(20) DEFAULT 'HTTP',
    limit_minute INT DEFAULT 0,
    limit_hour INT DEFAULT 0,
    limit_day INT DEFAULT 0,
    limit_week INT DEFAULT 0,
    limit_month INT DEFAULT 0,
    enabled_models TEXT
)");
$db->exec("CREATE TABLE IF NOT EXISTS token_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tokens INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    password VARCHAR(255)
)");
$db->exec("CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message MEDIUMTEXT,
    file VARCHAR(255),
    line INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) DEFAULT 'Новый чат',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('user','bot') NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo 'База инициализирована.';
?>
