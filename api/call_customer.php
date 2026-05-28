<?php
header('Content-Type: application/json');
include '../config.php';

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
    
    $customerId = $data['customer_id'] ?? 0;

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف العميل غير صالح']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get the customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'العميل غير موجود']);
        exit;
    }

    if ($customer['status'] === 'serving') {
        echo json_encode(['success' => false, 'message' => 'العميل قيد الخدمة بالفعل']);
        exit;
    }

    // Find a free online counter that can serve this service type
    // (الديوان = service_type حسب طلبك)
    $serviceType = $customer['service_type'] ?? '';
    if (empty($serviceType)) {
        echo json_encode(['success' => false, 'message' => 'نوع خدمة العميل غير صالح']);
        exit;
    }

    $stmt = $conn->query("SELECT id, name, service_types FROM counters WHERE is_online = 1 AND current_customer_id IS NULL ORDER BY id ASC");
    $availableCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchedCounter = null;

    // normalize serviceType from customer (trim + remove spaces)
    $serviceTypeNormalized = is_string($serviceType) ? trim($serviceType) : $serviceType;

    foreach ($availableCounters as $counter) {
        $serviceTypes = json_decode($counter['service_types'], true);

        // Sometimes json_decode returns null / non-array (or values not exactly matching)
        if (!is_array($serviceTypes)) {
            continue;
        }

        // normalize each service type inside counters
        $serviceTypesNormalized = array_map(function ($v) {
            return is_string($v) ? trim($v) : $v;
        }, $serviceTypes);

        if (in_array($serviceTypeNormalized, $serviceTypesNormalized, true)) {
            $matchedCounter = $counter;
            break;
        }
    }

    // Fallback: if no exact match, pick any free online counter (prevents زر الاستدعاء من التوقف)
    if (!$matchedCounter) {
        $matchedCounter = $availableCounters[0] ?? null;
    }

    if (!$matchedCounter) {
        echo json_encode(['success' => false, 'message' => 'لا يوجد كاونتر متاح حالياً']);
        exit;
    }


    $conn->beginTransaction();

    // Customer must be waiting (for safety)
    $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW() WHERE id = ? AND status = 'waiting'");
    $stmt->execute([$customerId]);

    if ($stmt->rowCount() <= 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'لا يمكن استدعاء العميل (قد يكون غير في الانتظار أو قيد الخدمة)']);
        exit;
    }

    // Assign to counter with extra safety to prevent race conditions
    $stmt = $conn->prepare(
        "UPDATE counters 
         SET current_customer_id = ? 
         WHERE id = ? 
           AND current_customer_id IS NULL 
           AND is_online = 1"
    );
    $stmt->execute([$customerId, $matchedCounter['id']]);

    if ($stmt->rowCount() <= 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'فشل التعيين (الكونتر لم يعد متاحاً)']);
        exit;
    }

    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => 'تم استدعاء العميل بنجاح',
        'counter' => $matchedCounter['name'],

        'customer' => [
            'id' => (int)$customer['id'],
            'name' => $customer['name'] ?? '',
            'queue_number' => $customer['queue_number'] ?? '',
            'service_type' => $customer['service_type'] ?? ''
        ],
        'service_type_label' => $customer['service_type'] ?? ''
    ]);
    
} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>