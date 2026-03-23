document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('dashboard.html')) {
        loadDashboardData();
        loadMyRequests();
        loadNotifications();
        initChart();
    }
});


async function loadDashboardData() {
    const token = localStorage.getItem('session_token');
    
    try {
        const response = await fetch('api/get_dashboard_stats.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalRequests').textContent = data.stats.total || 0;
            document.getElementById('resolvedRequests').textContent = data.stats.resolved || 0;
            document.getElementById('inProgressRequests').textContent = data.stats.in_progress || 0;
            document.getElementById('readArticles').textContent = data.stats.articles_read || 0;
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}


async function loadMyRequests() {
    const token = localStorage.getItem('session_token');
    
    try {
        const response = await fetch('api/get_my_requests.php?limit=5', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const requests = await response.json();
        
        const tbody = document.getElementById('myRecentRequests');
        if (tbody) {
            tbody.innerHTML = '';
            
            if (requests.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>У вас пока нет заявок</p>
                            <a href="requests.html" class="btn btn-sm btn-primary">Создать заявку</a>
                        </td>
                    </tr>
                `;
                return;
            }
            
            requests.forEach(req => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${req.id}</td>
                    <td>${req.title}</td>
                    <td>${getCategoryName(req.category)}</td>
                    <td><span class="badge ${getPriorityClass(req.priority)}">${req.priority}</span></td>
                    <td><span class="badge ${getStatusClass(req.status)}">${req.status}</span></td>
                    <td>${new Date(req.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewRequest(${req.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error loading my requests:', error);
    }
}


async function loadNotifications() {
    const token = localStorage.getItem('session_token');
    
    try {
        const response = await fetch('api/get_notifications.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateNotificationsList(data.notifications);
            document.getElementById('notificationCount').textContent = data.unread_count || 0;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}


function updateNotificationsList(notifications) {
    const list = document.getElementById('notificationsList');
    
    if (!list) return;
    
    if (!notifications || notifications.length === 0) {
        list.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                <p>Нет уведомлений</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.is_read ? '' : 'unread'}" 
             onclick="markNotificationRead(${notif.id})">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <i class="fas ${getNotificationIcon(notif.type)} fa-lg text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${notif.title}</h6>
                    <p class="small text-muted mb-1">${notif.message}</p>
                    <small class="text-muted">
                        ${timeAgo(notif.created_at)}
                    </small>
                </div>
                ${!notif.is_read ? '<span class="badge bg-danger">New</span>' : ''}
            </div>
        </div>
    `).join('');
}


function initChart() {
    const ctx = document.getElementById('requestsChart');
    if (!ctx) return;
    
 
    fetch('api/get_requests_stats.php', {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('session_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Новые заявки',
                    data: data.new_requests || [65, 59, 80, 81, 56, 55, 40],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Решенные заявки',
                    data: data.resolved_requests || [28, 48, 40, 19, 86, 27, 90],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    })
    .catch(error => {
        console.error('Error loading chart data:', error);
    });
}


function getNotificationIcon(type) {
    const icons = {
        'request_created': 'fa-ticket-alt',
        'request_updated': 'fa-edit',
        'request_assigned': 'fa-user-check',
        'comment_added': 'fa-comment',
        'status_changed': 'fa-exchange-alt'
    };
    return icons[type] || 'fa-bell';
}

function timeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        год: 31536000,
        месяц: 2592000,
        неделя: 604800,
        день: 86400,
        час: 3600,
        минута: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return `${interval} ${unit}${interval > 1 ? 'а' : ''} назад`;
        }
    }
    
    return 'только что';
}

function markNotificationRead(id) {
    fetch('api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('session_token')}`
        },
        body: JSON.stringify({ notification_id: id })
    })
    .then(() => {
        loadNotifications();
    });
}

function viewRequest(id) {
    window.location.href = `request-detail.html?id=${id}`;
}