<?php
// Migration script for workflow_definitions table
// 1. Add workflow_file column
// 2. Copy data from structure_json to workflow_file
// 3. Remove structure_json column

/*
Since "DROP COLUMN" support in SQLite is version dependent (Added in 3.35.0+),
and we might be on an older version or want maximum compatibility,
we will use the "table recreate" strategy which is safer and universal for SQLite.

Steps:
1. Rename old table to workflow_definitions_old
2. Create new table with desired schema
3. Copy data from old table to new table
4. Drop old table
*/

$dbPath = __DIR__ . '/database/workflow.sqlite';

if (!file_exists($dbPath)) {
    die("Database not found at $dbPath\n");
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting migration...\n";

    $pdo->beginTransaction();

    // 1. Rename old table
    echo "Renaming old table...\n";
    $pdo->exec("ALTER TABLE workflow_definitions RENAME TO workflow_definitions_old");

    // 2. Create new table
    echo "Creating new table...\n";
    // Keeping same structure but replacing structure_json with workflow_file
    $sqlCreate = "CREATE TABLE workflow_definitions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        workflow_file TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlCreate);

    // 3. Copy data
    // Map structure_json -> workflow_file
    echo "Copying data...\n";
    $sqlCopy = "INSERT INTO workflow_definitions (id, name, description, workflow_file, is_active, created_at)
                SELECT id, name, description, structure_json, is_active, created_at
                FROM workflow_definitions_old";
    $pdo->exec($sqlCopy);

    // 4. Drop old table
    echo "Dropping old table...\n";
    $pdo->exec("DROP TABLE workflow_definitions_old");

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
