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
    $customerId = (int)($data['customer_id'] ?? 0);

    if ($counterId <= 0 || $customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'counter_id and customer_id are required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // Load counter + allowed service types
    $stmt = $conn->prepare("SELECT id, is_online, service_types, current_customer_id FROM counters WHERE id = ? LIMIT 1");


    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$counter) {
        throw new Exception('Counter not found');
    }

    $isOnline = ($counter['is_online'] === 1 || $counter['is_online'] === '1' || $counter['is_online'] === true);
    if (!$isOnline) {
        throw new Exception('Counter is offline');
    }

    if (!empty($counter['current_customer_id'])) {
        throw new Exception('Counter is currently busy');
    }

    $serviceTypes = [];
    if (!empty($counter['service_types'])) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (!is_array($serviceTypes)) {
            $serviceTypes = [];
        }
    }

    // Load customer
    $stmt = $conn->prepare("SELECT id, status, service_type FROM customers WHERE id = ? LIMIT 1");

    $stmt->execute([$customerId]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception('Customer not found');
    }

    if ($customer['status'] !== 'waiting') {
        throw new Exception('Customer is not waiting');
    }

    if (!in_array($customer['service_type'], $serviceTypes, true)) {


        throw new Exception('Customer service type is not allowed for this counter');
    }


    // Assign: waiting -> serving, set called_at, and set counter.current_customer_id
    $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW() WHERE id = ?");
    $stmt->execute([$customerId]);

    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $counterId]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Customer started serving']);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

