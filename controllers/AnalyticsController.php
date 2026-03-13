<?php

class AnalyticsController {

    public function get_tracker_stats($pdo, $userId) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'completed' => 0,
            'rejected' => 0
        ];

        // Total
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total'] = $stmt->fetchColumn();

        // Pending
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE user_id = ? AND status IN ('START', 'PENDING', 'Draft', 'In Progress')");
        $stmt->execute([$userId]);
        $stats['pending'] = $stmt->fetchColumn();

        // Completed
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE user_id = ? AND status = 'COMPLETED'");
        $stmt->execute([$userId]);
        $stats['completed'] = $stmt->fetchColumn();

        // Rejected
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE user_id = ? AND status = 'REJECTED'");
        $stmt->execute([$userId]);
        $stats['rejected'] = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    public function get_statistics($pdo) {
        $stats = [];

        // 1. Total Volume
        $stats['total_docs'] = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
        $stats['total_amount'] = $pdo->query("SELECT SUM(doc_amount) FROM documents")->fetchColumn() ?: 0;

        // 2. By Department
        $stmt = $pdo->query("SELECT de.name, COUNT(d.doc_id) as count FROM documents d JOIN departments de ON d.dept_id = de.id GROUP BY de.name");
        $stats['by_dept'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. By Status
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM documents GROUP BY status");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
