<?php
// migrate_instances.php
require_once 'api.php';

try {
    $pdo = getDB();

    // Create workflow_instances table
    $pdo->exec("CREATE TABLE IF NOT EXISTS workflow_instances (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        workflow_id INTEGER,
        workflow_name TEXT,
        status TEXT DEFAULT 'PENDING',
        current_node_id TEXT,
        data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create instance_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS instance_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        instance_id INTEGER,
        node_id TEXT,
        node_type TEXT,
        message TEXT,
        status TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(instance_id) REFERENCES workflow_instances(id)
    )");

    echo "Migration successful: workflow_instances and instance_logs tables created.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>