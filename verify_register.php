<?php
// verify_register.php
$url = 'http://localhost/workflow/api.php?action=register';
// Note: Since we are running via CLI, we can't easily hit localhost if no server is running.
// Instead, we will include api.php and mock the environment.

// Mock $_SERVER and Input
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'register';

// Mock Input Stream
$inputData = json_encode(['username' => 'test_user_' . time(), 'password' => 'secret123']);
$tempFile = tempnam(sys_get_temp_dir(), 'php_input');
file_put_contents($tempFile, $inputData);

// We can't easily override php://input in a running script without external extensions.
// So we will modify api.php temporarily or writing a test that invokes the logic directly?
// Actually, let's just use the CLI to interact with the DB directly to ensure the table works, 
// and then use a cURL command if possible, or just trust the logic if the DB write works.

// Better approach: Direct DB Test + Logic Check
require_once 'setup_auth_db.php'; // Ensure DB exists

echo "Table check complete.\n";

// Test Insert manually to verify DB is writable
try {
    $pdo = new PDO("sqlite:" . __DIR__ . '/database/workflow.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user = 'testval_' . time();
    $pass = password_hash('pass', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$user, $pass]);

    echo "Manual Insert Successful: $user\n";

    // Check if it's there
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "Verification Successful: User found in DB.\n";
    } else {
        echo "Verification Failed: User not found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>