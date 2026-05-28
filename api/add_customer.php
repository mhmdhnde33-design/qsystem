<?php
header('Content-Type: application/json');
include '../config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'الطريقة غير مسموحة']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('تنسيق JSON غير صالح');
    }
    
$name = $data['name'] ?? '';
    $serviceType = $data['service_type'] ?? '';

    if (empty($name) || empty($serviceType)) {
        echo json_encode(['success' => false, 'message' => 'الاسم ونوع الخدمة مطلوبان']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    
    // Generate queue number.
    // IMPORTANT: queue_number is stored as a single-letter prefix + 3 digits (e.g., G001).
    // Using the first character of service_type may break this scheme when service_type values don't match.
    $prefixMap = [
        'public_attorney' => 'A',
        'public_prosecution' => 'P',
        'sharia' => 'S',
        'civil_beginning' => 'C',
        'investigation' => 'I',
        'penal_reconciliation' => 'R'
    ];

    $prefix = $prefixMap[$serviceType] ?? strtoupper(substr($serviceType, 0, 1));

    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)) as last_num 
                           FROM customers WHERE queue_number LIKE ? AND DATE(created_at) = CURDATE()");
    $likePattern = $prefix . '%';
    $stmt->execute([$likePattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = ($result['last_num'] ?? 0) + 1;
    $queueNumber = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    
    // Insert customer
    $stmt = $conn->prepare("INSERT INTO customers (queue_number, name, service_type) VALUES (?, ?, ?)");
    $stmt->execute([$queueNumber, $name, $serviceType]);


    
    // Return created customer id to allow immediate call to a counter
    $customerId = (int)$conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'customer_id' => $customerId,
        'queue_number' => $queueNumber,
        'message' => 'تم إضافة العميل بنجاح'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

