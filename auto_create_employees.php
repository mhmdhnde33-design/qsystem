<?php
// Create one employee login per counter automatically.
// WARNING: This will overwrite nothing (inserts only if username doesn't exist).
// It uses a single default password you provide.
// Usage in browser:
//   http://localhost/queue-management-system/auto_create_employees.php?default_password=123456
// Optional:
//   &username_prefix=emp
//   &is_active=1

include 'config.php';

$defaultPassword = $_GET['default_password'] ?? '';
$usernamePrefix = $_GET['username_prefix'] ?? 'emp';
$isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : 1;

if ($defaultPassword === '') {
    http_response_code(400);
    echo 'Missing default_password parameter';
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

// Fetch counters
$stmt = $conn->query("SELECT id, name FROM counters ORDER BY id ASC");
$counters = $stmt->fetchAll(PDO::FETCH_ASSOC);

$created = 0;
$skipped = 0;
$rows = [];

foreach ($counters as $counter) {
    $counterId = (int)$counter['id'];
    $username = $usernamePrefix . '_' . $counterId; // example: emp_1

    // Check existing
    $check = $conn->prepare("SELECT id FROM employees WHERE username = ? LIMIT 1");
    $check->execute([$username]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $skipped++;
        $rows[] = ['username' => $username, 'counter_id' => $counterId, 'status' => 'skipped_exists'];
        continue;
    }

    $ins = $conn->prepare("INSERT INTO employees (username, password_hash, counter_id, is_active) VALUES (?, ?, ?, ?)");
    $ins->execute([$username, $passwordHash, $counterId, $isActive]);

    $created++;
    $rows[] = ['username' => $username, 'counter_id' => $counterId, 'status' => 'created'];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'created' => $created,
    'skipped' => $skipped,
    'default_password' => $defaultPassword,
    'rows' => $rows
]);

