<?php
// We'll trust the migration script output for now, and check the schema using the same method that worked for migration.
// The migration script used PDO successfully.
// Let's create a minimal script that just checks schema.

$dbPath = __DIR__ . '/database/workflow.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    echo "Columns: " . implode(", ", $columns) . "\n";

    if (in_array('workflow_file', $columns) && !in_array('structure_json', $columns)) {
        echo "SUCCESS: Schema migrated correctly.\n";
    } else {
        echo "FAILURE: Schema mismatch.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
