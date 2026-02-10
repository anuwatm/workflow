<?php
// Check table columns
$dbPath = __DIR__ . '/database/workflow.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in workflow_definitions:\n";
    foreach ($columns as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }

    // Check if workflow_definitions_old exists (in case rollback failed?)
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='workflow_definitions_old'");
    if ($stmt->fetch()) {
        echo "\nworkflow_definitions_old exists!\n";
        $stmt = $pdo->query("PRAGMA table_info(workflow_definitions_old)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns in workflow_definitions_old:\n";
        foreach ($columns as $col) {
            echo $col['name'] . " (" . $col['type'] . ")\n";
        }
    } else {
        echo "\nworkflow_definitions_old does not exist.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
