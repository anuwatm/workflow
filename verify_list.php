<?php
// Verify list API returns enhanced details
$baseUrl = "http://localhost/workflow/api.php";

function get($url)
{
    return file_get_contents($url);
}

// 1. Insert a test record with creator_name
$dbPath = __DIR__ . '/database/workflow.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$name = "List_Test_" . time();
$desc = "Test Description";
$creator = "Tester_Bot";
$file = $name . ".json";

$stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, description, workflow_file, creator_name) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $desc, $file, $creator]);

echo "Inserted test record.\n";

// 2. Call list API
$listUrl = "$baseUrl?action=list";
$response = get($listUrl);
$data = json_decode($response, true);

if ($data && isset($data['files']) && is_array($data['files'])) {
    $found = false;
    foreach ($data['files'] as $wf) {
        if ($wf['name'] === $name) {
            $found = true;
            echo "Found workflow in list.\n";

            // Check fields
            if (isset($wf['creator_name']) && $wf['creator_name'] === $creator) {
                echo "PASS: creator_name is correct.\n";
            } else {
                echo "FAIL: creator_name mismatch or missing.\n";
                print_r($wf);
            }

            if (isset($wf['created_at']) && isset($wf['updated_at'])) {
                echo "PASS: Dates are present.\n";
            } else {
                echo "FAIL: Dates missing.\n";
            }

            if (isset($wf['description']) && $wf['description'] === $desc) {
                echo "PASS: Description is correct.\n";
            } else {
                echo "FAIL: Description mismatch.\n";
            }
            break;
        }
    }

    if (!$found) {
        echo "FAIL: Test workflow not found in list.\n";
    }
} else {
    echo "FAIL: Invalid API response.\n";
    echo $response;
}

// Cleanup
$pdo->exec("DELETE FROM workflow_definitions WHERE name = '$name'");
echo "Cleanup done.\n";
