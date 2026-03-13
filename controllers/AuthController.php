<?php
class AuthController {
    
    public function register($pdo) {
        $data = json_decode(file_get_contents('php://input'), true);
        $empId = $data['emp_id'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $email = $data['email'] ?? '';
        $positionId = $data['position_id'] ?? null;
        $deptId = $data['dept_id'] ?? null;

        if (empty($empId) || empty($username) || empty($password) || empty($email)) {
             echo json_encode(['success' => false, 'error' => 'All fields are required']);
             exit;
        }

        // Check if username or ID exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR id = :id");
        $stmt->execute([':username' => $username, ':id' => $empId]);
        if ($stmt->fetch()) {
             echo json_encode(['success' => false, 'error' => 'Username or Emp ID already exists']);
             exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user with new fields
        $sql = "INSERT INTO users (id, username, email, password_hash, position_id, dept_id) 
                VALUES (:id, :username, :email, :password, :pos, :dept)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $empId,
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':pos' => $positionId,
            ':dept' => $deptId
        ]);

        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    }

    public function login($pdo) {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
    }

    public function logout() {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
    }

    public function check_auth() {
        if (isset($_SESSION['user_id'])) {
            echo json_encode(['authenticated' => true, 'username' => $_SESSION['username']]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
    }
}
