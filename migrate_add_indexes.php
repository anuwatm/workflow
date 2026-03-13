<?php
$dbPath = __DIR__ . '/database/workflow.sqlite';

try {
    if (!file_exists($dbPath)) {
        die("Database file not found at $dbPath\n");
    }

    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";

    // Create Index on documents for tracking queries
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_documents_user_status ON documents(user_id, status);");
    echo "Index 'idx_documents_user_status' checked/created.\n";
    
    // Create Index for inbox queries where status in ('START', 'PENDING', 'Draft')
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_documents_status ON documents(status);");
    echo "Index 'idx_documents_status' checked/created.\n";

    // Create Index on workflow_logs to speed up document history rendering
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_workflow_logs_document_id ON workflow_logs(document_id);");
    echo "Index 'idx_workflow_logs_document_id' checked/created.\n";
    
    // Create Index on delegations for inbox calculations
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_delegations_match ON delegations(delegatee_id, status, start_date, end_date);");
    echo "Index 'idx_delegations_match' checked/created.\n";
    
    // Create Index on workflow_instances mapping
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_workflow_instances_workflow_name ON workflow_instances(workflow_name);");
    echo "Index 'idx_workflow_instances_workflow_name' checked/created.\n";

    echo "All indexes optimization completed successfully.\n";
} catch (PDOException $e) {
    echo "Optimization failed: " . $e->getMessage() . "\n";
}
?>
