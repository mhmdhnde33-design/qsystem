<?php 
include 'config.php';

// Get display settings
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'خدمة العملاء', 'welcome_message' => 'مرحباً بكم في مركز الخدمة لدينا'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام خدمة المراجعين في عدلية الرقة</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #16581a 0%, #18571b 100%);
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
            padding-bottom: 5rem; /* reserve space for fixed bottom bar */
        }

        /* prevent label overlap in dynamic cards */
        #countersGrid > div { position: relative;
       
     }

        .display-time { color: #a5d6a7; }
        .display-time-large { font-size: 3rem; line-height: 1.1; }
        .display-date-large { font-size: 2rem; line-height: 1.2; }

        .marquee {
            animation: marquee 20s linear infinite;
            white-space: nowrap;
            display: inline-block;
            padding-left: 100%;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        .flip-in { animation: flipIn 0.6s ease-in-out; }
        @keyframes flipIn {
            from { transform: rotateX(90deg) scale(0.8); opacity: 0; }
            to { transform: rotateX(0deg) scale(1); opacity: 1; }
        }

        .pulse-glow { animation: pulseGlow 2s infinite; }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
            50% { box-shadow: 0 0 40px rgba(255, 255, 255, 0.8); }
        }

        .queue-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .ticker-item { animation: tickerScroll 30s linear infinite; }
        @keyframes tickerScroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* kept from previous file (not used right now) */
        .fixed-brand-bar {
            position: static;
            background: rgba(27, 21, 72, 0.95);
            height: 80px;
            box-shadow: 0 -6px 20px rgba(0,0,0,0.25);
        }
        .fixed-brand-bar .bar-content {}
    </style>
</head>
<body class="text-white min-h-screen">
    <!-- Header with Marquee -->
    <div class="bg-black bg-opacity-30 py-3 mb-8">
        <div class="container mx-auto px-4">
            <div class="overflow-hidden">
                <div class="marquee text-xl font-semibold">
                    <i class="fas fa-info-circle ml-3"></i>
                    <?php echo htmlspecialchars($settings['welcome_message'] ?? 'مرحباً بكم في مركز الخدمة لدينا'); ?>
                    • يرجى تجهيز رقم الدور • شكراً لصبركم • نحن بخدمتكم•
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Company Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center gap-4 mb-6">
                <span class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-white bg-opacity-20 overflow-hidden">
                    <img src="assets/logo.png.webp" alt="شعار النسر الذهبي" class="w-28 h-28 object-cover">
                </span>
                <!-- Reduced font size as requested -->
                <h1 class="text-5xl md:text-6xl font-bold" id="companyName">
                    <?php echo htmlspecialchars($settings['company_name'] ?? 'نظام خدمة المراجعين في عدلية الرقة'); ?>
                </h1>
            </div>
            <div class="text-4xl md:text-5xl color:white font-semibold display-time" id="welcomeMessage">
                نظام خدمة المراجعين في عدلية الرقة
            </div>
        </div>

        <!-- Current Counters Section -->
        <div class="bg-white bg-opacity-20 rounded-3xl p-12 mb-12 text-center border-4 border-white border-opacity-30 pulse-glow">
            <h2 class="text-5xl font-bold mb-8 text-yellow-300">حالة الدواوين الآن</h2>
            <div id="countersGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6"></div>
        </div>

        <!-- Status Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <!-- Next in Line -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-8 text-center backdrop-blur-sm">
                <h3 class="text-3xl font-bold mb-6 text-green-300">
                    <i class="fas fa-arrow-right ml-3"></i>التالي في الدور
                </h3>
                <div id="nextInLine" class="text-5xl font-bold queue-number text-green-300 mb-4">---</div>
                <div class="text-xl opacity-90" id="nextCustomerName">في انتظار العميل التالي</div>
            </div>

            <!-- Waiting Count -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-8 text-center backdrop-blur-sm">
                <h3 class="text-3xl font-bold mb-6 text-blue-300">
                    <i class="fas fa-users ml-3"></i>في الانتظار
                </h3>
                <div id="waitingCount" class="text-5xl font-bold text-blue-300 mb-4">0</div>
                <div class="text-xl opacity-90">عملاء في الانتظار</div>
            </div>

            <!-- Average Wait Time -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-8 text-center backdrop-blur-sm">
                <h3 class="text-3xl font-bold mb-6 text-purple-300">
                    <i class="fas fa-clock ml-3"></i>الوقت المقدر
                </h3>
                <div id="averageWait" class="text-5xl font-bold text-purple-300 mb-4">5 دقيقة</div>
                <div class="text-xl opacity-90">الوقت المقدر</div>
            </div>
        </div>

        <!-- Recently Called Section -->
        <div class="bg-opacity-15 rounded-2xl p-8 mb-8 text-center backdrop-blur-sm">
            <h3 class="text-4xl font-bold mb-6 text-orange-300">
                <i class="fas fa-history ml-3"></i>المكالمات الأخيرة
            </h3>
            <div id="recentNumbers" class="flex justify-center space-x-6 flex-wrap gap-4">
                <div class="text-2xl opacity-70">لا توجد مكالمات حديثة</div>
            </div>
        </div>

        <!-- Waiting Queue Ticker -->
        <div class="bg-black bg-opacity-40 rounded-xl p-4 mb-8">
            <div class="flex items-center mb-2">
                <i class="fas fa-list-ol text-2xl mr-3 text-yellow-400"></i>
                <h4 class="text-2xl font-bold text-yellow-400">قائمة الانتظار</h4>
            </div>
            <div class="overflow-hidden">
                <div id="waitingQueueTicker" class="ticker-item text-xl font-semibold">قائمة الانتظار فارغة</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-12 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="display-date-large font-semibold display-time" id="currentDate"></div>
                <div id="currentTime" class="display-time display-time-large font-mono font-bold"></div>
                <div class="text-2xl font-semibold">
                    <i class="fas fa-heart text-red-400 ml-2"></i>
                    شكراً لانتظاركم
                </div>
            </div>
        </div>

        <footer class="bg-gray-800 text-white py-6 mt-5">
            <div class="container mx-auto px-4 text-center">
                <p>&copy; 2026 نظام خدمة المراجعين في عدلية الرقة. دائرة المعلوماتية.</p>
            </div>
        </footer>

        <!-- Audio for notifications -->
        <audio id="notificationSound" preload="auto">
            <source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg">
        </audio>

        <script src="js/display.js"></script>
</body>
</html>

