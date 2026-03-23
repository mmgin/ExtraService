
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    

    if (window.location.pathname.includes('dashboard.html') || 
        window.location.pathname.includes('profile.html')) {
        loadUserData();
    }
});


async function checkAuth() {
    const token = localStorage.getItem('session_token');
    
    if (!token && isProtectedPage()) {
        window.location.href = 'login.html';
        return;
    }
    
    if (token) {
        try {
            const response = await fetch('api/check_session.php', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (!data.valid) {
                localStorage.removeItem('session_token');
                localStorage.removeItem('user_data');
                if (isProtectedPage()) {
                    window.location.href = 'login.html';
                }
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const email = form.email.value;
    const password = form.password.value;
    const remember = document.getElementById('remember')?.checked || false;
    
    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password, remember })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('session_token', data.token);
            localStorage.setItem('user_data', JSON.stringify(data.user));
            
            showAlert('Успешный вход! Перенаправление...', 'success');
            
            setTimeout(() => {
                window.location.href = data.user.role === 'admin' ? 'adm.php' : 'dashboard.html';
            }, 1500);
        } else {
            showAlert(data.error || 'Ошибка входа', 'danger');
        }
    } catch (error) {
        showAlert('Ошибка соединения с сервером', 'danger');
    }
}


async function handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    

    const password = formData.get('password');
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password !== confirmPassword) {
        showAlert('Пароли не совпадают', 'danger');
        return;
    }
    
    if (password.length < 8) {
        showAlert('Пароль должен содержать минимум 8 символов', 'danger');
        return;
    }
    
    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Регистрация успешна! Перенаправление на вход...', 'success');
            
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            showAlert(data.error || 'Ошибка регистрации', 'danger');
        }
    } catch (error) {
        showAlert('Ошибка соединения с сервером', 'danger');
    }
}

async function loadUserData() {
    const token = localStorage.getItem('session_token');
    
    if (!token) return;
    
    try {
        const response = await fetch('api/get_user.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateUserInterface(data.user);
            localStorage.setItem('user_data', JSON.stringify(data.user));
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}


function updateUserInterface(user) {

    const userNameSpan = document.getElementById('userName');
    if (userNameSpan) {
        userNameSpan.textContent = user.username || user.full_name || 'Пользователь';
    }
    

    const userFullName = document.getElementById('userFullName');
    if (userFullName) {
        userFullName.textContent = user.full_name || user.username;
    }
    

    const profileFullName = document.getElementById('profileFullName');
    if (profileFullName) {
        profileFullName.textContent = user.full_name || user.username;
    }
    
    const profilePosition = document.getElementById('profilePosition');
    if (profilePosition) {
        profilePosition.textContent = user.position || 'Должность не указана';
    }
    
    const profileDepartment = document.getElementById('profileDepartment');
    if (profileDepartment) {
        profileDepartment.textContent = user.department || 'Отдел не указан';
    }
    
    // Заполняем поля профиля
    const profileUsername = document.getElementById('profileUsername');
    if (profileUsername) profileUsername.value = user.username || '';
    
    const profileEmail = document.getElementById('profileEmail');
    if (profileEmail) profileEmail.value = user.email || '';
    
    const profilePhone = document.getElementById('profilePhone');
    if (profilePhone) profilePhone.value = user.phone || '';
    
    const profileCreatedAt = document.getElementById('profileCreatedAt');
    if (profileCreatedAt && user.created_at) {
        profileCreatedAt.value = new Date(user.created_at).toLocaleDateString();
    }
    
    const profileLastLogin = document.getElementById('profileLastLogin');
    if (profileLastLogin && user.last_login) {
        profileLastLogin.value = new Date(user.last_login).toLocaleString();
    }
    
    const profileRole = document.getElementById('profileRole');
    if (profileRole) {
        const roles = {
            'user': 'Пользователь',
            'support': 'Сотрудник поддержки',
            'admin': 'Администратор'
        };
        profileRole.value = roles[user.role] || user.role;
    }
    

    const editFullName = document.getElementById('editFullName');
    if (editFullName) editFullName.value = user.full_name || '';
    
    const editPhone = document.getElementById('editPhone');
    if (editPhone) editPhone.value = user.phone || '';
    
    const editDepartment = document.getElementById('editDepartment');
    if (editDepartment) editDepartment.value = user.department || '';
    
    const editPosition = document.getElementById('editPosition');
    if (editPosition) editPosition.value = user.position || '';
}


async function logout() {
    const token = localStorage.getItem('session_token');
    
    if (token) {
        try {
            await fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
        } catch (error) {
            console.error('Logout error:', error);
        }
    }
    
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    window.location.href = 'login.html';
}


function editProfile() {
    const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}


async function saveProfile() {
    const form = document.getElementById('editProfileForm');
    const formData = new FormData(form);
    const token = localStorage.getItem('session_token');
    
    try {
        const response = await fetch('api/update_profile.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Профиль обновлен', 'success');
            

            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            modal.hide();
            

            loadUserData();
        } else {
            showAlert(data.error || 'Ошибка обновления', 'danger');
        }
    } catch (error) {
        showAlert('Ошибка соединения с сервером', 'danger');
    }
}


function changePassword() {
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

async function updatePassword() {
    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);
    const token = localStorage.getItem('session_token');
    
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        showAlert('Новые пароли не совпадают', 'danger');
        return;
    }
    
    try {
        const response = await fetch('api/change_password.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Пароль успешно изменен', 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
            

            form.reset();
        } else {
            showAlert(data.error || 'Ошибка смены пароля', 'danger');
        }
    } catch (error) {
        showAlert('Ошибка соединения с сервером', 'danger');
    }
}


function isProtectedPage() {
    const protectedPages = ['dashboard.html', 'profile.html', 'adm.php', 'requests.html'];
    const currentPage = window.location.pathname.split('/').pop();
    return protectedPages.includes(currentPage);
}


function socialLogin(provider) {
    window.location.href = `api/social_login.php?provider=${provider}`;
}


document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
});

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('passwordStrength');
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    const colors = ['#dc3545', '#ffc107', '#ffc107', '#17a2b8', '#28a745'];
    const width = (strength / 5) * 100;
    
    strengthBar.style.width = width + '%';
    strengthBar.style.backgroundColor = colors[strength - 1] || '#dc3545';
}