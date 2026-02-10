<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbPath = __DIR__ . '/database/workflow.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "DB Path: $dbPath <br>";
echo "Real Path: " . realpath($dbPath) . "<br>";

// 1. Check columns
$stmt = $pdo->query("PRAGMA table_info(workflow_definitions)");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columns: ";
foreach ($cols as $c)
    echo $c['name'] . " ";
echo "<br><br>";

// 2. Try Insert with creator_name
echo "Attempting INSERT with creator_name... <br>";
try {
    $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, workflow_file, creator_name) VALUES ('Debug', 'debug.json', 'Me')");
    $stmt->execute();
    echo "Insert Success <br>";
} catch (Exception $e) {
    echo "Insert Failed: " . $e->getMessage() . "<br>";
}

echo "<br>";

// 3. Try Insert WITHOUT creator_name
echo "Attempting INSERT WITHOUT creator_name... <br>";
try {
    $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, workflow_file) VALUES ('Debug2', 'debug2.json')");
    $stmt->execute();
    echo "Insert2 Success <br>";
} catch (Exception $e) {
    echo "Insert2 Failed: " . $e->getMessage() . "<br>";
}
?>