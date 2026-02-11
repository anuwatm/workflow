<?php
// setup_extended_db.php
$dbPath = __DIR__ . '/database/workflow.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Departments
    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL
    )");

    // Seed Departments
    $depts = ['HR', 'Finance', 'IT', 'Marketing', 'Operations'];
    foreach ($depts as $d) {
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
        $stmt->execute([$d]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO departments (name) VALUES (?)")->execute([$d]);
        }
    }

    // 2. Create Positions
    $pdo->exec("CREATE TABLE IF NOT EXISTS positions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL
    )");

    // Seed Positions
    $positions = ['Officer', 'Senior Officer', 'Supervisor', 'Manager', 'Director'];
    foreach ($positions as $p) {
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE name = ?");
        $stmt->execute([$p]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO positions (name) VALUES (?)")->execute([$p]);
        }
    }

    echo "Departments and Positions created and seeded.\n";

    // 3. Re-create Users Table to match requirements
    // Requirements: id (EmpID), username, email, password_hash, position_id, dept_id

    // Check if we need to migrate existing data or just wipe. 
    // For safety, let's rename old table if it has the old schema.

    // Check if 'password_hash' column exists
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasHashCol = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'password_hash')
            $hasHashCol = true;
    }

    if (!$hasHashCol) {
        // Migration needed: Rename old table
        $pdo->exec("ALTER TABLE users RENAME TO users_backup_" . time());
        echo "Backed up existing users table.\n";

        // Create new table
        // Note: id is NOT Autoincrement to allow Manual EmpID entry
        $sql = "CREATE TABLE users (
            id TEXT PRIMARY KEY, 
            username TEXT NOT NULL UNIQUE,
            email TEXT,
            password_hash TEXT NOT NULL,
            position_id INTEGER,
            dept_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(position_id) REFERENCES positions(id),
            FOREIGN KEY(dept_id) REFERENCES departments(id)
        )";
        $pdo->exec($sql);
        echo "Created new users table with extended fields.\n";
    } else {
        echo "Users table already appears to have new schema.\n";
    }

    echo "Database setup complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>