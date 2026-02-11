<?php
// verify_execution.php
session_start();
$_SESSION['user_id'] = 1; // Mock Auth

require_once 'api.php';
require_once 'WorkflowEngine.php';

// Mock Data: Simple Flow
// Start -> SystemAction -> End
$workflowJson = json_encode([
    'nodes' => [
        ['id' => 'Start1', 'type' => 'StartFlow', 'x' => 0, 'y' => 0, 'widgets_values' => []],
        ['id' => 'Action1', 'type' => 'SystemAction', 'x' => 200, 'y' => 0, 'widgets_values' => ['action' => 'Test Log']],
        ['id' => 'End1', 'type' => 'EndFlow', 'x' => 400, 'y' => 0, 'widgets_values' => ['status' => 'Completed']]
    ],
    'connections' => [
        [
            'output_node_id' => 'Start1',
            'output_name' => 'start',
            'input_node_id' => 'Action1',
            'input_name' => 'EXEC'
        ],
        [
            'output_node_id' => 'Action1',
            'output_name' => 'DONE',
            'input_node_id' => 'End1',
            'input_name' => 'EXEC'
        ]
    ]
]);

echo "--- Simulating Workflow Execution ---\n";

try {
    $pdo = getDB();

    // 1. Create Instance
    $stmt = $pdo->prepare("INSERT INTO workflow_instances (workflow_name, status, data) VALUES (?, 'PENDING', ?)");
    $stmt->execute(['Test Execution', $workflowJson]);
    $instanceId = $pdo->lastInsertId();

    echo "Instance Created: ID $instanceId\n";

    // 2. Run Engine
    $engine = new WorkflowEngine($pdo, $instanceId);
    $engine->start($workflowJson);

    echo "Engine Execution Finished.\n";

    // 3. Verify Logs
    echo "\n--- Verify Logs ---\n";
    $stmt = $pdo->prepare("SELECT * FROM instance_logs WHERE instance_id = ? ORDER BY id ASC");
    $stmt->execute([$instanceId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        echo "[{$log['status']}] Node {$log['node_id']}: {$log['message']}\n";
    }

    // 4. Verify Final Status
    echo "\n--- Verify Status ---\n";
    $stmt = $pdo->prepare("SELECT status FROM workflow_instances WHERE id = ?");
    $stmt->execute([$instanceId]);
    $status = $stmt->fetchColumn();
    echo "Final Instance Status: $status\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>