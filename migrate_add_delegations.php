<?php

$dbPath = __DIR__ . '/database/workflow.sqlite';

try {
    if (!file_exists($dbPath)) {
        die("Database file not found at $dbPath\n");
    }

    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";

    // 1. Create delegations table
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS delegations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            delegator_id INTEGER NOT NULL,
            delegatee_id INTEGER NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status TEXT DEFAULT 'ACTIVE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (delegator_id) REFERENCES users(id),
            FOREIGN KEY (delegatee_id) REFERENCES users(id)
        );
    ";

    $pdo->exec($createTableQuery);
    echo "Table 'delegations' checked/created successfully.\n";

    // 2. Add is_active to users table if it doesn't exist
    // Check if the column exists first
    $checkSql = "PRAGMA table_info(users);";
    $stmt = $pdo->query($checkSql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasIsActive = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'is_active') {
             $hasIsActive = true;
             break;
        }
    }

    if (!$hasIsActive) {
         $alterQuery = "ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1;";
         $pdo->exec($alterQuery);
         echo "Column 'is_active' added to users table.\n";
    } else {
         echo "Column 'is_active' already exists in users table.\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
