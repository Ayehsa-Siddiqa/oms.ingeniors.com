CREATE DATABASE IF NOT EXISTS oms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oms;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets, notifications, activity_logs, documents, employee_fines, pending_tasks, requirements, project_assignments, projects, employees, departments, role_permissions, permissions, users, roles, settings;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Roles
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Permissions
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY permissions_module_action_unique (module, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role Permissions
CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    remember_token VARCHAR(255) NULL,
    remember_expires_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status),
    INDEX idx_users_deleted_at (deleted_at),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Departments
CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(120) NOT NULL UNIQUE,
    manager VARCHAR(120) NULL,
    description TEXT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_departments_status (status),
    INDEX idx_departments_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Employees
CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    department VARCHAR(120) NOT NULL,
    designation VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    joining_date DATE NOT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    profile_picture VARCHAR(255) NULL,
    emergency_contact VARCHAR(160) NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employees_department (department),
    INDEX idx_employees_status (status),
    INDEX idx_employees_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Projects
CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(180) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'In House',
    description TEXT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'Direct',
    priority ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    delivery_date DATE NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Not Started',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    assigned_to VARCHAR(100) NULL,
    notes TEXT NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projects_status (status),
    INDEX idx_projects_deadline (deadline),
    INDEX idx_projects_priority (priority),
    INDEX idx_projects_deleted_at (deleted_at),
    CONSTRAINT fk_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Project Assignments
CREATE TABLE project_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY project_employee_unique (project_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Requirements
CREATE TABLE requirements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item VARCHAR(180) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    priority ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    required_date DATE NOT NULL,
    vendor VARCHAR(160) NULL,
    estimated_cost DECIMAL(12,2) DEFAULT 0,
    status ENUM('Pending','Ordered','Purchased','Received') NOT NULL DEFAULT 'Pending',
    remarks TEXT NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_requirements_status (status),
    INDEX idx_requirements_required_date (required_date),
    INDEX idx_requirements_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Pending Tasks
CREATE TABLE pending_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_title VARCHAR(180) NOT NULL,
    category VARCHAR(120) NULL,
    priority ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    required_date DATE NULL,
    assigned_to VARCHAR(160) NULL,
    status ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    remarks TEXT NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pending_tasks_status (status),
    INDEX idx_pending_tasks_required_date (required_date),
    INDEX idx_pending_tasks_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Employee Fines
CREATE TABLE employee_fines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(120) NOT NULL,
    reason TEXT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    fine_date DATE NOT NULL,
    paid ENUM('No','Yes') NOT NULL DEFAULT 'No',
    approved_by VARCHAR(120) NULL,
    remarks TEXT NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_fines_paid (paid),
    INDEX idx_employee_fines_fine_date (fine_date),
    INDEX idx_employee_fines_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Documents
CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(120) NULL,
    version VARCHAR(40) DEFAULT '1.0',
    description TEXT NULL,
    file_path VARCHAR(255) NULL,
    deleted_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documents_category (category),
    INDEX idx_documents_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Activity Logs
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(180) NOT NULL,
    module VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    browser VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_logs_user (user_id),
    INDEX idx_activity_logs_created_at (created_at),
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Notifications
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Settings
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Password Resets
CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO roles (id, name, description) VALUES
(1, 'Super Admin', 'Complete system access with full admin permissions.'),
(2, 'Admin', 'Manages operations, projects, employees, and requirements.'),
(3, 'Employee', 'Can view and update assigned projects and tasks.');

INSERT INTO permissions (module, action) VALUES
('dashboard','view'),
('projects','manage'),
('employees','manage'),
('departments','manage'),
('requirements','manage'),
('pending_tasks','manage'),
('fines','manage'),
('documents','manage'),
('reports','view'),
('settings','manage'),
('users','manage'),
('roles','manage'),
('permissions','manage'),
('profile','manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module NOT IN ('settings', 'users', 'roles', 'permissions');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE module IN ('dashboard', 'projects', 'pending_tasks', 'documents', 'profile');

-- Default Users (Password is 'password' for all)
INSERT INTO users (id, role_id, name, email, password, status) VALUES
(1, 1, 'Super Admin', 'superadmin@oms.test', '$2y$10$4vDb2cAwTZTE/ydGp51F2eV9MX0W.M0t/BPJ4BX8KXmb/KjGCA7RC', 'Active'),
(2, 1, 'Ashu Ingeniors', 'ashu@ingeniors.com', '$2y$10$4vDb2cAwTZTE/ydGp51F2eV9MX0W.M0t/BPJ4BX8KXmb/KjGCA7RC', 'Active'),
(3, 2, 'Operations Admin', 'admin@oms.test', '$2y$10$4vDb2cAwTZTE/ydGp51F2eV9MX0W.M0t/BPJ4BX8KXmb/KjGCA7RC', 'Active'),
(4, 3, 'Employee User', 'employee@oms.test', '$2y$10$4vDb2cAwTZTE/ydGp51F2eV9MX0W.M0t/BPJ4BX8KXmb/KjGCA7RC', 'Active');

-- Departments
INSERT INTO departments (id, department_name, manager, description, status, created_by, updated_by) VALUES
(1, 'Structural Engineering', 'Ahsan Khan', 'Structural analysis, steel, RCC, and design review.', 'Active', 1, 1),
(2, 'MEP Engineering', 'Mariam Sheikh', 'Mechanical, electrical, and plumbing drafting.', 'Active', 1, 1),
(3, 'CAD Drafting', 'Bilal Ahmed', 'Shop drawings, coordination drawings, and revisions.', 'Active', 1, 1),
(4, 'Quality Assurance', 'Zainab Fatima', 'Drawing compliance and standard verification.', 'Active', 1, 1);

-- Employees
INSERT INTO employees (id, employee_code, name, department, designation, phone, email, joining_date, status, emergency_contact, created_by, updated_by) VALUES
(1, 'EMP-001', 'Ahsan Khan', 'Structural Engineering', 'Senior Structural Engineer', '+92-300-1111111', 'ahsan@ingeniors.com', '2024-02-01', 'Active', '+92-300-9000001', 1, 1),
(2, 'EMP-002', 'Mariam Sheikh', 'MEP Engineering', 'MEP Lead Engineer', '+92-300-2222222', 'mariam@ingeniors.com', '2024-05-15', 'Active', '+92-300-9000002', 1, 1),
(3, 'EMP-003', 'Bilal Ahmed', 'CAD Drafting', 'Senior CAD Specialist', '+92-300-3333333', 'bilal@ingeniors.com', '2023-11-10', 'Active', '+92-300-9000003', 1, 1),
(4, 'EMP-004', 'Zainab Fatima', 'Quality Assurance', 'QA Inspector', '+92-300-4444444', 'zainab@ingeniors.com', '2024-01-20', 'Active', '+92-300-9000004', 1, 1),
(5, 'EMP-005', 'Hamza Tariq', 'Structural Engineering', 'Junior Steel Detailer', '+92-300-5555555', 'hamza@ingeniors.com', '2024-08-01', 'Active', '+92-300-9000005', 1, 1);

-- Projects
INSERT INTO projects (id, project_name, description, source, priority, start_date, deadline, delivery_date, status, progress, notes, created_by, updated_by) VALUES
(1, 'Steel Warehouse Frame Design', 'Complete structural calculation package with 3D Tekla models and shop drawings.', 'Upwork', 'High', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'In Progress', 65, 'Client requested calculations report in metric units.', 1, 1),
(2, 'Residential MEP Drawings Package', 'Complete MEP drawing set for 3-story residential villa project.', 'Fiverr', 'Medium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'In Review', 85, 'Plumbing schematics completed, reviewing electrical layouts.', 1, 1),
(3, 'Staircase Fabrication Drawings', 'Detailed connection details and steel staircase fabrication drawings.', 'Direct', 'Low', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, 'Waiting on Client', 25, 'Waiting for updated site measurements.', 1, 1),
(4, 'Commercial Complex HVAC Layout', 'Duct sizing, load calculations, and equipment schedule drawings.', 'WhatsApp', 'High', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'Revision', 90, 'Client requested relocated chiller unit on roof.', 1, 1),
(5, 'Industrial Shed Foundation Plan', 'RCC pad foundation design with anchor bolt layout drawings.', 'Upwork', 'Medium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 9 DAY), NULL, 'Not Started', 0, 'Soil report received, commencing calculation sheets.', 1, 1),
(6, 'Pedestrian Footbridge Design', 'Steel arch truss footbridge calculation and GA drawings.', 'Direct', 'High', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Delivered', 100, 'Approved by municipal authority with zero comments.', 1, 1);

-- Project Assignments
INSERT INTO project_assignments (project_id, employee_id) VALUES
(1, 1), (1, 3),
(2, 2), (2, 3),
(3, 1), (3, 5),
(4, 2), (4, 4),
(5, 1), (5, 5),
(6, 1), (6, 3);

-- Requirements / Purchases
INSERT INTO requirements (id, item, quantity, priority, required_date, vendor, estimated_cost, status, remarks, created_by, updated_by) VALUES
(1, 'A3 Color Laser Toner Set', 2, 'Medium', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Office Supply Express', 140.00, 'Pending', 'Needed for client submission drawing prints.', 1, 1),
(2, 'High-Performance 2TB NVMe SSD', 2, 'High', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TechZone Distributors', 180.00, 'Ordered', 'Workstation upgrade for Tekla 3D modeling.', 1, 1),
(3, '27-inch 4K Designer Monitor', 1, 'High', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Dell Direct', 320.00, 'Pending', 'For CAD coordination workstation.', 1, 1),
(4, 'Laser Distance Meter (100m)', 1, 'Low', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Bosch Tools', 85.00, 'Purchased', 'Site survey inspection tool.', 1, 1);

-- Pending Tasks
INSERT INTO pending_tasks (id, task_title, category, priority, required_date, assigned_to, status, remarks, created_by, updated_by) VALUES
(1, 'Weekly project milestone review meeting', 'Coordination', 'High', CURDATE(), 'Ahsan Khan', 'In Progress', 'Prepare slide deck for client status update.', 1, 1),
(2, 'Renew AutoCAD network license server', 'IT Support', 'High', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Operations Admin', 'Pending', 'Ensure 10 seat tokens are active.', 1, 1),
(3, 'Client site inspection for Staircase project', 'Site Visit', 'Medium', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Bilal Ahmed', 'Pending', 'Take field measurements of landing beams.', 1, 1),
(4, 'Archive completed Q3 project drawings to cloud', 'Maintenance', 'Low', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Zainab Fatima', 'Pending', 'Upload to company secure storage.', 1, 1);

-- Employee Fines
INSERT INTO employee_fines (id, employee_name, reason, amount, fine_date, paid, approved_by, remarks, created_by, updated_by) VALUES
(1, 'Bilal Ahmed', 'Unannounced absence during critical client drawing submittal.', 20.00, CURDATE(), 'No', 'Operations Admin', 'First warning letter issued.', 1, 1),
(2, 'Hamza Tariq', 'Late arrival exceeding 45 minutes without notice.', 10.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Yes', 'Operations Admin', 'Deducted from salary slip.', 1, 1);

-- Documents
INSERT INTO documents (id, title, category, version, description, file_path, created_by, updated_by) VALUES
(1, 'Company CAD Layer & Drafting Standards', 'Engineering', '2.1', 'Official standard guidelines for AutoCAD and Revit deliverables.', NULL, 1, 1),
(2, 'Tekla 3D Modeling Checklist', 'Structural', '1.4', 'Quality verification checklist for steel connection modeling.', NULL, 1, 1),
(3, 'Employee Operations Handbook', 'Human Resources', '3.0', 'Policies, office hours, leave procedure, and submission rules.', NULL, 1, 1);

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'INGENIORS'),
('company_email', 'info@ingeniors.com'),
('theme_color', '#4f8cff'),
('logo', '');

-- Notifications
INSERT INTO notifications (user_id, title, message) VALUES
(1, 'Welcome to INGENIORS OMS', 'Your engineering operations management workspace is fully configured and ready.'),
(1, 'HVAC Project Urgent Revision', 'Commercial Complex HVAC Layout has received revisions due in 2 days.'),
(2, 'Welcome Ashu', 'System initialized with full Super Admin privileges.');

-- 17. Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'General',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_announcements_dates (start_date, end_date),
    INDEX idx_announcements_created_at (created_at),
    INDEX idx_announcements_deleted_at (deleted_at),
    CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO announcements (id, title, content, type, start_date, end_date, created_by) VALUES
(1, 'All-Hands Engineering Review', 'Monthly review meeting scheduled this Friday at 3:00 PM. Please have your project progress sheets updated.', 'Important', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1),
(2, 'AutoCAD & Tekla Network Updates', 'Server maintenance will occur tonight at 11:00 PM. Save and close all local drawing files before leaving.', 'Urgent', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1);

