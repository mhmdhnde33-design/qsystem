<?php
include 'config.php';

$counterId = isset($_SESSION['counter_id']) ? (int)$_SESSION['counter_id'] : 0;
if ($counterId <= 0) {
    // Redirect to login page if not authenticated
    header('Location: employee_login.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// We don’t validate session here (as requested). We only render UI.
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>واجهة الموظف - Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       
       
       header { background: #16aa6d; }

       /* الحالة الحالية فقط (الشريط العلوي داخل الكارت) */
#servingBox { background: #eaf7ea !important; }
       #servingBox * { color: inherit; }

       .current-status-box { border: 1px solid #1c8152 !important; }


            .queue-number { font-family: 'Courier New', monospace; font-weight: bold; }



        
        .bg-gray-800 {
            position: relative;
            width: 100%;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-gray-50">

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">لوحة موظف الخدمة</h1>
            <p class="text-sm text-gray-600">الكونتر: <span class="font-semibold"><?php echo h($counterId); ?></span></p>
        </div>
        <div class="text-left">
            <div id="current-time" class="text-xl font-bold font-mono text-gray-800"></div>
            <div class="text-sm text-gray-500">جرّب: Refresh تلقائي</div>
        </div>
    </div>
</header>

<main class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Current serving -->
        <section class="lg:col-span-1 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-user-check ml-2"></i>الحالة الحالية</h2>

            <div id="servingBox" class="border rounded-lg p-4 bg-white current-status-box" style="background:#ffffff !important;">

                <div class="text-sm text-green-800">لا يوجد عميل قيد الخدمة</div>

                <div class="mt-2 text-3xl font-bold text-green-700 queue-number" id="servingQueueNumber">---</div>
                <div class="text-gray-700 mt-1 font-semibold" id="servingCustomerName">-</div>
                <div class="text-gray-600 mt-2 text-sm" id="servingServiceLabel">-</div>

                <div class="mt-4">
                    <button disabled id="btnComplete" class="w-full bg-blue-500 text-white py-2 rounded-lg opacity-50">
                        <i class="fas fa-check ml-2"></i>إنهاء الخدمة
                    </button>
                </div>

                <div class="mt-2">
                    <button disabled id="btnExit" class="w-full bg-gray-700 text-white py-2 rounded-lg opacity-50">
                        <i class="fas fa-sign-out-alt ml-2"></i>خروج/إلغاء
                    </button>
                </div>

                <!-- New: quick call button (from serving box) -->
                <div class="mt-4">
                    <button disabled id="btnCallFromServing" class="w-full bg-green-600 text-white py-2 rounded-lg opacity-50">
                        <i class="fas fa-bullhorn ml-2"></i>استدعاء/بدء الدور التالي
                    </button>
                </div>

            </div>
        </section>

        <!-- Waiting queue for counter -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-list ml-2"></i>قائمة انتظار الديوان/النوع الخاص بك</h2>
                <button onclick="refreshEmployeeQueue()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-sync-alt ml-2"></i>تحديث
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">رقم الدور</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الاسم</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الديوان</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">إجراء</th>
                        </tr>
                    </thead>
                    <tbody id="employeeQueueTable" class="divide-y divide-gray-200">
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">جارٍ التحميل...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <div id="errorNotification" class="hidden fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50"></div>
    <footer class="bg-gray-800 text-white py-6 mt-5">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2026 نظام خدمة المراجعين في عدلية الرقة.  دائرة المعلوماتية.</p>
        </div>
    </footer>
</main>

<script>
window.__EMPLOYEE_COUNTER_ID__ = <?php echo (int)$counterId; ?>;
// username is optional (display only)
window.__EMPLOYEE_USERNAME__ = <?php echo isset($_SESSION['employee_username']) ? json_encode((string)$_SESSION['employee_username']) : '""'; ?>;
</script>
<script src="js/employee.js"></script>
</body>
</html>

