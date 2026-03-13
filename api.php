<?php
session_start();
header('Content-Type: application/json');

function sanitizeAndValidateFile($filename) {
    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'ppt', 'pptx', 'doc', 'xls'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedExtensions)) {
        return false;
    }
    
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    // Remove special characters, keep alphanumeric, dash, underscore
    $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
    
    if (empty($safeBasename)) {
        $safeBasename = 'file_' . time();
    }
    
    return $safeBasename . '.' . $ext;
}
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = 'c:/LocalDevine/www/workflow/database/workflow.sqlite';
        if (!file_exists($dbPath)) {
            http_response_code(500);
            die(json_encode(['error' => 'Database not found. Please run init_db.php first.']));
        }
        try {
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    return $pdo;
}

// Autoload Controllers
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'Controller') !== false) {
        require_once __DIR__ . '/controllers/' . $class_name . '.php';
    }
});

$action = $_GET['action'] ?? '';
try {
    $pdo = getDB();
    
    // Unauthenticated Endpoints 
    if ($action === 'register') {
        (new AuthController())->register($pdo);
        exit;
    } elseif ($action === 'login') {
        (new AuthController())->login($pdo);
        exit;
    } elseif ($action === 'check_auth') {
        (new AuthController())->check_auth();
        exit;
    } elseif ($action === 'get_meta_data') {
        (new UserController())->get_meta_data($pdo);
        exit;
    }

    // Require Authentication for the rest
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';

    // Route Authenticated Actions
    switch ($action) {
        // Auth Controller
        case 'logout': (new AuthController())->logout(); break;
        
        // User Controller
        case 'get_user_details': (new UserController())->get_user_details($pdo, $userId); break;
        case 'get_users': (new UserController())->get_users($pdo); break;
        case 'save_delegation': (new UserController())->save_delegation($pdo, $userId); break;
        case 'get_my_delegations': (new UserController())->get_my_delegations($pdo, $userId); break;
        case 'revoke_delegation': (new UserController())->revoke_delegation($pdo, $userId); break;

        // Workflow Controller
        case 'save': (new WorkflowController())->save($pdo, $username); break;
        case 'list': (new WorkflowController())->list($pdo); break;
        case 'load': (new WorkflowController())->load($pdo); break;
        case 'run': (new WorkflowController())->run($pdo); break;

        // Document Controller
        case 'upload': (new DocumentController())->upload(); break;
        case 'start_document': (new DocumentController())->start_document($pdo, $userId); break;
        case 'track_documents': (new DocumentController())->track_documents($pdo, $userId); break;
        case 'get_inbox': (new DocumentController())->get_inbox($pdo, $userId); break;
        case 'process_document': (new DocumentController())->process_document($pdo, $userId); break;
        case 'get_document_history': (new DocumentController())->get_document_history($pdo); break;

        // Analytics Controller
        case 'get_tracker_stats': (new AnalyticsController())->get_tracker_stats($pdo, $userId); break;
        case 'get_statistics': (new AnalyticsController())->get_statistics($pdo); break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'System Router Error: ' . $e->getMessage()]);
}
