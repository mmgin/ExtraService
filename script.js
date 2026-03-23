
async function loadRecentRequests() {
    try {
        const response = await fetch('api/get_requests.php?limit=5');
        const requests = await response.json();
        
        const tbody = document.getElementById('recentRequests');
        if (tbody) {
            tbody.innerHTML = '';
            requests.forEach(req => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${req.id}</td>
                    <td>${req.title}</td>
                    <td>${getCategoryName(req.category)}</td>
                    <td><span class="badge ${getPriorityClass(req.priority)}">${req.priority}</span></td>
                    <td><span class="badge ${getStatusClass(req.status)}">${req.status}</span></td>
                    <td>${new Date(req.created_at).toLocaleDateString()}</td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}


async function loadAllRequests() {
    try {
        const response = await fetch('api/get_requests.php');
        const requests = await response.json();
        
        const tbody = document.getElementById('requestsTableBody');
        if (tbody) {
            tbody.innerHTML = '';
            requests.forEach(req => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${req.id}</td>
                    <td>${req.title}</td>
                    <td>${getCategoryName(req.category)}</td>
                    <td><span class="badge ${getPriorityClass(req.priority)}">${req.priority}</span></td>
                    <td><span class="badge ${getStatusClass(req.status)}">${req.status}</span></td>
                    <td>${req.created_by || 'Аноним'}</td>
                    <td>${new Date(req.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewRequest(${req.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editRequest(${req.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}

async function createRequest() {
    const form = document.getElementById('createRequestForm');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/add_request.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          
            const modal = bootstrap.Modal.getInstance(document.getElementById('createRequestModal'));
            modal.hide();
            
          
            showAlert('Заявка успешно создана!', 'success');
            
           
            loadAllRequests();
            
        
            form.reset();
        } else {
            showAlert('Ошибка при создании заявки: ' + result.error, 'danger');
        }
    } catch (error) {
        showAlert('Ошибка соединения с сервером', 'danger');
    }
}


function getCategoryName(category) {
    const categories = {
        'support': 'Поддержка руководства',
        'printer': 'Поддержка принтеров',
        'network': 'Сетевая инфраструктура',
        'software': 'Программное обеспечение'
    };
    return categories[category] || category;
}

function getPriorityClass(priority) {
    const classes = {
        'low': 'bg-success',
        'medium': 'bg-primary',
        'high': 'bg-warning',
        'critical': 'bg-danger'
    };
    return classes[priority] || 'bg-secondary';
}

function getStatusClass(status) {
    const classes = {
        'new': 'bg-info',
        'in_progress': 'bg-warning',
        'resolved': 'bg-success',
        'closed': 'bg-secondary'
    };
    return classes[status] || 'bg-secondary';
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}


document.addEventListener('DOMContentLoaded', function() {
   
    if (document.getElementById('recentRequests')) {
        loadRecentRequests();
    }
    if (document.getElementById('requestsTableBody')) {
        loadAllRequests();
    }
    

    const categoryFilter = document.getElementById('categoryFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (categoryFilter && priorityFilter && statusFilter) {
        [categoryFilter, priorityFilter, statusFilter].forEach(filter => {
            filter.addEventListener('change', filterRequests);
        });
    }
    

    const searchInput = document.getElementById('searchArticles');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchArticles, 500));
    }
});

function filterRequests() {
 
    console.log('Filtering requests...');
}

function searchArticles() {
    
    console.log('Searching articles...');
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}