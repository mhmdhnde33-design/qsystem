# TODO - Queue Management System (ربط مترابط)

## Step 1
- [x] مراجعة وتعديل `api/call_customer.php` ليصبح منطق الاستدعاء موحداً: يعتمد فقط على `customers.service_type` ويختار كونتر فارغ online مناسب حسب `counters.service_types`.

## Step 2
- [ ] تعديل `api/exit_customer.php` و/أو `api/cancel_customer.php` (إن لزم) ليقوم بتفريغ الكونتر وتشغيل التالي تلقائياً بنفس service_type لضمان عدم وجود فراغ في حالة الديوان.



## Step 3
- [x] تعديل `api/get_display_data.php` ليعرض “التالي” و“قائمة الانتظار” بتسلسل منطقي مرتبط بـ service_type/الديوان (Next يعتمد على أقدم waiting يمكن أن يخدمه كونتر فارغ مناسب).





## Step 4
- [ ] التأكد من أن `employee_call_customer.php` و `complete_customer.php` تتوافق مع نفس منطق service_type + counters.current_customer_id (لن نعدل إلا عند الحاجة).

## Step 5
- [ ] اختبار شامل:
  - إصدار رقم بديوان (service_type) من `index.php`
  - ظهور العميل في انتظار الموظف المناسب
  - عند استدعاء المشرف/الموظف: تحديث display وemployee
  - عند إنهاء/خروج: انتقال العميل التالي بشكل تسلسلي

