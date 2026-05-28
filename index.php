<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام خدمة المراجعين في عدلية الرقة</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #27802d 0%, #2c8330 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .queue-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        footer {
            position: relative;
            width: 100%;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white bg-opacity-20 mr-3 overflow-hidden">
<img src="assets/logo.png.webp" alt="شعار النسر الذهبي" class="w-10 h-10 object-cover">
                    </span>
                    <h1 class="text-3xl md:text-4xl font-bold">نظام خدمة المراجعين في عدلية الرقة</h1>
                </div>
                <div class="text-left">
                    <div id="current-time" class="text-xl md:text-2xl font-bold font-mono text-white"></div>
                    <div class="text-sm">مرحباً بالمشرف</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="waiting-count">0</h3>
                        <p class="text-gray-600">في الانتظار</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-user-check text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="serving-count">0</h3>
                        <p class="text-gray-600">قيد الخدمة</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="completed-count">0</h3>
                        <p class="text-gray-600">مكتمل</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="today-count">0</h3>
                        <p class="text-gray-600">إجمالي اليوم</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Customer Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-plus-circle ml-2"></i>تسجيل عميل جديد</h2>
                    
                    <form id="customerForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم العميل</label>
                            <input type="text" id="customerName" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="أدخل اسم العميل">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نوع الخدمة</label>
                            <select id="serviceType" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                
                                <option value="public_attorney">ديوان المحامي العام</option>
                                <option value="public_prosecution">ديوان الكاتب بالعدل</option>

                                <option value="sharia">ديوان الشرعية</option>
                        >
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition duration-300 font-semibold">
                            <i class="fas fa-ticket-alt ml-2"></i>إصدار رقم الدور
                        </button>
                    </form>
                    
                    <!-- Generated Queue Display -->
                    <div id="queueResult" class="mt-6 hidden">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                            <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">تم إصدار رقم الدور</h3>
                            <div class="text-3xl font-bold text-green-600 queue-number mb-2" id="generatedQueue"></div>
                            <p class="text-gray-600">يرجى الانتظار حتى يتم استدعاء دورك</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Management -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-list ml-2"></i>إدارة الدواوين</h2>
                        <div class="flex space-x-2">
                            <button onclick="refreshQueue()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                                <i class="fas fa-sync-alt ml-2"></i>تحديث
                            </button>
                        </div>
                    </div>

                    <!-- Counter Status -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">حالة الدواوين</h3>
                        <div id="countersStatus" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Counters will be loaded here -->
                        </div>
                    </div>

                    <!-- Queue List -->
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">رقم الدور</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">العميل</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الخدمة</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الحالة</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الوقت</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="queueTable" class="divide-y divide-gray-200">
                                <!-- Queue data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-5">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2026 نظام خدمة المراجعين في عدلية الرقة. دائرة المعلوماتية.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>