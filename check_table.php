<?php
$dbPath = __DIR__ . '/database/workflow.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "{$col['cid']} | {$col['name']} | {$col['type']} | NotNull: {$col['notnull']} | Default: {$col['dflt_value']} | PK: {$col['pk']}\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
