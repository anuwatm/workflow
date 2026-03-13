<?php
class DocumentController {

    private function sanitizeAndValidateFile($filename) {
        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'ppt', 'pptx', 'doc', 'xls'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExtensions)) {
            return false;
        }
        
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        
        if (empty($safeBasename)) {
            $safeBasename = 'file_' . time();
        }
        
        return $safeBasename . '.' . $ext;
    }

    public function upload() {
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../docFlow';
            if (!file_exists($uploadDir)) {
                 mkdir($uploadDir, 0777, true);
            }

            $origName = basename($_FILES['file']['name']);
            $fileName = $this->sanitizeAndValidateFile($origName);
            
            if ($fileName === false) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: pdf, png, jpg, docx, xlsx, ppt, pptx, doc, xls']);
                 exit;
            }

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
    }

    public function start_document($pdo, $userId) {
        $title = $_POST['title'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $deptId = $_POST['dept_id'] ?? null;
        $workflowId = $_POST['workflow_id'] ?? null;

        if (empty($title) || empty($amount) || empty($deptId) || empty($workflowId)) {
             echo json_encode(['success' => false, 'error' => 'Missing required fields']);
             exit;
        }

        // 1. Generate Doc No
        $datePrefix = date('Ymd');
        $stmt = $pdo->prepare("SELECT doc_number FROM documents WHERE dateprefix = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$datePrefix]);
        $lastDoc = $stmt->fetch(PDO::FETCH_ASSOC);

        $runningNo = 1;
        if ($lastDoc) {
             $lastRunning = (int) substr($lastDoc['doc_number'], -4);
             $runningNo = $lastRunning + 1;
        }
        $docNo = $datePrefix . str_pad($runningNo, 4, '0', STR_PAD_LEFT);

        // 2. Parse Workflow for Next Node
        $stmt = $pdo->prepare("SELECT workflow_file FROM workflow_definitions WHERE id = ?");
        $stmt->execute([$workflowId]);
        $wfDef = $stmt->fetch(PDO::FETCH_ASSOC);

        $nextNodeId = 'Unknown';
        $status = 'START';

        if ($wfDef) {
             $filePath = __DIR__ . '/../storage/' . $wfDef['workflow_file'];
             
             // Caching logic adapted from API
             $json = null;
             if (!isset($_SESSION['workflow_cache'])) {
                 $_SESSION['workflow_cache'] = [];
             }
             if (isset($_SESSION['workflow_cache'][$filePath])) {
                 $json = $_SESSION['workflow_cache'][$filePath];
             } else if (file_exists($filePath)) {
                 $json = json_decode(file_get_contents($filePath), true);
                 $_SESSION['workflow_cache'][$filePath] = $json;
             }
             
             if ($json) {
                 $nodes = $json['nodes'] ?? [];
                 $connections = $json['connections'] ?? [];

                 $startNode = null;
                 foreach ($nodes as $n) {
                     if ($n['type'] === 'StartFlow') {
                         $startNode = $n;
                         break;
                     }
                 }

                 if ($startNode) {
                     foreach ($connections as $c) {
                         if ($c['output_node_id'] === $startNode['id']) {
                             $nextNodeId = $c['input_node_id'];
                             break;
                         }
                     }
                 }
             }
        }

        // 3. Insert Document
        $docId = uniqid('DOC-', true);
        $stmt = $pdo->prepare("INSERT INTO documents (doc_id, doc_number, doc_title, doc_amount, dept_id, user_id, workflow_id, current_node_id, status, dateprefix) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$docId, $docNo, $title, $amount, $deptId, $userId, $workflowId, $nextNodeId, $status, $datePrefix]);

        // 3.5 Log the submission
        $stmt = $pdo->prepare("INSERT INTO workflow_logs (document_id, actor_id, node_id, action, comment) VALUES (?, ?, 'StartFlow', 'SUBMIT', 'เริ่มต้นสร้างเอกสาร')");
        $stmt->execute([$docId, $userId]);

        // 4. Handle Files
        if (isset($_FILES['files'])) {
             $docDir = __DIR__ . '/../docFlow/' . $docNo . '/';
             if (!file_exists($docDir)) {
                 if (!mkdir($docDir, 0777, true)) {
                     throw new Exception("Failed to create document directory: $docNo");
                 }
             }

             $files = $_FILES['files'];
             if (is_array($files['name'])) {
                 $count = count($files['name']);
                 for ($i = 0; $i < $count; $i++) {
                     if ($files['error'][$i] === UPLOAD_ERR_OK) {
                         $tmpName = $files['tmp_name'][$i];
                         $origName = basename($files['name'][$i]);
                         $safeName = $this->sanitizeAndValidateFile($origName);
                         
                         if ($safeName === false) {
                             throw new Exception("Invalid file type: " . htmlspecialchars($origName));
                         }

                         $target = $docDir . $safeName;

                         if (move_uploaded_file($tmpName, $target)) {
                             $relativePath = $docNo . '/' . $safeName;
                             $stmt = $pdo->prepare("INSERT INTO document_files (document_id, filename, file_path) VALUES (?, ?, ?)");
                             $stmt->execute([$docId, $origName, $relativePath]);
                         }
                     }
                 }
             }
        }
        echo json_encode(['success' => true, 'doc_id' => $docId, 'doc_no' => $docNo]);
    }

    public function track_documents($pdo, $userId) {
        $sql = "
            SELECT d.doc_id as id, d.doc_number as doc_no, d.doc_title as title, d.doc_amount as amount, d.status, d.created_at, d.current_node_id as current_node, 
                   w.name as workflow_name
            FROM documents d
            LEFT JOIN workflow_definitions w ON d.workflow_id = w.id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'documents' => $documents]);
    }

    public function get_inbox($pdo, $userId) {
        // Build profiles ONCE 
        $stmt = $pdo->prepare("
            SELECT p.name as position_name, u.dept_id 
            FROM users u LEFT JOIN positions p ON u.position_id = p.id WHERE u.id = ?
            UNION ALL
            SELECT p.name, u.dept_id 
            FROM delegations del
            JOIN users u ON del.delegator_id = u.id
            LEFT JOIN positions p ON u.position_id = p.id
            WHERE del.delegatee_id = ? AND del.status = 'ACTIVE' 
              AND CURRENT_TIMESTAMP BETWEEN del.start_date AND del.end_date
        ");
        $stmt->execute([$userId, $userId]);
        $rawProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profiles = [];
        foreach ($rawProfiles as $r) {
            $profiles[] = [
                'position' => strtolower((string)$r['position_name']),
                'dept_id' => $r['dept_id']
            ];
        }

        $sql = "
             SELECT d.doc_id as id, d.doc_number as doc_no, d.doc_title as title, d.doc_amount as amount, d.status, d.created_at, d.current_node_id as current_node, d.dept_id,
                    w.name as workflow_name, w.workflow_file,
                    u.username as requester_name
             FROM documents d
             LEFT JOIN workflow_definitions w ON d.workflow_id = w.id
             LEFT JOIN users u ON d.user_id = u.id
             WHERE d.status IN ('START', 'PENDING', 'Draft', 'In Progress')
             ORDER BY d.created_at DESC
        ";
        $documents = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $inbox = [];
        $cacheDir = __DIR__ . '/../storage/array_cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        foreach ($documents as $doc) {
             $isHandler = false;
             $filename = basename($doc['workflow_file'] ?? '');
             if (!$filename) continue;

             $jsonFilePath = __DIR__ . '/../storage/' . $filename;
             $cacheFilePath = $cacheDir . $filename . '.php';
             
             $json = null;

             // System-wide Array Cache System 
             if (file_exists($cacheFilePath) && filemtime($cacheFilePath) >= filemtime($jsonFilePath)) {
                 $json = require $cacheFilePath;
             } else if (file_exists($jsonFilePath)) {
                 $json = json_decode(file_get_contents($jsonFilePath), true);
                 if ($json) {
                     // Compile cache payload 
                     $exported = var_export($json, true);
                     file_put_contents($cacheFilePath, "<?php return " . $exported . ";");
                 }
             }

             if ($json) {
                 $nodes = $json['nodes'] ?? [];                 
                 $currentNode = null;
                 foreach ($nodes as $n) {
                     if (($n['id'] ?? '') === $doc['current_node']) {
                         $currentNode = $n;
                         break;
                     }
                 }

                 if ($currentNode) {
                     foreach ($profiles as $profile) {
                         if ($currentNode['type'] === 'OfficerReview') {
                             $requiredPosition = strtolower($currentNode['widgets_values']['Review'] ?? '');
                             if ($profile['position'] === $requiredPosition && $doc['dept_id'] === $profile['dept_id']) {
                                 $isHandler = true;
                                 break 2;
                             }
                         } elseif ($currentNode['type'] === 'ManagerApproval') {
                             $requiredLevel = strtolower($currentNode['widgets_values']['level'] ?? '');
                             if ($profile['position'] === $requiredLevel && $doc['dept_id'] === $profile['dept_id']) {
                                 $isHandler = true;
                                 break 2;
                             }
                         }
                     }
                 }
             }

             if ($isHandler) {
                 unset($doc['workflow_file']);
                 $inbox[] = $doc;
             }
        }
        echo json_encode(['success' => true, 'documents' => $inbox]);
    }

    public function process_document($pdo, $userId) {
        $docId = $_POST['doc_id'] ?? '';
        $decision = $_POST['decision'] ?? '';
        $remark = $_POST['remark'] ?? '';

        if (empty($docId) || !in_array($decision, ['APPROVE', 'REJECT'])) {
             echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
             exit;
        }

        $stmt = $pdo->prepare("SELECT current_node_id as current_node, workflow_id FROM documents WHERE doc_id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
             throw new Exception("Document not found");
        }

        $stmt = $pdo->prepare("SELECT workflow_file FROM workflow_definitions WHERE id = ?");
        $stmt->execute([$doc['workflow_id']]);
        $wfDef = $stmt->fetch(PDO::FETCH_ASSOC);

        $newStatus = 'In Progress';
        $nextNodeId = $doc['current_node'];

        if ($wfDef) {
             $filePath = __DIR__ . '/../storage/' . $wfDef['workflow_file'];
             if (file_exists($filePath)) {
                 $json = json_decode(file_get_contents($filePath), true);
                 $connections = $json['connections'] ?? [];
                 
                 $targetSockets = [];
                 if ($decision === 'APPROVE') {
                     $targetSockets = ['PASSED', 'APPROVED', 'DONE', 'TRUE', 'FLOW', 'start'];
                 } else if ($decision === 'REJECT') {
                     $targetSockets = ['REJECTED', 'DENIED', 'FALSE'];
                 }

                 $foundNext = false;
                 foreach ($connections as $c) {
                     if ($c['output_node_id'] === $doc['current_node']) {
                         if (in_array($c['output_name'], $targetSockets)) {
                             $nextNodeId = $c['input_node_id'];
                             $foundNext = true;
                             
                             $nodes = $json['nodes'] ?? [];
                             foreach ($nodes as $n) {
                                 if ($n['id'] === $nextNodeId && $n['type'] === 'EndFlow') {
                                     $statusWidget = 'Completed';
                                     if (isset($n['widgets_values']['status'])) {
                                         $statusWidget = $n['widgets_values']['status'];
                                     }
                                     
                                     $widgetLower = strtolower($statusWidget);
                                     if (strpos($widgetLower, 'terminate') !== false) {
                                         $newStatus = 'Terminated';
                                     } else {
                                         $newStatus = 'Completed';
                                     }
                                     break;
                                 }
                             }
                             break;
                         }
                     }
                 }
                 
                 if (!$foundNext) {
                     $newStatus = ($decision === 'REJECT') ? 'Rejected' : 'Completed';
                 }
             } else {
                 $newStatus = ($decision === 'REJECT') ? 'Rejected' : 'Completed';
             }
        } else {
             $newStatus = ($decision === 'REJECT') ? 'Rejected' : 'Completed';
        }

        $stmt = $pdo->prepare("UPDATE documents SET status = ?, current_node_id = ? WHERE doc_id = ?");
        $stmt->execute([$newStatus, $nextNodeId, $docId]);

        $stmt = $pdo->prepare("INSERT INTO workflow_logs (document_id, actor_id, node_id, action, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$docId, $userId, $doc['current_node'], $decision, $remark]);

        echo json_encode(['success' => true, 'new_status' => $newStatus, 'next_node' => $nextNodeId]);
    }

    public function get_document_history($pdo) {
        $docId = $_GET['doc_id'] ?? '';
        if (empty($docId)) {
             echo json_encode(['success' => false, 'error' => 'Document ID required']);
             exit;
        }

        $sql = "
             SELECT l.action, l.comment, l.created_at, l.node_id,
                    u.username as actor_name,
                    p.name as actor_position
             FROM workflow_logs l
             LEFT JOIN users u ON l.actor_id = u.id
             LEFT JOIN positions p ON u.position_id = p.id
             WHERE l.document_id = ?
             ORDER BY l.created_at ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$docId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'history' => $history]);
    }
}
