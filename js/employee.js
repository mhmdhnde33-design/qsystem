// Clock
function updateTime() {
    const now = new Date();
    const formattedTime = now.toLocaleTimeString('ar-EG', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const timeEl = document.getElementById('current-time');
    if (timeEl) timeEl.textContent = formattedTime;
}
setInterval(updateTime, 1000);
updateTime();

function showError(message) {
    const el = document.getElementById('errorNotification');
    if (!el) return;
    el.classList.remove('hidden');
    el.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            <span>${message}</span>
            <button onclick="document.getElementById('errorNotification').classList.add('hidden')" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    setTimeout(() => {
        el.classList.add('hidden');
    }, 5000);
}

const serviceTypeLabels = {
    public_attorney: 'ديوان المحامي العام',
    public_prosecution: 'ديوان الكاتب بالعدل',
    sharia: 'ديوان الشريعة',
    civil_beginning: 'ديوان البداية المدنية',
    investigation: 'ديوان التحقيق',
    penal_reconciliation: 'ديوان صلح الجزاء',
    general: 'عام',
    payment: 'دفـع',
    inquiry: 'استعلام',
    technical: 'فني',
    support: 'دعم'
};

function formatServiceLabel(serviceType) {
    return serviceTypeLabels[serviceType] || serviceType || '-';
}

function renderServing(current) {
    const servingQueueNumber = document.getElementById('servingQueueNumber');
    const servingCustomerName = document.getElementById('servingCustomerName');
    const servingServiceLabel = document.getElementById('servingServiceLabel');
    const btnComplete = document.getElementById('btnComplete');
    const btnExit = document.getElementById('btnExit');
    const btnCallFromServing = document.getElementById('btnCallFromServing');

    if (!servingQueueNumber || !servingCustomerName || !servingServiceLabel) return;

    if (!current) {
        servingQueueNumber.textContent = '---';
        servingCustomerName.textContent = '-';
        servingServiceLabel.textContent = '-';
        if (btnComplete) { btnComplete.disabled = true; btnComplete.classList.add('opacity-50'); }
        if (btnExit) { btnExit.disabled = true; btnExit.classList.add('opacity-50'); }
        if (btnCallFromServing) { btnCallFromServing.disabled = true; btnCallFromServing.classList.add('opacity-50'); }
        return;
    }


    servingQueueNumber.textContent = current.queue_number || '---';
    servingCustomerName.textContent = current.name || '-';
    servingServiceLabel.textContent = formatServiceLabel(current.service_type);

    if (btnComplete) {
        btnComplete.disabled = false;
        btnComplete.classList.remove('opacity-50');
        btnComplete.onclick = () => employeeComplete(current.id);
    }

    if (btnExit) {
        btnExit.disabled = false;
        btnExit.classList.remove('opacity-50');
        btnExit.onclick = () => employeeExit(current.id);
    }

    if (btnCallFromServing) {
        // enabled when there is someone waiting in the list currently shown
        const hasWaiting = document.querySelector('#employeeQueueTable button[onclick^="employeeCall("]:not([disabled])');

        btnCallFromServing.disabled = !hasWaiting;
        if (btnCallFromServing.disabled) {
            btnCallFromServing.classList.add('opacity-50');
        } else {
            btnCallFromServing.classList.remove('opacity-50');
            btnCallFromServing.onclick = () => {
                // try to click first enabled waiting button
                const firstWaitingBtn = document.querySelector('#employeeQueueTable button[onclick^="employeeCall("]:not([disabled])');
                if (firstWaitingBtn) firstWaitingBtn.click();
                else {
                    // fallback: if there is any waiting row at all, call its first customer id
                    const firstWaitingRow = document.querySelector('#employeeQueueTable tr');
                    if (firstWaitingRow) {
                        const btn = firstWaitingRow.querySelector('button[onclick^="employeeCall("]:not([disabled])');
                        if (btn) btn.click();
                    }
                }

            };
        }
    }



}


function renderWaiting(waiting) {
    const tbody = document.getElementById('employeeQueueTable') || document.getElementById('employeeQueuetable');

    if (!tbody) return;
    tbody.innerHTML = '';

    if (!waiting || waiting.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">لا يوجد عملاء بانتظار هذا الديوان</td></tr>`;
        return;
    }

    waiting.forEach(c => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        const canStart = c.status === 'waiting';
        tr.innerHTML = `
            <td class="px-4 py-3"><span class="queue-number text-lg">${c.queue_number || '-'}</span></td>
            <td class="px-4 py-3">${c.name || '-'}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">${formatServiceLabel(c.service_type)}</span></td>
            <td class="px-4 py-3">
                <button ${canStart ? '' : 'disabled'} onclick="employeeCall(${c.id})" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition ${canStart ? '' : 'opacity-50'}">
                    <i class="fas fa-bullhorn ml-1"></i>${canStart ? 'استدعاء/بدء' : 'بانتظار الدور'}
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function postJSON(url, bodyObj) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bodyObj)
    }).then(r => r.json());
}

function refreshEmployeeQueue() {
    const counterId = window.__EMPLOYEE_COUNTER_ID__;
    postJSON('api/employee_get_queue.php', { counter_id: counterId })
        .then(res => {
            if (!res.success) {
                showError(res.message || 'حدث خطأ في تحميل قائمة الانتظار');
                return;
            }
            const data = res.data;
            renderServing(data.current);
            renderWaiting(data.waiting);
        })
        .catch(err => {
            console.error(err);
            showError('فشل الاتصال بخادم قائمة الموظف');
        });
}

function employeeCall(customerId) {
    const counterId = window.__EMPLOYEE_COUNTER_ID__;
    if (!confirm('هل تريد بدء خدمة هذا المراجع؟')) return;

    postJSON('api/employee_call_customer.php', { counter_id: counterId, customer_id: customerId })
        .then(res => {
            if (!res.success) {
                showError(res.message || 'تعذر بدء الخدمة');
                return;
            }
            refreshEmployeeQueue();
        })
        .catch(err => {
            console.error(err);
            showError('تعذر بدء الخدمة');
        });
}

function employeeComplete(customerId) {
    if (!confirm('هل أنت متأكد من إنهاء الخدمة؟')) return;

    postJSON('api/complete_customer.php', { customer_id: customerId })
        .then(res => {
            if (!res.success) {
                showError(res.message || 'تعذر إنهاء الخدمة');
                return;
            }
            refreshEmployeeQueue();
        })
        .catch(err => {
            console.error(err);
            showError('تعذر إنهاء الخدمة');
        });
}

function employeeExit(customerId) {
    if (!confirm('هل تريد خروج/إلغاء هذا المراجع من الكونتر؟')) return;

    postJSON('api/exit_customer.php', { customer_id: customerId })
        .then(res => {
            if (!res.success) {
                showError(res.message || 'تعذر تنفيذ الخروج');
                return;
            }
            refreshEmployeeQueue();
        })
        .catch(err => {
            console.error(err);
            showError('تعذر تنفيذ الخروج');
        });
}

// Auto refresh
// Logout button (optional)
function tryBindLogout() {
    const btn = document.getElementById('btnLogout');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        try {
            const res = await fetch('api/employee_logout.php', { method: 'POST' });
            const data = await res.json();
            window.location.href = 'employee_login.php';
        } catch (e) {
            window.location.href = 'employee_login.php';
        }
    });
}
tryBindLogout();

setInterval(refreshEmployeeQueue, 5000);
refreshEmployeeQueue();

