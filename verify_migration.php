<?php
// Verify the migration and API updates

function getDB()
{
    $dbPath = __DIR__ . '/database/workflow.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

try {
    echo "1. Checking table structure...\n";
    $pdo = getDB();
    $stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    if (in_array('workflow_file', $columns)) {
        echo "PASS: 'workflow_file' column exists.\n";
    } else {
        echo "FAIL: 'workflow_file' column MISSING.\n";
    }

    if (!in_array('structure_json', $columns)) {
        echo "PASS: 'structure_json' column is gone.\n";
    } else {
        echo "FAIL: 'structure_json' column still exists.\n";
    }

    echo "\n2. Testing API (Simulated)...\n";

    // Create a dummy workflow to test save
    $workflowName = "Test_Migration_Workflow_" . time();
    $testData = json_encode(["test" => "data"]);

    // Simulate SAVE action
    $_GET['action'] = 'save';
    $_GET['name'] = $workflowName;
    $_GET['description'] = 'Test Description';

    // We can't easily simulate PHP input stream for file_get_contents('php://input') in CLI without extensive mocking or using a separate process.
    // Instead, let's just check if we can Insert manually via PDO to verify the column is writable, 
    // and then use the Code logic to read it back.

    $safeFilename = $workflowName . '.json';
    $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, description, workflow_file) VALUES (?, ?, ?)");
    $stmt->execute([$workflowName, 'Test Description', $safeFilename]);
    file_put_contents(__DIR__ . '/storage/' . $safeFilename, $testData);

    echo "Inserted test record directly to DB.\n";

    // Simulate LOAD action (which uses the updated API code logic found in api.php)
    // We will include api.php but capture output.
    // Need to reset global state
    $_GET = ['action' => 'load', 'file' => $workflowName];

    ob_start();
    include 'api.php';
    $output = ob_get_clean();

    if ($output === $testData) {
        echo "PASS: API load returned correct data from 'workflow_file' column.\n";
    } else {
        echo "FAIL: API load returned unexpected data: " . substr($output, 0, 100) . "...\n";
    }

    // Clean up
    $pdo->exec("DELETE FROM workflow_definitions WHERE name = '$workflowName'");
    if (file_exists(__DIR__ . '/storage/' . $safeFilename)) {
        unlink(__DIR__ . '/storage/' . $safeFilename);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
