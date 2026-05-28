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

    $counterId = (int)($data['counter_id'] ?? 0);
    if ($counterId <= 0) {
        echo json_encode(['success' => false, 'message' => 'counter_id is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Load counter + its service types
    $stmt = $conn->prepare("SELECT id, name, service_types, current_customer_id FROM counters WHERE id = ? LIMIT 1");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$counter) {
        echo json_encode(['success' => false, 'message' => 'Counter not found']);
        exit;
    }

    $serviceTypes = [];
    if (!empty($counter['service_types'])) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (!is_array($serviceTypes)) {
            $serviceTypes = [];
        }
    }

    // Current serving customer for this counter (if any)
    $currentCustomer = null;
    if (!empty($counter['current_customer_id'])) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$counter['current_customer_id']]);
        $currentCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Waiting queue for this counter's service types
    if (empty($serviceTypes)) {
        $waiting = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
        $sql = "
            SELECT *
            FROM customers
            WHERE status = 'waiting'
              AND service_type IN ($placeholders)
              AND DATE(created_at) = CURDATE()
            ORDER BY created_at ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($serviceTypes);
        $waiting = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'counter' => [
                'id' => (int)$counter['id'],
                'name' => $counter['name'] ?? '',
                'service_types' => $serviceTypes,
            ],
            'current' => $currentCustomer,
            'waiting' => $waiting
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

