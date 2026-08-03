-- SQL Setup Script for Student Feedback System

-- 1. Create and configure `admin` database
CREATE DATABASE IF NOT EXISTS `admin`;
USE `admin`;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
);

-- Insert default admin credentials (username: admin, password: admin123)
INSERT INTO `admin` (`username`, `password`) 
VALUES ('admin', 'admin123') 
ON DUPLICATE KEY UPDATE `username`=`username`;


-- 2. Create and configure `faculty` database
CREATE DATABASE IF NOT EXISTS `faculty`;
USE `faculty`;

CREATE TABLE IF NOT EXISTS `faculty` (
  `id` INT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `designation` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `scheme` VARCHAR(255) NOT NULL,
  `semester` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `year` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `status` INT DEFAULT 1
);



-- 3. Create and configure `student` database
CREATE DATABASE IF NOT EXISTS `student`;
USE `student`;

CREATE TABLE IF NOT EXISTS `student` (
  `id` INT PRIMARY KEY,
  `sname` VARCHAR(255) NOT NULL,
  `year` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `imported` BOOLEAN DEFAULT FALSE
);

-- Insert a default student for testing (username: vishali, password: password123)
INSERT INTO `student` (`id`, `sname`, `year`, `password`, `imported`)
VALUES (1, 'vishali', '1st', 'password123', FALSE)
ON DUPLICATE KEY UPDATE `sname`=`sname`;


-- 4. Create and configure `questions` database
CREATE DATABASE IF NOT EXISTS `questions`;
USE `questions`;

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `questions` VARCHAR(255) NOT NULL
);


-- 5. Create `responses` database
CREATE DATABASE IF NOT EXISTS `responses`;
