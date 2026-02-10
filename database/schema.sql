-- SQLite Compatible Schema

-- Enable Foreign Keys
PRAGMA foreign_keys = ON;

-- =============================================
-- 1. MASTER DATA TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS departments (
    id TEXT PRIMARY KEY,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS positions (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    level INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS action_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    description TEXT
);

-- =============================================
-- 2. CORE TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    username TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    position_id TEXT,
    dept_id TEXT,
    is_active INTEGER DEFAULT 1, -- Boolean as 0/1
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (dept_id) REFERENCES departments(id)
);

CREATE TABLE IF NOT EXISTS workflow_definitions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    workflow_file TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 3. TRANSACTION TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS documents (
    doc_id TEXT PRIMARY KEY,
    doc_number TEXT NOT NULL UNIQUE,
    doc_title TEXT NOT NULL,
    doc_amount REAL DEFAULT 0.00,
    dateprefix TEXT,
    dept_id TEXT NOT NULL,
    workflow_id INTEGER NOT NULL,
    user_id TEXT NOT NULL,
    current_node_id TEXT,
    status TEXT DEFAULT 'Draft' CHECK( status IN ('Draft', 'In Progress', 'Completed', 'Terminated', 'Rejected') ),
    form_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id),
    FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Trigger for ON UPDATE CURRENT_TIMESTAMP
CREATE TRIGGER IF NOT EXISTS update_documents_timestamp 
AFTER UPDATE ON documents
BEGIN
    UPDATE documents SET updated_at = CURRENT_TIMESTAMP WHERE doc_id = old.doc_id;
END;

CREATE TABLE IF NOT EXISTS workflow_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id TEXT NOT NULL,
    actor_id TEXT,
    node_id TEXT NOT NULL,
    action TEXT NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(doc_id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS document_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id TEXT NOT NULL,
    file_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT,
    file_size INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(doc_id)
);

-- =============================================
-- 4. INITIAL SEED DATA
-- =============================================

INSERT OR IGNORE INTO departments (id, code, name) VALUES 
('D01', 'HR', 'Human Resources'),
('D02', 'IT', 'Information Technology'),
('D03', 'ACC', 'Accounting'),
('D04', 'SALES', 'Sales & Marketing');

INSERT OR IGNORE INTO positions (id, name, level) VALUES 
('P01', 'Officer', 1),
('P02', 'Senior Officer', 3),
('P03', 'Supervisor', 5),
('P04', 'Manager', 7),
('P05', 'Director', 10);

INSERT OR IGNORE INTO action_types (code, description) VALUES
('SUBMIT', 'ส่งเอกสารเข้าระบบ'),
('APPROVE', 'อนุมัติรายการ'),
('REJECT', 'ปฏิเสธรายการ'),
('RETURN', 'ส่งคืนแก้ไข'),
('SYSTEM', 'ระบบดำเนินการอัตโนมัติ');
