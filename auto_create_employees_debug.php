<?php
// Debug version of auto_create_employees.php
// Shows whether counters are found and whether insert succeeds.

include 'config.php';

$defaultPassword = $_GET['default_password'] ?? '';
$usernamePrefix = $_GET['username_prefix'] ?? 'emp';
$isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : 1;

header('Content-Type: text/plain; charset=utf-8');

if ($defaultPassword === '') {
    http_response_code(400);
    echo "Missing default_password parameter\n";
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

    $stmt = $conn->query("SELECT id, name FROM counters ORDER BY id ASC");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Counters found: " . count($counters) . "\n";
    foreach ($counters as $c) {
        echo "- counter_id=" . (int)$c['id'] . ", name=" . ($c['name'] ?? '') . "\n";
    }

    if (count($counters) === 0) {
        echo "No counters in DB. Please insert counters first.\n";
        exit;
    }

    $created = 0;
    $skipped = 0;

    foreach ($counters as $counter) {
        $counterId = (int)$counter['id'];
        $username = $usernamePrefix . '_' . $counterId;

        $check = $conn->prepare("SELECT id FROM employees WHERE username = ? LIMIT 1");
        $check->execute([$username]);
        $exists = $check->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $skipped++;
            echo "SKIP: $username already exists\n";
            continue;
        }

        $ins = $conn->prepare("INSERT INTO employees (username, password_hash, counter_id, is_active) VALUES (?, ?, ?, ?)");
        $ins->execute([$username, $passwordHash, $counterId, $isActive]);

        $created++;
        echo "CREATED: $username (counter_id=$counterId)\n";
    }

    echo "Done. created=$created skipped=$skipped\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}

