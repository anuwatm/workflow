<?php
session_start();
header('Content-Type: application/json');

function getDB()
{
    $dbPath = 'c:/LocalDevine/www/workflow/database/workflow.sqlite';
    if (!file_exists($dbPath)) {
        http_response_code(500);
        die(json_encode(['error' => 'Database not found. Please run init_db.php first.']));
    }
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
    }
}

$action = $_GET['action'] ?? '';


if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $empId = $data['emp_id'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $email = $data['email'] ?? '';
    $positionId = $data['position_id'] ?? null;
    $deptId = $data['dept_id'] ?? null;

    if (empty($empId) || empty($username) || empty($password) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    try {
        $pdo = getDB();

        // Check if username or ID exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR id = :id");
        $stmt->execute([':username' => $username, ':id' => $empId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username or Emp ID already exists']);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user with new fields
        $sql = "INSERT INTO users (id, username, email, password_hash, position_id, dept_id) 
                VALUES (:id, :username, :email, :password, :pos, :dept)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $empId,
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':pos' => $positionId,
            ':dept' => $deptId
        ]);

        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($action === 'get_meta_data') {
    try {
        $pdo = getDB();
        $positions = $pdo->query("SELECT id, name FROM positions")->fetchAll(PDO::FETCH_ASSOC);
        $departments = $pdo->query("SELECT id, name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['positions' => $positions, 'departments' => $departments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Username and password are required']);
        exit;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);

} elseif ($action === 'check_auth') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['authenticated' => true, 'username' => $_SESSION['username']]);
    } else {
        echo json_encode(['authenticated' => false]);
    }

} elseif ($action === 'save') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = file_get_contents('php://input'); // This acts as structure_json
    $currentName = 'Untitled Workflow';
    $description = $_GET['description'] ?? '';

    if (isset($_GET['name'])) {
        $currentName = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_GET['name']);
    }

    // Generate a safe filename for storage
    $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $currentName) . '.json';
    $storagePath = __DIR__ . '/storage/' . $safeFilename;

    try {
        // 1. Save JSON to file
        if (file_put_contents($storagePath, $data) === false) {
            throw new Exception("Failed to write to storage file: $safeFilename");
        }

        $pdo = getDB();

        // 2. Update Database
        // Check if workflow with name exists
        $stmt = $pdo->prepare("SELECT id FROM workflow_definitions WHERE name = :name");
        $stmt->execute([':name' => $currentName]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE workflow_definitions SET workflow_file = :filename, description = :desc, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':filename' => $safeFilename, ':desc' => $description, ':id' => $existing['id']]);
            echo json_encode(['success' => true, 'message' => 'Workflow updated', 'id' => $existing['id'], 'filename' => $currentName]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, description, workflow_file, creator_name) VALUES (:name, :desc, :filename, :creator)");
            $stmt->execute([
                ':name' => $currentName,
                ':desc' => $description,
                ':filename' => $safeFilename,
                ':creator' => $_SESSION['username']
            ]);
            echo json_encode(['success' => true, 'message' => 'Workflow created', 'id' => $pdo->lastInsertId(), 'filename' => $currentName]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'e: ' . $e->getMessage()]);
    }

} elseif ($action === 'list') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    try {
        $pdo = getDB();
        // Return detailed list
        $stmt = $pdo->query("SELECT id, name, description, creator_name, created_at, updated_at, workflow_file FROM workflow_definitions ORDER BY updated_at DESC");
        $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['files' => $workflows]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'load') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $name = $_GET['file'] ?? '';

    try {
        $pdo = getDB();
        // Get the filename from DB
        $stmt = $pdo->prepare("SELECT workflow_file, description, name FROM workflow_definitions WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $filename = $row['workflow_file'];
            $filePath = __DIR__ . '/storage/' . $filename;

            if (is_file($filePath)) {
                $content = file_get_contents($filePath);
                $jsonContent = json_decode($content);
                // Return wrapped response
                echo json_encode([
                    'meta' => [
                        'name' => $row['name'],
                        'description' => $row['description']
                    ],
                    'content' => $jsonContent
                ]);
            } else {
                // Fallback if file missing but DB says it's there?
                $json = json_decode($filename);
                if ($json && json_last_error() == JSON_ERROR_NONE) {
                    echo $filename;
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Storage file not found']);
                }
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Workflow not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} elseif ($action === 'upload') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/docFlow';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['file']['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        $targetPath = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => true, 'filename' => $fileName]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    }
} elseif ($action === 'run') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['workflow_json'])) {
        echo json_encode(['success' => false, 'error' => 'No workflow data provided']);
        exit;
    }

    $json = $data['workflow_json'];
    $workflowName = $data['name'] ?? 'Untitled Execution';

    try {
        require_once 'WorkflowEngine.php';
        $pdo = getDB();

        // Create Instance Record
        $stmt = $pdo->prepare("INSERT INTO workflow_instances (workflow_name, status, data) VALUES (?, 'PENDING', ?)");
        $stmt->execute([$workflowName, $json]);
        $instanceId = $pdo->lastInsertId();

        // Execution
        $engine = new WorkflowEngine($pdo, $instanceId);
        $engine->start($json);

        echo json_encode(['success' => true, 'message' => 'Workflow executed successfully', 'instance_id' => $instanceId]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Execution Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
