<?php
class WorkflowController {

    public function save($pdo, $username) {
        $data = file_get_contents('php://input'); 
        $currentName = 'Untitled Workflow';
        $description = $_GET['description'] ?? '';

        if (isset($_GET['name'])) {
            $currentName = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_GET['name']);
        }

        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $currentName) . '.json';
        $storagePath = __DIR__ . '/../storage/' . $safeFilename;

        // 1. Save JSON to file
        if (file_put_contents($storagePath, $data) === false) {
             throw new Exception("Failed to write to storage file: $safeFilename");
        }

        // 2. Update Database
        $stmt = $pdo->prepare("SELECT id FROM workflow_definitions WHERE name = :name");
        $stmt->execute([':name' => $currentName]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
             $stmt = $pdo->prepare("UPDATE workflow_definitions SET workflow_file = :filename, description = :desc, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
             $stmt->execute([':filename' => $safeFilename, ':desc' => $description, ':id' => $existing['id']]);
             echo json_encode(['success' => true, 'message' => 'Workflow updated', 'id' => $existing['id'], 'filename' => $currentName]);
        } else {
             $stmt = $pdo->prepare("INSERT INTO workflow_definitions (name, description, workflow_file, creator_name) VALUES (:name, :desc, :filename, :creator)");
             $stmt->execute([
                 ':name' => $currentName,
                 ':desc' => $description,
                 ':filename' => $safeFilename,
                 ':creator' => $username
             ]);
             echo json_encode(['success' => true, 'message' => 'Workflow created', 'id' => $pdo->lastInsertId(), 'filename' => $currentName]);
        }
    }

    public function list($pdo) {
        $stmt = $pdo->query("SELECT id, name, description, creator_name, created_at, updated_at, workflow_file FROM workflow_definitions ORDER BY updated_at DESC");
        $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['files' => $workflows]);
    }

    public function load($pdo) {
        $name = $_GET['file'] ?? '';

        $stmt = $pdo->prepare("SELECT workflow_file, description, name FROM workflow_definitions WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
             $filename = $row['workflow_file'];
             $filePath = __DIR__ . '/../storage/' . $filename;

             if (is_file($filePath)) {
                 $content = file_get_contents($filePath);
                 $jsonContent = json_decode($content);
                 echo json_encode([
                     'meta' => [
                         'name' => $row['name'],
                         'description' => $row['description']
                     ],
                     'content' => $jsonContent
                 ]);
             } else {
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
    }

    public function run($pdo) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['workflow_json'])) {
             echo json_encode(['success' => false, 'error' => 'No workflow data provided']);
             exit;
        }

        $json = $data['workflow_json'];
        $workflowName = $data['name'] ?? 'Untitled Execution';

        require_once __DIR__ . '/../WorkflowEngine.php';

        $stmt = $pdo->prepare("INSERT INTO workflow_instances (workflow_name, status, data) VALUES (?, 'PENDING', ?)");
        $stmt->execute([$workflowName, $json]);
        $instanceId = $pdo->lastInsertId();

        $engine = new WorkflowEngine($pdo, $instanceId);
        $engine->start($json);

        echo json_encode(['success' => true, 'message' => 'Workflow executed successfully', 'instance_id' => $instanceId]);
    }
}
