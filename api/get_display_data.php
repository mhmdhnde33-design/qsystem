<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = [];
    
    // Get currently serving customer
    $stmt = $conn->query(" 
        SELECT c.*, cnt.name as counter_name 
        FROM customers c 
        LEFT JOIN counters cnt ON cnt.current_customer_id = c.id 
        WHERE c.status = 'serving' AND DATE(c.created_at) = CURDATE() 
        ORDER BY c.called_at DESC 
        LIMIT 1
    ");

    $data['now_serving'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Next in line: أقدم عميل يمكن إدخاله على أول كونتر متاح يخدم service_type
    $freeCountersStmt = $conn->query(" 
        SELECT id, name, service_types 
        FROM counters 
        WHERE is_online = 1 AND current_customer_id IS NULL 
        ORDER BY id ASC
    ");
    $freeCounters = $freeCountersStmt->fetchAll(PDO::FETCH_ASSOC);

    $nextCustomer = null;
    foreach ($freeCounters as $counter) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (!is_array($serviceTypes) || empty($serviceTypes)) {
            continue;
        }

        $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
        $sql = "
            SELECT *
            FROM customers
            WHERE status = 'waiting'
              AND DATE(created_at) = CURDATE()
              AND service_type IN ($placeholders)
            ORDER BY created_at ASC
            LIMIT 1
        ";

        $stmt2 = $conn->prepare($sql);
        $stmt2->execute($serviceTypes);
        $candidate = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($candidate) {
            $nextCustomer = $candidate;
            break;
        }
    }

    $data['next_in_line'] = $nextCustomer;
    
    // Waiting count (للشاشة ككل)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting' AND DATE(created_at) = CURDATE()");
    $data['waiting_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recently called (last 6 completed or serving)
    $stmt = $conn->query(" 
        SELECT queue_number, called_at 
        FROM customers 
        WHERE (status = 'completed' OR status = 'serving') 
        AND DATE(created_at) = CURDATE() 
        AND called_at IS NOT NULL 
        ORDER BY called_at DESC 
        LIMIT 6
    ");
    $data['recent_called'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get waiting queue for ticker (queue_number + name for UI normalization)
    $stmt = $conn->query(" 
        SELECT queue_number, name 
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE() 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $data['waiting_queue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Get all counters with their current serving number
    $stmt = $conn->query(" 
        SELECT c.id, c.name, c.service_types, c.is_online,
               cust.queue_number AS current_queue_number,
               cust.name AS current_customer_name
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id
        ORDER BY c.id ASC
    ");

    $data['counters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counter information for currently serving
    if ($data['now_serving']) {
        $stmt = $conn->prepare("SELECT name FROM counters WHERE current_customer_id = ?");
        $stmt->execute([$data['now_serving']['id']]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['now_serving']['counter_name'] = $counter ? $counter['name'] : 'كاونتر متاح';
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

