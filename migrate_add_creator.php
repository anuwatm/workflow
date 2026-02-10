<?php
// Migration: Add creator_name to workflow_definitions
$dbPath = __DIR__ . '/database/workflow.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    if (!in_array('creator_name', $columns)) {
        echo "Adding creator_name column...\n";
        $pdo->exec("ALTER TABLE workflow_definitions ADD COLUMN creator_name TEXT DEFAULT 'System'");
        echo "Column added successfully.\n";
    } else {
        echo "Column creator_name already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
