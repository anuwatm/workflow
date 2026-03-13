<?php
class UserController {

    public function get_meta_data($pdo) {
        $positions = $pdo->query("SELECT id, name FROM positions")->fetchAll(PDO::FETCH_ASSOC);
        $departments = $pdo->query("SELECT id, name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['positions' => $positions, 'departments' => $departments]);
    }

    public function get_user_details($pdo, $userId) {
        $stmt = $pdo->prepare("
            SELECT u.username, p.name as position, d.name as department 
            FROM users u
            LEFT JOIN positions p ON u.position_id = p.id
            LEFT JOIN departments d ON u.dept_id = d.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['success' => true, 'username' => $user['username'], 'position' => $user['position'], 'department' => $user['department']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User details not found']);
        }
    }

    public function get_users($pdo) {
        $stmt = $pdo->query("SELECT id, username, dept_id FROM users WHERE is_active = 1");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
    }

    public function save_delegation($pdo, $delegatorId) {
        $delegateeId = $_POST['delegatee_id'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';

        if (empty($delegateeId) || empty($startDate) || empty($endDate)) {
            echo json_encode(['success' => false, 'error' => 'All fields are required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO delegations (delegator_id, delegatee_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$delegatorId, $delegateeId, $startDate, $endDate]);
        echo json_encode(['success' => true]);
    }

    public function get_my_delegations($pdo, $delegatorId) {
        $stmt = $pdo->prepare("
            SELECT d.id, d.start_date, d.end_date, d.status, d.created_at,
                   u.username as delegatee_name, p.name as delegatee_position
            FROM delegations d
            JOIN users u ON d.delegatee_id = u.id
            LEFT JOIN positions p ON u.position_id = p.id
            WHERE d.delegator_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$delegatorId]);
        $delegations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'delegations' => $delegations]);
    }

    public function revoke_delegation($pdo, $delegatorId) {
        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Delegation ID required']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE delegations SET status = 'REVOKED' WHERE id = ? AND delegator_id = ?");
        $stmt->execute([$id, $delegatorId]);
        echo json_encode(['success' => true]);
    }
}
