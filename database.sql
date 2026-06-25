CREATE DATABASE noticeboard_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE noticeboard_db;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student') DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
bg_color_code VARCHAR(20)
);

CREATE TABLE academic_years (
id INT AUTO_INCREMENT PRIMARY KEY,
year_name VARCHAR(50) NOT NULL
);

CREATE TABLE notices (
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
content TEXT NOT NULL,
category_id INT,
user_id INT,
status ENUM('draft','published','expired') DEFAULT 'draft',
target_role ENUM('student','admin','all') DEFAULT 'all',
target_year_id INT NULL,
publish_date DATETIME NULL,
expire_date DATETIME NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(category_id) REFERENCES categories(id),
FOREIGN KEY(user_id) REFERENCES users(id),
FOREIGN KEY(target_year_id) REFERENCES academic_years(id)
);

CREATE TABLE attachments (
id INT AUTO_INCREMENT PRIMARY KEY,
notice_id INT,
file_path VARCHAR(255),
file_type VARCHAR(50),

FOREIGN KEY(notice_id) REFERENCES notices(id)

);

CREATE TABLE comments (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
notice_id INT,
comment TEXT,
status ENUM('approved','pending') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(user_id) REFERENCES users(id),
FOREIGN KEY(notice_id) REFERENCES notices(id)

);

CREATE TABLE history (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
action VARCHAR(255),
ip_address VARCHAR(50),
notice_id INT NULL,
action_type VARCHAR(50),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(user_id) REFERENCES users(id)

);

-- Insert your admin user safely with a valid bcrypt hash
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) 
VALUES (
  'Daw Khin Moe Aye', 
  'admin1@gmail.com', 
  '$2y$10$7R6vYn7Cg2bFmZzD9KxOeO6h3g9DyxK2A3e8R1bC5dE6fG7h8i9jK', 
  'admin'
);

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) 
VALUES (
  'Daw Su Sandar', 
  'admin2@gmail.com', 
  'admin234', 
  'admin'
);

INSERT INTO `notices` (`title`, `content`, `category_id`, `user_id`, `status`, `target_role`, `target_year_id`, `publish_date`, `expire_date`) 
VALUES (
  'Cron Scheduler', 
  'Testing automation', 
  1, 
  2,
  'draft',
  'all',  
  NULL, -- Removed the quotes
  DATE_ADD(NOW(), INTERVAL 2 MINUTE), -- Automatically calculates 2 minutes from right now!
  NULL  -- Left as NULL since it doesn't expire
);

INSERT INTO `notices` (`title`, `content`, `category_id`, `user_id`, `status`, `target_role`, `target_year_id`, `publish_date`, `expire_date`) 
VALUES (
  'Mid-term Examination', 
  'Mid-term examination will start on July10', 
  1, 
  2,
  'draft',
  'all',  
  NULL, -- Removed the quotes
  DATE_ADD(NOW(), INTERVAL 2 MINUTE), -- Automatically calculates 2 minutes from right now!
  NULL  -- Left as NULL since it doesn't expire
);