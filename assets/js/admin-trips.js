function viewTrip(tripId) {
    fetch(`manageTrips.php?action=get&id=${tripId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const trip = data.trip;
                const modalBody = document.getElementById('viewModalBody');
                modalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Trip ID</div>
                            <div class="detail-value">${trip.id}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Trip Name</div>
                            <div class="detail-value">${trip.trip_name || trip.name || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Destination</div>
                            <div class="detail-value">${trip.destination}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value">${formatDate(trip.start_date)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">End Date</div>
                            <div class="detail-value">${formatDate(trip.end_date)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Budget Range</div>
                            <div class="detail-value">Rs.${parseInt(trip.budget_min).toLocaleString()} - Rs.${parseInt(trip.budget_max).toLocaleString()}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><span class="status-badge status-${trip.status}">${trip.status}</span></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Host Name</div>
                            <div class="detail-value">${trip.host_name}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Host Email</div>
                            <div class="detail-value">${trip.host_email}</div>
                        </div>
                        <div class="detail-item full-width">
                            <div class="detail-label">Description</div>
                            <div class="detail-value">${trip.description}</div>
                        </div>
                    </div>
                `;
                openModal('viewModal');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error fetching trip details', 'error');
        });
}

function editTrip(tripId) {
    fetch(`manageTrips.php?action=get&id=${tripId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const trip = data.trip;
                document.getElementById('edit_trip_id').value = trip.id;
                document.getElementById('edit_name').value = trip.trip_name || trip.name || '';
                document.getElementById('edit_destination').value = trip.destination;
                document.getElementById('edit_description').value = trip.description;
                document.getElementById('edit_start_date').value = trip.start_date;
                document.getElementById('edit_end_date').value = trip.end_date;
                document.getElementById('edit_budget_min').value = trip.budget_min;
                document.getElementById('edit_budget_max').value = trip.budget_max;
                document.getElementById('edit_status').value = trip.status;
                openModal('editModal');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error fetching trip details', 'error');
        });
}

function deleteTrip(tripId, tripName) {
    if(confirm(`Are you sure you want to delete trip "${tripName}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', tripId);
        formData.append('ajax', '1');

        fetch('manageTrips.php', {
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
            showToast('Error deleting trip', 'error');
        });
    }
}

document.getElementById('editTripForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!validateTripForm()) {
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'update');
    formData.append('ajax', '1');

    fetch('manageTrips.php', {
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
        showToast('Error updating trip', 'error');
    });
});

function validateTripForm() {
    let isValid = true;
    clearErrors();

    const name = document.getElementById('edit_name').value.trim();
    const destination = document.getElementById('edit_destination').value.trim();
    const description = document.getElementById('edit_description').value.trim();
    const startDate = document.getElementById('edit_start_date').value;
    const endDate = document.getElementById('edit_end_date').value;
    const budgetMin = parseInt(document.getElementById('edit_budget_min').value);
    const budgetMax = parseInt(document.getElementById('edit_budget_max').value);

    if(name.length < 3) {
        showError('error_name', 'Trip name must be at least 3 characters');
        isValid = false;
    }

    if(destination.length < 2) {
        showError('error_destination', 'Destination must be at least 2 characters');
        isValid = false;
    }

    if(description.length < 10) {
        showError('error_description', 'Description must be at least 10 characters');
        isValid = false;
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if(end < start) {
        showError('error_end_date', 'End date must be after start date');
        isValid = false;
    }

    if(budgetMin < 0) {
        showError('error_budget_min', 'Budget must be a positive number');
        isValid = false;
    }

    if(budgetMax < budgetMin) {
        showError('error_budget_max', 'Max budget must be greater than min budget');
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
