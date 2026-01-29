document.getElementById('adminProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!validateProfileForm()) {
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'update_profile');
    formData.append('ajax', '1');

    fetch('adminSettings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error updating profile', 'error');
    });
});

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!validatePasswordForm()) {
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'change_password');
    formData.append('ajax', '1');

    fetch('adminSettings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message, 'success');
            document.getElementById('changePasswordForm').reset();
            clearErrors();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error changing password', 'error');
    });
});

function validateProfileForm() {
    clearErrors();

    const name = document.getElementById('admin_name').value.trim();
    const email = document.getElementById('admin_email').value.trim();

    if(name.length < 2) {
        showError('error_admin_name', 'Name must be at least 2 characters');
        return false;
    }

    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('error_admin_email', 'Please enter a valid email address');
        return false;
    }

    return true;
}

function validatePasswordForm() {
    let isValid = true;
    clearErrors();

    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if(!currentPassword) {
        showError('error_current_password', 'Please enter your current password');
        return false;
    }

    if(newPassword.length < 8) {
        showError('error_new_password', 'Password must be at least 8 characters');
        return false;
    }

    if(newPassword !== confirmPassword) {
        showError('error_confirm_password', 'Passwords do not match');
        return false;
    }

    if(currentPassword === newPassword) {
        showError('error_new_password', 'New password must be different from current password');
        return false;
    }

    return true;
}

function cleanupOldData() {
    if(confirm('This will delete old completed trips and rejected applications from more than 1 year ago. Continue?')) {
        const formData = new FormData();
        formData.append('action', 'cleanup');

        fetch('api/settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error cleaning up data', 'error');
        });
    }
}

function exportData() {
    showToast('Export feature coming soon', 'success');
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    errorElement.textContent = message;
    errorElement.classList.add('show');
}

function clearErrors() {
    const errors = document.querySelectorAll('.error-message');
    errors.forEach(error => {
        error.textContent = '';
        error.classList.remove('show');
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
