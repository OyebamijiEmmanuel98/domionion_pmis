/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * MAIN JAVASCRIPT FILE
 * =====================================================
 * 
 * This file contains common JavaScript functionality for the PMIS.
 * 
 * @author Final Year Project
 * @version 1.0
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize sidebar toggle
    initSidebarToggle();
    
    // Initialize confirmation dialogs
    initConfirmations();
    
    // Initialize form validations
    initFormValidations();
    
    // Initialize auto-hide alerts
    initAutoHideAlerts();
    
    // Initialize date pickers
    initDatePickers();
    
    // Initialize print buttons
    initPrintButtons();
});

/**
 * Sidebar Toggle Functionality
 */
function initSidebarToggle() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }
}

/**
 * Confirmation Dialogs
 */
function initConfirmations() {
    // Find all elements with data-confirm attribute
    const confirmElements = document.querySelectorAll('[data-confirm]');
    
    confirmElements.forEach(function(element) {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {string} redirectUrl - URL to redirect if confirmed
 */
function confirmAction(message, redirectUrl) {
    if (confirm(message)) {
        window.location.href = redirectUrl;
    }
}

/**
 * Form Validations
 */
function initFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Add error message if not exists
                    let errorMsg = field.parentNode.querySelector('.form-error');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'form-error';
                        field.parentNode.appendChild(errorMsg);
                    }
                    errorMsg.textContent = 'This field is required';
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.parentNode.querySelector('.form-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(function(field) {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a valid email address');
                }
            });
            
            // Phone validation
            const phoneFields = form.querySelectorAll('input[data-phone]');
            phoneFields.forEach(function(field) {
                if (field.value && !isValidPhone(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a valid phone number');
                }
            });
            
            // Date range validation
            const startDate = form.querySelector('input[name="start_date"]');
            const endDate = form.querySelector('input[name="end_date"]');
            if (startDate && endDate && startDate.value && endDate.value) {
                if (new Date(startDate.value) > new Date(endDate.value)) {
                    isValid = false;
                    showFieldError(endDate, 'End date must be after start date');
                }
            }
            
            // Password match validation
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                showFieldError(confirmPassword, 'Passwords do not match');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Show field error message
 */
function showFieldError(field, message) {
    let errorMsg = field.parentNode.querySelector('.form-error');
    if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'form-error';
        field.parentNode.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

/**
 * Validate phone format (Nigerian)
 */
function isValidPhone(phone) {
    const pattern = /^(\+?234|0)[7-9][0-1][0-9]{8}$/;
    return pattern.test(phone);
}

/**
 * Auto-hide alerts after 5 seconds
 */
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

/**
 * Initialize date pickers
 */
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(function(input) {
        // Set max date to today for date of birth fields
        if (input.name === 'date_of_birth') {
            const today = new Date().toISOString().split('T')[0];
            input.setAttribute('max', today);
        }
        
        // Set min date to today for future date fields
        if (input.name === 'start_date' || input.name === 'end_date') {
            // Don't restrict - allow historical data entry
        }
    });
}

/**
 * Calculate leave days
 */
function calculateLeaveDays() {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    const totalDaysField = document.querySelector('input[name="total_days"]');
    
    if (startDate && endDate && totalDaysField && startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        
        if (start <= end) {
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            totalDaysField.value = diffDays;
        } else {
            totalDaysField.value = '';
        }
    }
}

/**
 * Initialize print buttons
 */
function initPrintButtons() {
    const printButtons = document.querySelectorAll('[data-print]');
    
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });
}

/**
 * Toggle user dropdown (placeholder for future enhancement)
 */
function toggleUserDropdown() {
    // Can be implemented with a dropdown menu
    // For now, just a placeholder
}

/**
 * Search table functionality
 */
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    const filter = input.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent || cells[j].innerText;
            if (cellText.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Skip action columns
            if (cols[j].classList.contains('actions')) continue;
            
            let data = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = 'Loading...';
    spinner.style.cssText = 'text-align: center; padding: 20px;';
    
    if (element) {
        element.innerHTML = '';
        element.appendChild(spinner);
    }
}

/**
 * Hide loading spinner
 */
function hideLoading(element) {
    const spinner = element.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * AJAX request helper
 */
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                callback(null, xhr.responseText);
            } else {
                callback(new Error('Request failed'), null);
            }
        }
    };
    
    xhr.send(data);
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format currency (Naira)
 */
function formatCurrency(amount) {
    return '₦' + formatNumber(parseFloat(amount).toFixed(2));
}

/**
 * Confirm delete action
 */
function confirmDelete(message, url) {
    const confirmMessage = message || 'Are you sure you want to delete this record? This action cannot be undone.';
    if (confirm(confirmMessage)) {
        window.location.href = url;
    }
}

/**
 * Preview image before upload
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Add event listeners for dynamic elements
document.addEventListener('click', function(e) {
    // Handle delete confirmations
    if (e.target.matches('.btn-delete') || e.target.closest('.btn-delete')) {
        const button = e.target.matches('.btn-delete') ? e.target : e.target.closest('.btn-delete');
        const message = button.getAttribute('data-message') || 'Are you sure you want to delete this record?';
        const url = button.getAttribute('data-url');
        
        if (url && !confirm(message)) {
            e.preventDefault();
        }
    }
});
