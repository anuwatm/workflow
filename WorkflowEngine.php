<?php
// workflow/WorkflowEngine.php

class WorkflowEngine
{
    private $pdo;
    private $instanceId;
    private $workflowData;
    private $nodesMap = [];
    private $executionCount = 0;
    private $maxExecutions = 100;

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

        $this->executionCount = 0;
        $this->log($startNode['id'], "Workflow Started", "INFO");
        $this->executeNode($startNode);
    }

    private function executeNode($node)
    {
        if ($this->executionCount > $this->maxExecutions) {
            $this->log($node['id'], "Execution Terminated (Infinite Loop Detected)", "ERROR");
            $this->updateInstanceStatus("ERROR", $node['id']);
            throw new Exception("Infinite loop detected: Maximum execution limit reached.");
        }
        $this->executionCount++;

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

        // Handle Branching (Condition)
        if ($node['type'] === 'Condition') {
            // Real Evaluation: Check data against document
            $field = $node['widgets_values']['field'] ?? '';
            $operator = $node['widgets_values']['operator'] ?? '=';
            $value = $node['widgets_values']['value'] ?? '';

            // Get Document Data (Explicit relation via metadata or workflow instances tracking)
            // Fix: Re-wrote query to prevent full scans or incorrect OR statements
            $stmt = $this->pdo->prepare("SELECT d.* FROM documents d WHERE d.doc_id IN (SELECT document_id FROM workflow_logs WHERE node_id = 'StartFlow' AND instance_id = ?)");
            // Note: Since instance schema lacks direct document relation in the provided snippet, an alternative is joining by workflow logs if we had mapped them.
            // A more direct fix based on the current schema context:
            $stmt = $this->pdo->prepare("
                SELECT d.* 
                FROM documents d 
                JOIN workflow_instances w ON d.workflow_id = w.workflow_name 
                WHERE w.id = ? 
                LIMIT 1
            ");
            $stmt->execute([$this->instanceId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            $result = false;
            if ($document) {
                $docValue = 0;
                if (strtolower($field) === 'amount') {
                    $docValue = (float) $document['doc_amount']; // FIXED: 'amount' -> 'doc_amount' to match schema
                } else if (strtolower($field) === 'department' && isset($document['dept_id'])) {
                    $docValue = $document['dept_id'];
                }

                $targetValue = (float) $value;

                switch ($operator) {
                    case '>': $result = ($docValue > $targetValue); break;
                    case '<': $result = ($docValue < $targetValue); break;
                    case '=': $result = ($docValue == $targetValue); break;
                    case '!=': $result = ($docValue != $targetValue); break;
                    case '>=': $result = ($docValue >= $targetValue); break;
                    case '<=': $result = ($docValue <= $targetValue); break;
                    default: $result = false;
                }
            } else {
                 $this->log($node['id'], "Condition Failed: Document not found for evaluation", "ERROR");
            }

            $path = $result ? 'TRUE' : 'FALSE';
            $this->log($node['id'], "Condition Evaluated -> Field: $field, Operator: $operator, Target: $value | Result: $path", "INFO");

            $pathTaken = false;
            foreach ($nextNodes as $conn) {
                if ($conn['source_socket'] === $path) {
                    $this->executeNode($this->nodesMap[$conn['target_node']]);
                    $pathTaken = true;
                    return; // Take only one path
                }
            }
            if (!$pathTaken) {
                 $this->log($node['id'], "No route found for condition result: $path", "WARNING");
            }
            return;
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