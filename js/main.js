// Update the current clock display
function updateTime() {
    const now = new Date();
    const formattedTime = now.toLocaleTimeString('ar-EG', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const timeEl = document.getElementById('current-time');
    if (timeEl) {
        timeEl.textContent = formattedTime;
    }
}

setInterval(updateTime, 1000);
updateTime();

function initCustomerForm() {
    const form = document.getElementById('customerForm');
    const queueResult = document.getElementById('queueResult');
    const generatedQueue = document.getElementById('generatedQueue');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const customerName = document.getElementById('customerName').value.trim();
        const serviceType = document.getElementById('serviceType').value;

        if (!customerName) {
            showError('يرجى إدخال اسم العميل');
            return;
        }

        fetch('api/add_customer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: customerName,
                service_type: serviceType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                form.reset();
                if (queueResult) {
                    queueResult.classList.remove('hidden');
                }
                if (generatedQueue) {
                    generatedQueue.textContent = data.queue_number || '--';
                }

                try {
                    const serviceTypeLabels = {
                        public_attorney: 'ديوان المحامي العام',
                        public_prosecution: 'ديوان الكاتب بالعدل',
                        sharia: 'ديوان الشرعية',
                        general: 'عام',
                        payment: 'دفع',
                        inquiry: 'استعلام',
                        technical: 'فني',
                        support: 'دعم'
                    };

                    const deptLabel = serviceTypeLabels[serviceType] || serviceType || 'الديوان';
                    const queueNumber = data.queue_number || '---';
                    const text = `أهلًا وسهلًا بك في القصر العدلي بالرقة نحن بخدمتكم. اسم المراجع: ${customerName}. رقم الدور: ${queueNumber}. الديوان: ${deptLabel}`;

                    // نطق صوتي: رقم الدور + اسم الديوان فقط
                    try {
                        const deptNameForSpeech = String(deptLabel || '').trim();
                        const speechText = `رقم الدور ${queueNumber}. الديوان ${deptNameForSpeech}`;
                        // playArabicWelcome(speechText);
                    } catch (e) {}

                    // طباعة الكرت بعد إصدار رقم الدور
                    printCard({
                        customerName: customerName,
                        serviceLabel: deptLabel,
                        queueNumber: queueNumber
                    });
                    printWelcome(text);

                } catch (e) {}

                refreshQueue();
                refreshStats();

                return;
            } else {
                showError(data.message || 'فشل في إضافة العميل');
            }
        })
        .catch(error => {
            console.error('Error adding customer:', error);
            showError('فشل في إضافة العميل');
        });
    });
}

initCustomerForm();

// Initial load counts for dropdown
refreshServiceTypeCounts();

function refreshQueue() {
    fetch('api/get_queue.php')
        .then(response => response.json())
        .then(data => {
            const payload = data.data || data;
            if (data.success || payload.customers || payload.counters) {
                updateQueueTable(payload.customers || []);
                updateCounters(payload.counters || []);
            } else {
                showError(data.message || 'فشل في تحميل قائمة الانتظار');
            }
        })
        .catch(error => {
            console.error('Error loading queue:', error);
            showError('فشل في تحميل قائمة الانتظار');
        });
}

function updateQueueTable(customers) {
    const table = document.getElementById('queueTable');
    if (!table) {
        return;
    }

    const serviceTypeLabels = {
        public_attorney: 'ديوان المحامي العام',
        public_prosecution: 'ديوان الكاتب بالعدل',
        sharia: 'ديوان الشرعية',
        general: 'عام',
        payment: 'دفع',
        inquiry: 'استعلام',
        technical: 'فني',
        support: 'دعم'
    };

    table.innerHTML = '';

    customers.forEach(customer => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';

        let statusClass = '';
        let statusText = '';
        switch (customer.status) {
            case 'waiting':
                statusClass = 'bg-yellow-100 text-yellow-800';
                statusText = 'في الانتظار';
                break;
            case 'serving':
                statusClass = 'bg-blue-100 text-blue-800';
                statusText = 'قيد الخدمة';
                break;
            case 'completed':
                statusClass = 'bg-green-100 text-green-800';
                statusText = 'مكتمل';
                break;
            case 'cancelled':
                statusClass = 'bg-red-100 text-red-800';
                statusText = 'خارج الديوان';
                break;
            default:
                statusClass = 'bg-gray-100 text-gray-800';
                statusText = customer.status || 'غير معروف';
        }

        const createdTime = customer.created_at ? new Date(customer.created_at).toLocaleTimeString('ar-EG', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }) : '-';

        row.innerHTML = `
            <td class="px-4 py-3">
                <span class="queue-number text-lg font-bold">${customer.queue_number || '-'}</span>
            </td>
            <td class="px-4 py-3">${customer.name || '-'}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    ${serviceTypeLabels[customer.service_type] || customer.service_type || 'عام'}
                </span>
            </td>
            <td class="px-4 py-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">${createdTime}</td>
            <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                    <!-- زر الاستدعاء تم إلغاؤه من الواجهة الرئيسية، والاستدعاء يتم فقط من واجهة الموظف -->
                    ${customer.status === 'serving' ? `
                        <button onclick="completeCustomer(${customer.id})" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-check ml-1"></i>إنهاء
                        </button>
                    ` : ''}

                    ${customer.status !== 'completed' && customer.status !== 'cancelled' && customer.status !== 'serving' ? `
                        <button onclick="cancelCustomer(${customer.id})" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition duration-200">
                            <i class="fas fa-times ml-1"></i>إلغاء
                        </button>
                    ` : ''}

                    ${customer.status === 'waiting' ? `
                        <button onclick="callCustomer(${customer.id})" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition duration-200">
                            <i class="fas fa-bullhorn ml-1"></i>استدعاء
                        </button>
                    ` : ''}

                    ${customer.status === 'waiting' || customer.status === 'serving' ? `
                        <button onclick="exitCustomer(${customer.id})" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition duration-200">
                            <i class="fas fa-sign-out-alt ml-1"></i>إخراج من الديوان
                        </button>
                    ` : ''}


                </div>
            </td>
        `;

        table.appendChild(row);
    });
}

function updateCounters(counters) {
    const container = document.getElementById('countersStatus');
    if (!container) {
        return;
    }

    if (!counters || counters.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500">لا توجد دواوين مُعدة</div>';
        return;
    }

    const departmentNameMapping = {
        1: 'ديوان المحامي العام',
        2: 'ديوان الكاتب بالعدل',
        3: 'ديوان الشرعية',
       
    };

    const serviceTypeLabels = {
        public_attorney: 'ديوان المحامي العام',
        public_prosecution: 'ديوان الكاتب بالعدل',
        sharia: 'ديوان الشرعية',
        general: 'عام',
        payment: 'دفع',
        inquiry: 'استعلام',
        technical: 'فني',
        support: 'دعم'
    };

    container.innerHTML = '';

    counters.forEach(counter => {
        const isOnline = counter.is_online === true || counter.is_online === 1 || counter.is_online === '1';
        const counterDiv = document.createElement('div');
        counterDiv.className = `border rounded-lg p-4 ${isOnline ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'}`;

        const displayName = counter.display_name || departmentNameMapping[counter.id] || counter.name || 'ديوان غير معروف';

        let serviceTypes = [];
        if (Array.isArray(counter.service_types)) {
            serviceTypes = counter.service_types;
        } else if (typeof counter.service_types === 'string' && counter.service_types.trim()) {
            try {
                serviceTypes = JSON.parse(counter.service_types);
            } catch (error) {
                serviceTypes = [counter.service_types];
            }
        }

        const serviceNames = serviceTypes.map(type => serviceTypeLabels[type] || type).join(', ') || 'عام';
        const isBusy = !!counter.current_customer_name;
        const statusLabel = !isOnline ? 'غير متصل' : (isBusy ? 'مشغول' : 'متاح');
        const statusClass = !isOnline ? 'bg-yellow-200 text-yellow-800' : (isBusy ? 'bg-red-200 text-red-800' : 'bg-green-200 text-green-800');

        counterDiv.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <h4 class="font-semibold">${displayName}</h4>
                <span class="px-2 py-1 rounded text-xs ${statusClass}">${statusLabel}</span>
            </div>
            <div class="text-sm text-gray-600 mb-2">الخدمات: ${serviceNames}</div>
            <div class="text-sm">${counter.current_customer_name ? `
                قيد الخدمة: <span class="font-bold">${counter.current_customer_name}</span>
                <div class="mt-3">
                    <button onclick="exitCustomer(${counter.current_customer_id})" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition duration-200">
                        <i class="fas fa-sign-out-alt ml-1"></i>إخراج من الديوان
                    </button>
                </div>
            ` : 'متاح'}</div>
        `;

        container.appendChild(counterDiv);
    });

}

// Customer actions
// تم إلغاء زر الاستدعاء من الواجهة الرئيسية، لذا تم تعطيل استدعاء المشرف هنا.
function callCustomer(customerId) {
    fetch('api/call_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshQueue();
            refreshStats();

            try {
                const c = data.customer || {};
                const queueNumber = c.queue_number ? String(c.queue_number) : (c.queue_number === '' ? '' : (c.queue_number || ''));
                const serviceType = c.service_type || data.service_type_label || '';
                const serviceTypeLabels = {
                    public_attorney: 'ديوان المحامي العام',
                    public_prosecution: 'ديوان الكاتب بالعدل',
                    sharia: 'ديوان الشرعية',
                    general: 'عام',
                    payment: 'دفع',
                    inquiry: 'استعلام',
                    technical: 'فني',
                    support: 'دعم'
                };

                const deptLabel = serviceTypeLabels[serviceType] || serviceType || 'الديوان';
                const name = c.name || '';
                const text = `أهلًا وسهلًا بك في القصر العدلي بالرقة نحن بخدمتكم. ` +
                    `اسم المراجع: ${name || '---'}. ` +
                    `رقم الدور: ${queueNumber || '---'}. ` +
                    `الديوان: ${deptLabel}`;

                playArabicWelcome(text);
                printWelcome(text);
            } catch (e) {}
        } else {

            showError(data.message || 'فشل في استدعاء العميل');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('فشل في استدعاء العميل');
    });
}

function playArabicWelcome(text) {
    // توليد صوت عربي عبر المتصفح (SpeechSynthesis)
    try {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(String(text));
        utterance.lang = 'ar-SA';

        // محاولة اختيار صوت (امرأة/بنت) إن وجد ضمن أصوات المتصفح
        try {
            const voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
            const femaleVoice = (voices || []).find(v => {
                const name = (v.name || '').toString();
                return /female|woman|girl|أنث|بنت/i.test(name);
            });
            if (femaleVoice) utterance.voice = femaleVoice;
        } catch (e) {}

        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        utterance.volume = 1;
        window.speechSynthesis.speak(utterance);
    } catch (e) {}
}

function printWelcome(text) {
    // عرض رسالة الترحيب داخل الصفحة
    try {
        let el = document.getElementById('welcomeOverlay');
        if (!el) {
            el = document.createElement('div');
            el.id = 'welcomeOverlay';
            el.className = 'fixed inset-x-0 top-4 z-50 flex justify-center pointer-events-none';
            document.body.appendChild(el);
        }

        el.innerHTML = `
            <div class="pointer-events-auto bg-green-700 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3">
                <i class="fas fa-bell"></i>
                <div class="text-right">
                    <div class="font-bold text-lg">ترحيب</div>
                    <div class="text-sm opacity-95">${text}</div>
                </div>
            </div>
        `;

        el.style.display = 'flex';
        clearTimeout(window.__welcomeHideTimer);
        window.__welcomeHideTimer = setTimeout(() => {
            if (el) el.style.display = 'none';
        }, 6500);
    } catch (e) {}
}

function printCard({ customerName, serviceLabel, queueNumber }) {
    try {
        const ticketHtml = `
            <html dir="rtl" lang="ar">
            <head>
                <meta charset="UTF-8" />
                <title>طباعة تذكرة</title>
                <style>
                    @media print { 
                        @page { margin: 0; }
                        /* hint for some thermal/receipt drivers */
                        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    }
                    body {
                        font-family: "Arial", "Tahoma", sans-serif;
                        margin: 0;
                        padding: 0;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        direction: rtl;
                        background: #fff;
                    }
                    .wrap { width: 80mm; padding: 6mm 4mm; }
                    .title { text-align: center; font-weight: 900; font-size: 13px; margin-bottom: 4px; line-height: 1.2; padding: 0 2mm; white-space: normal; }
                    .big { text-align: center; font-weight: 900; font-size: 34px; letter-spacing: 2px; margin: 2px 0; }
                    .nameLine { font-weight: 900; font-size: 24px; margin: 4px 0 0; text-align: center; }
                    .deptLine { font-weight: 900; font-size: 22px; margin: 4px 0 0; text-align: center; }
                    .footer { text-align: center; font-weight: 900; font-size: 12px; margin-top: 10px; }
                    .hr { height: 1px; background: #000; margin: 8px 0; }
                    .cut-here { page-break-after: always; }
                    /* remove side shifts during printing */
                    .wrap { padding-left: 0; padding-right: 0; }

                </style>
            </head>
            <body>
                    <div class="wrap" style="position: relative; right: -5ch;">
                    <div class="title">نظام خدمة العملاء بالقصر العدلي</div>
                    <div class="deptLine" style="font-size:16px; margin-top:2px;">في الرقة</div>

                    <div class="big">${String(queueNumber || '').trim()}</div>
                    <div class="hr"></div>

                    <div class="nameLine">${String(customerName || '-').trim()}</div>

                    <div class="deptLine">${String(serviceLabel || '-').trim()}</div>

                    <div style="display:flex; gap:4px; justify-content:center; align-items:center; margin-top:6px;">
                        <div id="printTime" style="font-size:12px; font-weight:900; text-align:center;"></div>
                        <div id="printDate" style="font-size:12px; font-weight:900; text-align:center;"></div>
                    </div>

                    <div class="footer" style="margin-top:6px; font-size:14px;">شكراً لانتظاركم نحن بخدمتكم</div>

                    <script>
                        (function(){
                            try{
                                const now = new Date();
                                const time = now.toLocaleTimeString('ar-EG',{hour12:false,hour:'2-digit',minute:'2-digit',second:'2-digit'});
                                const date = now.toLocaleDateString('ar-EG',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
                                const day = now.toLocaleDateString('ar-EG',{weekday:'long'});
                                document.getElementById('printTime').textContent = time;
                                document.getElementById('printDate').textContent = date;
                                document.getElementById('printDay').textContent = day;
                            }catch(e){}
                        })();
                    <\/script>

                    <div class="cut-here"></div>
                    <div class="cut-here" style="page-break-after: always;"></div>

                </div>
                <script>
                    window.onload = () => {
                        try {
                            setTimeout(() => {
                                try {
                                    window.focus();
                                    window.print();
                                } catch(e){}
                            }, 150);
                        } catch(e){}
                    };
                <\/script>
            </body>
            </html>
        `;

        const w = window.open('', '_blank', 'width=420,height=700');
        if (!w) {
            console.warn('Pop-up blocked: لا يمكن فتح نافذة الطباعة');
            return;
        }
        w.document.open();
        w.document.write(ticketHtml);
        w.document.close();
    } catch (e) {
        console.error('printCard error:', e);
    }
}

// Customer actions
function completeCustomer(customerId) {
    fetch('api/complete_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshQueue();
            refreshStats();
        } else {
            showError(data.message || 'فشل في إنهاء العميل');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('فشل في إنهاء العميل');
    });
}

function exitCustomer(customerId) {
    if (confirm('هل أنت متأكد أنك تريد إخراج هذا العميل من الديوان؟')) {
        fetch('api/exit_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: customerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshQueue();
                refreshStats();
            } else {
                showError(data.message || 'فشل في إخراج العميل');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('فشل في إخراج العميل');
        });
    }
}

function cancelCustomer(customerId) {
    if (confirm('هل أنت متأكد أنك تريد إلغاء هذا العميل؟')) {
        fetch('api/cancel_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: customerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshQueue();
                refreshStats();
            } else {
                showError(data.message || 'فشل في إلغاء العميل');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('فشل في إلغاء العميل');
        });
    }
}

// Update service type counts (dropdown counters)
function updateServiceTypeDropdownCounts(counts) {
    const select = document.getElementById('serviceType');
    if (!select) return;

    // Store base label once (without count) for stable updates
    Array.from(select.options).forEach(opt => {
        if (!opt.dataset.baseLabel) {
            opt.dataset.baseLabel = opt.textContent.replace(/\s*\(\s*\d+\s*\)\s*$/, '');
        }
    });

    Array.from(select.options).forEach(opt => {
        const key = opt.value;
        if (!key || key === 'all') return;

        const base = opt.dataset.baseLabel || opt.textContent;
        const c = (counts && typeof counts[key] !== 'undefined') ? counts[key] : 0;
        opt.textContent = `${base} (${c})`;
    });
}

function refreshServiceTypeCounts() {
    fetch('api/get_service_type_counts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateServiceTypeDropdownCounts(data.data || {});
            } else {
                console.warn('Failed to load service type counts:', data.message);
            }
        })
        .catch(err => {
            console.warn('Failed to load service type counts:', err);
        });
}

// Refresh stats
function refreshStats() {
    fetch('api/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('waiting-count').textContent = stats.waiting;
                document.getElementById('serving-count').textContent = stats.serving;
                document.getElementById('completed-count').textContent = stats.completed;
                document.getElementById('today-count').textContent = stats.today_total;
            } else {
                showError(data.message || 'فشل في تحميل الإحصائيات');
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            showError('فشل في تحميل الإحصائيات');
        });
}

// Show error message
function showError(message) {
    // Create or show error notification
    let errorDiv = document.getElementById('errorNotification');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'errorNotification';
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        document.body.appendChild(errorDiv);
    }

    errorDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentElement) {
            errorDiv.remove();
        }
    }, 5000);
}

// Auto-refresh every 10 seconds
setInterval(() => {
    refreshQueue();
    refreshServiceTypeCounts();
    refreshStats();
}, 10000);

// Initial load
refreshQueue();
refreshServiceTypeCounts();
refreshStats();

