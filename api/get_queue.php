<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get customers
    $stmt = $conn->query("SELECT * FROM customers WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counters
    $stmt = $conn->query("
        SELECT c.*, cust.name as current_customer_name 
        FROM counters c 
        LEFT JOIN customers cust ON c.current_customer_id = cust.id
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'counters' => $counters
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>