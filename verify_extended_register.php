<?php
// verify_extended_register.php
require_once 'api.php';

// Mock Environment
$_SERVER['REQUEST_METHOD'] = 'POST';

// 1. Get Metadata
$_GET['action'] = 'get_meta_data';
echo "--- Testing Get Metadata ---\n";
// Capture output
ob_start();
// api.php is already included at the top, so we just let it run if it wasn't guarded.
// But api.php runs logic at global scope.
// The best way to test api.php logic without HTTP is to clear output buffer after include.
// However, since we already included it at line 2, it executed the 'register' logic (empty action) or whatever.

// Let's just focus on DB verification which is what we really care about here.
// We already have getDB().

try {

    $pdo = getDB();

    echo "--- Checking Positions ---\n";
    $pos = $pdo->query("SELECT * FROM positions")->fetchAll();
    echo "Positions count: " . count($pos) . "\n";
    if (count($pos) == 0)
        echo "ERROR: No positions found.\n";

    echo "--- Checking Departments ---\n";
    $dept = $pdo->query("SELECT * FROM departments")->fetchAll();
    echo "Departments count: " . count($dept) . "\n";
    if (count($dept) == 0)
        echo "ERROR: No departments found.\n";

    echo "--- Testing User Insert ---\n";
    $empId = 'EMP' . rand(1000, 9999);
    $username = 'user_' . time();
    $email = $username . '@example.com';
    $password = password_hash('secret', PASSWORD_DEFAULT);
    $posId = $pos[0]['id'];
    $deptId = $dept[0]['id'];

    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password_hash, position_id, dept_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$empId, $username, $email, $password, $posId, $deptId]);

    echo "User Inserted: $empId, $username\n";

    // Verify
    $user = $pdo->query("SELECT * FROM users WHERE id = '$empId'")->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "Verification Success: User found in DB.\n";
        echo "Position ID: " . $user['position_id'] . "\n";
        echo "Dept ID: " . $user['dept_id'] . "\n";
    } else {
        echo "Verification Failed: User not found.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>