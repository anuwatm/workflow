<?php
// Test API save and load
$baseUrl = "http://localhost/workflow/api.php";

function post($url, $data)
{
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => $data,
        ],
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

function get($url)
{
    return file_get_contents($url);
}

$workflowName = "API_Test_" . time();
$workflowDesc = "Description for " . $workflowName;
$jsonData = json_encode(['nodes' => [], 'edges' => []]);

echo "1. Saving workflow '$workflowName'...\n";
$saveUrl = "$baseUrl?action=save&name=$workflowName&description=" . urlencode($workflowDesc);
$response = post($saveUrl, $jsonData);
echo "Response: $response\n";

$json = json_decode($response, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "PASS: Save successful.\n";
} else {
    echo "FAIL: Save failed.\n";
    exit;
}

echo "\n2. Verify DB content...\n";
$dbPath = __DIR__ . '/database/workflow.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$stmt = $pdo->prepare("SELECT workflow_file, description FROM workflow_definitions WHERE name = ?");
$stmt->execute([$workflowName]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['description'] === $workflowDesc && strpos($row['workflow_file'], $workflowName) !== false) {
    echo "PASS: DB record found with correct values. File: " . $row['workflow_file'] . "\n";
} else {
    echo "FAIL: DB record mismatch.\n";
    print_r($row);
}

echo "\n3. Loading workflow...\n";
$loadUrl = "$baseUrl?action=load&file=$workflowName";
$loadedData = get($loadUrl);

if ($loadedData === $jsonData) {
    echo "PASS: Load successful and data matches.\n";
} else {
    echo "FAIL: Load returned different data.\nExpected: $jsonData\nGot: $loadedData\n";
}

echo "\n4. Cleanup...\n";
$pdo->exec("DELETE FROM workflow_definitions WHERE name = '$workflowName'");
if (file_exists(__DIR__ . '/storage/' . $row['workflow_file'])) {
    unlink(__DIR__ . '/storage/' . $row['workflow_file']);
}
echo "Cleanup done.\n";
