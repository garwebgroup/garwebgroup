-- schema.sql
-- Run this once against a fresh MySQL database to create the tables
-- required by the backend library.

CREATE DATABASE IF NOT EXISTS company_info_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE company_info_system;

-- Application accounts + RBAC role
CREATE TABLE IF NOT EXISTS users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(32)  NOT NULL UNIQUE,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  full_name       VARCHAR(100) NOT NULL,
  role            ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user',
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until    DATETIME NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Company profile (Information About the Company page, item #2)
CREATE TABLE IF NOT EXISTS company_profile (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  company_name  VARCHAR(150) NOT NULL,
  description   TEXT,
  mission       TEXT,
  vision        TEXT,
  founded_year  INT,
  logo_path     VARCHAR(255)
) ENGINE=InnoDB;

-- Demo CRUD table for the information system (item #8)
CREATE TABLE IF NOT EXISTS employees (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  position    VARCHAR(100),
  department  VARCHAR(100),
  email       VARCHAR(190),
  phone       VARCHAR(30),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contact page submissions (item #3)
CREATE TABLE IF NOT EXISTS contact_messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  message     TEXT NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed one admin account so you can log in immediately.
-- Username: admin   Password: ChangeMe123!
-- (Hash below was generated with PHP's password_hash() using PASSWORD_ARGON2ID/BCRYPT;
--  if you regenerate it, use core/Security.php::hashPassword() so it matches your PHP build.)
-- Run this INSERT after creating the tables, or generate your own hash via:
--   php -r "echo password_hash('ChangeMe123!', PASSWORD_BCRYPT);"
INSERT INTO users (username, email, password_hash, full_name, role)
VALUES ('admin', 'admin@example.com', '<paste-generated-hash-here>', 'System Admin', 'admin');

-- Sample company profile row
INSERT INTO company_profile (company_name, description, mission, vision, founded_year, logo_path)
VALUES (
  'Nova Ghana Tech Ltd.',
  'Nova Ghana Tech Ltd. is a Ghana-based information technology company providing digital infrastructure and software solutions.',
  'To empower Ghanaian businesses through accessible, secure technology.',
  'A digitally transformed Ghana driven by locally built software.',
  2020,
  'assets/img/logo.png'
);

-- ---------------------------------------------------------------------
-- Newly added tables (attendance, employeebank, helpdesk, payroll)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empid` int(11) NOT NULL,
  `deptid` datetime NOT NULL,
  `workstart` datetime NOT NULL,
  `workend` datetime NOT NULL,
  `entrydate` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ;

CREATE TABLE IF NOT EXISTS `employeebank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ;

-- NOTE: `id` here has no AUTO_INCREMENT in the SQL as supplied. That means
-- the API cannot rely on the database to generate it, and generic
-- create() (which never writes to a primary key) will fail with
-- "Field 'id' doesn't have a default value". Recommended fix:
--   ALTER TABLE `helpdesk` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
CREATE TABLE IF NOT EXISTS `helpdesk` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `complaint` varchar(180) NOT NULL,
  `assignedto` int(11) NOT NULL,
  `entrydate` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `feedback` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ;

CREATE TABLE IF NOT EXISTS `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `month` text NOT NULL,
  `employee` int(11) NOT NULL,
  `grosssalary` decimal(10,0) NOT NULL,
  `deductions` decimal(10,0) NOT NULL,
  `netsalary` decimal(10,0) NOT NULL,
  `entrydate` datetime NOT NULL DEFAULT current_timestamp(),
  `bank` int(11) NOT NULL,
  `accountno` varchar(20) NOT NULL,
  `ssnitid` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` text NOT NULL,
  `emailaddress` text NOT NULL,
  `subject` text NOT NULL,
  `Yourmessage` text NOT NULL,
)