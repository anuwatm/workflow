<?php
// workflow/WorkflowEngine.php

class WorkflowEngine
{
    private $pdo;
    private $instanceId;
    private $workflowData;
    private $nodesMap = [];

    public function __construct($pdo, $instanceId)
    {
        $this->pdo = $pdo;
        $this->instanceId = $instanceId;
    }

    public function start($jsonContent)
    {
        $this->workflowData = json_decode($jsonContent, true);
        if (!$this->workflowData) {
            throw new Exception("Invalid JSON Workflow Data");
        }

        // Map nodes for easy access
        foreach ($this->workflowData['nodes'] as $node) {
            $this->nodesMap[$node['id']] = $node;
        }

        // Find Start Node
        $startNode = null;
        foreach ($this->workflowData['nodes'] as $node) {
            if ($node['type'] === 'StartFlow') {
                $startNode = $node;
                break;
            }
        }

        if (!$startNode) {
            throw new Exception("No StartFlow node found.");
        }

        $this->log($startNode['id'], "Workflow Started", "INFO");
        $this->executeNode($startNode);
    }

    private function executeNode($node)
    {
        $this->updateInstanceStatus("RUNNING", $node['id']);
        $this->log($node['id'], "Executing Node: " . $node['type'], "INFO");

        // Simulate processing (e.g. System Action)
        if ($node['type'] === 'SystemAction') {
            $action = $node['widgets_values']['action'] ?? 'Unknown Action';
            $this->log($node['id'], "System Action Performed: $action", "SUCCESS");
        }

        // Logic for Next Node
        $nextNodes = $this->getNextNodes($node);

        if (empty($nextNodes)) {
            if ($node['type'] === 'EndFlow') {
                $status = $node['widgets_values']['status'] ?? 'Completed';
                $this->updateInstanceStatus($status, $node['id']);
                $this->log($node['id'], "Workflow Ended: $status", "SUCCESS");
            } else {
                $this->log($node['id'], "Flow stopped (Dead end)", "WARNING");
            }
            return;
        }

        // Handle Branding (Condition)
        if ($node['type'] === 'Condition') {
            // Mock Evaluation: Randomly pick TRUE or FALSE for now
            // In real app, check data
            $result = (rand(0, 1) === 1);
            $path = $result ? 'TRUE' : 'FALSE';
            $this->log($node['id'], "Condition Evaluated: $path", "INFO");

            foreach ($nextNodes as $conn) {
                if ($conn['source_socket'] === $path) {
                    $this->executeNode($this->nodesMap[$conn['target_node']]);
                    return; // Take only one path
                }
            }
        }

        // Default: Execute all next nodes (Parallel)
        foreach ($nextNodes as $conn) {
            $this->executeNode($this->nodesMap[$conn['target_node']]);
        }
    }

    private function getNextNodes($node)
    {
        $next = [];
        // Helper to find connections where source is this node
        foreach ($this->workflowData['connections'] as $conn) {
            if ($conn['output_node_id'] === $node['id']) {
                $next[] = [
                    'target_node' => $conn['input_node_id'],
                    'source_socket' => $conn['output_name']
                ];
            }
        }
        return $next;
    }

    private function log($nodeId, $message, $status)
    {
        $stmt = $this->pdo->prepare("INSERT INTO instance_logs (instance_id, node_id, message, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->instanceId, $nodeId, $message, $status]);
    }

    private function updateInstanceStatus($status, $nodeId)
    {
        $stmt = $this->pdo->prepare("UPDATE workflow_instances SET status = ?, current_node_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $nodeId, $this->instanceId]);
    }
}
?>