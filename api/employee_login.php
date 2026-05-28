<?php
header('Content-Type: application/json');
include '../config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }

    $username = trim((string)($data['username'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($username === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'username and password are required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, username, password_hash, counter_id, is_active FROM employees WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    $hash = $employee['password_hash'] ?? '';
    if (!is_string($hash) || $hash === '' || !password_verify($password, $hash)) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Create session
    $_SESSION['employee_id'] = (int)$employee['id'];
    $_SESSION['employee_username'] = $employee['username'];
    $_SESSION['counter_id'] = (int)$employee['counter_id'];

    echo json_encode(['success' => true, 'message' => 'Login success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

