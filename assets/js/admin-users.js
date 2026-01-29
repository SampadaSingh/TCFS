function viewUser(userId) {
    fetch(`manageUsers.php?action=get&id=${userId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const user = data.user;
                const modalBody = document.getElementById('viewModalBody');
                modalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">User ID</div>
                            <div class="detail-value">${user.id}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value">${user.name}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">${user.email}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Date of Birth</div>
                            <div class="detail-value">${formatDate(user.dob)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Gender</div>
                            <div class="detail-value">${user.gender}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Joined Date</div>
                            <div class="detail-value">${formatDate(user.created_at)}</div>
                        </div>
                        <div class="detail-item full-width">
                            <div class="detail-label">Bio</div>
                            <div class="detail-value">${user.bio || 'No bio available'}</div>
                        </div>
                        <div class="detail-item full-width">
                            <div class="detail-label">Interests</div>
                            <div class="detail-value">${user.interests || 'No interests specified'}</div>
                        </div>
                    </div>
                `;
                openModal('viewModal');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error fetching user details', 'error');
        });
}

function editUser(userId) {
    fetch(`manageUsers.php?action=get&id=${userId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const user = data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_name').value = user.name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_dob').value = user.dob;
                document.getElementById('edit_gender').value = user.gender;
                document.getElementById('edit_bio').value = user.bio || '';
                openModal('editModal');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error fetching user details', 'error');
        });
}

function deleteUser(userId, userName) {
    if(confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', userId);
        formData.append('ajax', '1');

        fetch('manageUsers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error deleting user', 'error');
        });
    }
}

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!validateUserForm()) {
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'update');
    formData.append('ajax', '1');

    fetch('manageUsers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message, 'success');
            closeModal('editModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error updating user', 'error');
    });
});

function validateUserForm() {
    let isValid = true;
    clearErrors();

    const name = document.getElementById('edit_name').value.trim();
    const email = document.getElementById('edit_email').value.trim();
    const dob = document.getElementById('edit_dob').value;

    if(name.length < 2) {
        showError('error_name', 'Name must be at least 2 characters');
        isValid = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(!emailRegex.test(email)) {
        showError('error_email', 'Please enter a valid email address');
        isValid = false;
    }

    const dobDate = new Date(dob);
    const today = new Date();
    const age = today.getFullYear() - dobDate.getFullYear();
    
    if(age < 18) {
        showError('error_dob', 'User must be at least 18 years old');
        isValid = false;
    }

    if(age > 120) {
        showError('error_dob', 'Please enter a valid date of birth');
        isValid = false;
    }

    return isValid;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    clearErrors();
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

window.onclick = function(event) {
    if(event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
