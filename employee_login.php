<?php
include 'config.php';

$err = '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الموظف</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-6">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-user-tie text-green-700 text-xl"></i>
            </div>
            <div class="mr-3">
                <h1 class="text-xl font-bold text-gray-800">تسجيل دخول الموظف</h1>
                <p class="text-sm text-gray-600">ادخل بيانات الدخول للكونتر</p>
            </div>
        </div>

        <div id="errorBox" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded"></div>

        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">اسم المستخدم</label>
                <input name="username" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="مثال: emp1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">كلمة المرور</label>
                <input name="password" type="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition font-semibold">
                <i class="fas fa-sign-in-alt ml-2"></i>دخول
            </button>
        </form>
    </div>
</div>

<script>
    const form = document.getElementById('loginForm');
    const errorBox = document.getElementById('errorBox');

    function setError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('hidden');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorBox.classList.add('hidden');

        const fd = new FormData(form);
        const username = (fd.get('username') || '').toString();
        const password = (fd.get('password') || '').toString();

        try {
            const res = await fetch('api/employee_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();
            if (!data.success) {
                setError(data.message || 'فشل تسجيل الدخول');
                return;
            }
            window.location.href = 'employee.php';
        } catch (err) {
            setError('خطأ في الاتصال بالخادم');
        }
    });
</script>
</body>
</html>

