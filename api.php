<?php
header('Content-Type: application/json');

function getDB()
{
    $dbPath = __DIR__ . '/database/workflow.sqlite';
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

if ($action === 'save') {
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
            $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, description, workflow_file) VALUES (:name, :desc, :filename)");
            $stmt->execute([':name' => $currentName, ':desc' => $description, ':filename' => $safeFilename]);
            echo json_encode(['success' => true, 'message' => 'Workflow created', 'id' => $pdo->lastInsertId(), 'filename' => $currentName]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} elseif ($action === 'list') {
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
} else {
    echo json_encode(['error' => 'Invalid action']);
}
