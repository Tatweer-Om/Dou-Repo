<script>
    function get_date_only(dateString) {
    // Convert the date string to a Date object
    const date = new Date(dateString);

    // Format the date as needed, for example: "YYYY-MM-DD"
    return date.toISOString().split('T')[0]; // Adjust the format as needed
}


function show_notification(type, msg) {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: 'toast-top-right', // Set position to top-right
            preventDuplicates: false,
            onclick: null,
            showDuration: '300',
            hideDuration: '1000',
            timeOut: '5000',
            extendedTimeOut: '1000',
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
        if (type == "success") {
            toastr.success(msg, type);
        } else if (type == "error") {
            toastr.error(msg, type);
        } else if (type == "warning") {
            toastr.warning(msg, type);
        }
        // Ensure toastr appears above modals (z-index 9998)
        $('#toast-container').css('z-index', '99999');
    }

    function before_submit() {
        $('.submit_form').attr('disabled', true);
        $('.submit_form').html(
            'Please wait <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>');
    }

    function after_submit() {
        $('.submit_form').attr('disabled', false);
        $('.submit_form').html('Submit');
    }

function show_preloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.classList.remove('hidden');
        preloader.style.opacity = '1';
    }
}

function hide_preloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(() => preloader.classList.add('hidden'), 300);
    }
}

 function generateBarcode() {
    // Generate a random 8-12 digit number
    const randomCode = Math.floor(10000000 + Math.random() * 90000000);
    document.getElementById('barcode').value = randomCode;
  }

// Validation for quantity inputs (integers only, no decimals, no special characters)
window.validateQuantityInput = function(event) {
    const keyCode = event.keyCode || event.which;
    const key = event.key;
    
    // Allow: backspace, delete, tab, escape, enter, and arrow keys
    if ([8, 9, 27, 13, 46, 37, 38, 39, 40].indexOf(keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (keyCode === 65 && event.ctrlKey === true) ||
        (keyCode === 67 && event.ctrlKey === true) ||
        (keyCode === 86 && event.ctrlKey === true) ||
        (keyCode === 88 && event.ctrlKey === true)) {
        return true;
    }
    
    // Allow only digits (0-9) - prevent 'e', 'E', '+', '-', '.', and all other characters
    if ((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105)) {
        return true;
    }
    
    // Prevent all other keys
    event.preventDefault();
    return false;
};

// Validation for amount/price inputs (digits and decimals only, no special characters or alphabets)
window.validateAmountInput = function(event) {
    const input = event.target;
    const keyCode = event.keyCode || event.which;
    const key = event.key;
    
    // Allow: backspace, delete, tab, escape, enter, and arrow keys
    if ([8, 9, 27, 13, 46, 37, 38, 39, 40].indexOf(keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (keyCode === 65 && event.ctrlKey === true) ||
        (keyCode === 67 && event.ctrlKey === true) ||
        (keyCode === 86 && event.ctrlKey === true) ||
        (keyCode === 88 && event.ctrlKey === true)) {
        return true;
    }
    
    // Allow digits (0-9)
    if ((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105)) {
        return true;
    }
    
    // Allow decimal point (.) only once
    if ((keyCode === 190 || keyCode === 110) && input.value.indexOf('.') === -1) {
        return true;
    }
    
    // Prevent all other keys including 'e', 'E', '+', '-'
    event.preventDefault();
    return false;
};

// Clean input on paste for quantity (remove non-digits)
window.cleanQuantityOnPaste = function(event) {
    const input = event.target;
    setTimeout(() => {
        const oldValue = input.value;
        input.value = input.value.replace(/[^0-9]/g, '');
        // Trigger input event for Alpine.js and other frameworks
        if (input.value !== oldValue) {
            const inputEvent = new Event('input', { bubbles: true });
            input.dispatchEvent(inputEvent);
            // Also trigger change event
            const changeEvent = new Event('change', { bubbles: true });
            input.dispatchEvent(changeEvent);
        }
    }, 0);
};

// Clean input on paste for amount (remove invalid characters, keep only digits and one decimal)
window.cleanAmountOnPaste = function(event) {
    const input = event.target;
    setTimeout(() => {
        let value = input.value;
        const oldValue = value;
        // Remove all non-digit and non-decimal characters (including 'e', 'E', '+', '-')
        value = value.replace(/[^0-9.]/g, '');
        // Ensure only one decimal point
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        input.value = value;
        // Trigger input event for Alpine.js and other frameworks
        if (input.value !== oldValue) {
            const inputEvent = new Event('input', { bubbles: true });
            input.dispatchEvent(inputEvent);
            // Also trigger change event
            const changeEvent = new Event('change', { bubbles: true });
            input.dispatchEvent(changeEvent);
        }
    }, 0);
};

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Apply quantity validation to all quantity inputs with data-validate="quantity"
    document.querySelectorAll('input[type="number"][data-validate="quantity"]').forEach(function(input) {
        input.addEventListener('keydown', window.validateQuantityInput);
        input.addEventListener('paste', window.cleanQuantityOnPaste);
    });
    
    // Apply amount validation to all amount/price inputs with data-validate="amount"
    document.querySelectorAll('input[type="number"][data-validate="amount"]').forEach(function(input) {
        input.addEventListener('keydown', window.validateAmountInput);
        input.addEventListener('paste', window.cleanAmountOnPaste);
    });
});

</script>