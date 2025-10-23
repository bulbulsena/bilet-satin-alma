// JavaScript functions for Bilet Platformu

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Seat selection functionality
function selectSeat(seatElement, seatNumber) {
    if (seatElement.classList.contains('occupied') || seatElement.classList.contains('disabled')) {
        return;
    }
    
    // Toggle selection
    if (seatElement.classList.contains('selected')) {
        seatElement.classList.remove('selected');
        removeSeatFromSelection(seatNumber);
    } else {
        seatElement.classList.add('selected');
        addSeatToSelection(seatNumber);
    }
    
    updatePriceDisplay();
}

function addSeatToSelection(seatNumber) {
    let selectedSeats = getSelectedSeats();
    if (!selectedSeats.includes(seatNumber)) {
        selectedSeats.push(seatNumber);
        localStorage.setItem('selectedSeats', JSON.stringify(selectedSeats));
    }
}

function removeSeatFromSelection(seatNumber) {
    let selectedSeats = getSelectedSeats();
    let index = selectedSeats.indexOf(seatNumber);
    if (index > -1) {
        selectedSeats.splice(index, 1);
        localStorage.setItem('selectedSeats', JSON.stringify(selectedSeats));
    }
}

function getSelectedSeats() {
    let selectedSeats = localStorage.getItem('selectedSeats');
    return selectedSeats ? JSON.parse(selectedSeats) : [];
}

function clearSeatSelection() {
    localStorage.removeItem('selectedSeats');
    document.querySelectorAll('.seat.selected').forEach(seat => {
        seat.classList.remove('selected');
    });
}

// Price calculation
function updatePriceDisplay() {
    const basePrice = parseFloat(document.getElementById('basePrice')?.value || 0);
    const selectedSeats = getSelectedSeats();
    const seatCount = selectedSeats.length;
    const subtotal = basePrice * seatCount;
    
    // Apply coupon discount if any
    const couponCode = document.getElementById('couponCode')?.value;
    let discountAmount = 0;
    let finalPrice = subtotal;
    
    if (couponCode && window.couponData) {
        if (window.couponData.discount_type === 'percentage') {
            discountAmount = subtotal * (window.couponData.discount_value / 100);
        } else {
            discountAmount = window.couponData.discount_value;
        }
        finalPrice = Math.max(0, subtotal - discountAmount);
    }
    
    // Update display
    document.getElementById('seatCount').textContent = seatCount;
    document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' ₺';
    
    if (discountAmount > 0) {
        document.getElementById('discountAmount').textContent = '-' + discountAmount.toFixed(2) + ' ₺';
        document.getElementById('discountRow').style.display = 'table-row';
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    document.getElementById('finalPrice').textContent = finalPrice.toFixed(2) + ' ₺';
}

// Coupon validation
function validateCoupon() {
    const couponCode = document.getElementById('couponCode').value;
    if (!couponCode) {
        hideCouponMessage();
        updatePriceDisplay();
        return;
    }
    
    fetch('ajax/validate_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'coupon_code=' + encodeURIComponent(couponCode)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.couponData = data.coupon;
            showCouponMessage('Kupon başarıyla uygulandı!', 'success');
            updatePriceDisplay();
        } else {
            window.couponData = null;
            showCouponMessage(data.message, 'error');
            updatePriceDisplay();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCouponMessage('Kupon doğrulama sırasında hata oluştu', 'error');
    });
}

function showCouponMessage(message, type) {
    const messageDiv = document.getElementById('couponMessage');
    messageDiv.textContent = message;
    messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
    messageDiv.style.display = 'block';
}

function hideCouponMessage() {
    document.getElementById('couponMessage').style.display = 'none';
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Loading state management
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('loading', 'show');
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('loading', 'show');
    }
}

// Date and time utilities
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    return timeString.substring(0, 5);
}

function formatDateTime(dateString, timeString) {
    return formatDate(dateString) + ' ' + formatTime(timeString);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Search form enhancement
function enhanceSearchForm() {
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const departureCity = document.getElementById('departure_city').value;
            const arrivalCity = document.getElementById('arrival_city').value;
            const departureDate = document.getElementById('departure_date').value;
            
            if (!departureCity || !arrivalCity || !departureDate) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurun.');
                return false;
            }
            
            if (departureCity === arrivalCity) {
                e.preventDefault();
                alert('Kalkış ve varış şehirleri aynı olamaz.');
                return false;
            }
            
            const selectedDate = new Date(departureDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                e.preventDefault();
                alert('Geçmiş tarihli seferler için arama yapamazsınız.');
                return false;
            }
        });
    }
}

// Purchase ticket functionality
function proceedToPayment() {
    const selectedSeats = getSelectedSeats();
    const tripId = document.getElementById('tripId')?.value;
    const couponCode = document.getElementById('couponCode')?.value;
    
    if (selectedSeats.length === 0) {
        alert('Lütfen en az bir koltuk seçin.');
        return;
    }
    
    if (!tripId) {
        alert('Sefer bilgisi bulunamadı.');
        return;
    }
    
    if (!confirm('Bilet satın almak istediğinizden emin misiniz?')) {
        return;
    }
    
    showLoading('paymentBtn');
    
    const formData = new FormData();
    formData.append('trip_id', tripId);
    formData.append('selected_seats', JSON.stringify(selectedSeats));
    formData.append('coupon_code', couponCode);
    
    fetch('ajax/purchase_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('paymentBtn');
        
        if (data.success) {
            alert('Bilet satın alma başarılı! Biletleriniz "Biletlerim" sayfasında görüntülenebilir.');
            window.location.href = 'my_tickets.php';
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading('paymentBtn');
        console.error('Error:', error);
        alert('Bilet satın alma sırasında hata oluştu.');
    });
}

// Update payment button state
function updatePaymentButton() {
    const selectedSeats = getSelectedSeats();
    const paymentBtn = document.getElementById('paymentBtn');
    
    if (selectedSeats.length > 0) {
        paymentBtn.disabled = false;
        paymentBtn.textContent = `Ödeme Yap (${selectedSeats.length} koltuk)`;
    } else {
        paymentBtn.disabled = true;
        paymentBtn.innerHTML = '<i class="fas fa-credit-card"></i> Ödeme Yap';
    }
}

// Override updatePriceDisplay to also update payment button
const originalUpdatePriceDisplay = updatePriceDisplay;
updatePriceDisplay = function() {
    originalUpdatePriceDisplay();
    updatePaymentButton();
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    enhanceSearchForm();
    
    // Initialize seat selection if on booking page
    if (document.querySelector('.seat-grid')) {
        updatePriceDisplay();
        
        // Add trip ID to page for JavaScript
        const tripIdElement = document.createElement('input');
        tripIdElement.type = 'hidden';
        tripIdElement.id = 'tripId';
        tripIdElement.value = window.location.search.match(/id=(\d+)/)?.[1] || '';
        document.body.appendChild(tripIdElement);
    }
});
