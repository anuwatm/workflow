<?php
$dbPath = __DIR__ . '/database/workflow.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $stmt = $pdo->query("SELECT * FROM workflow_definitions LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Count: " . count($rows) . "\n";
    print_r($rows);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
