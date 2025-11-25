// Newsletter Mailer Script
var VENUECLOUD_ID = 35;

// Email validation
function checkEmail(email) {
    var emailRe = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return emailRe.test(email);
}

// Subscribe function
function mailerSubscribe() {
    var email = document.getElementById('subscribeEmail').value;
    var firstName = document.getElementById('subscribeFirstName').value;
    var lastName = document.getElementById('subscribeLastName').value;
    var name = firstName + ' ' + lastName;
    var submitBtn = document.getElementById('mailerSubscribeBtn');

    // Validate email
    if (!checkEmail(email)) {
        alert('Please enter a valid email address');
        return false;
    }

    // Validate names
    if (!firstName.trim() || !lastName.trim()) {
        alert('Please enter your first and last name');
        return false;
    }

    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> Subscribing...';

    // Make API request
    fetch('https://www.venuecloud.net/api/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email,
            name: name,
            mobile: '',
            date_of_birth: '',
            account_id: VENUECLOUD_ID,
            source: 'website',
            contact_permission: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.errorCode === 'success') {
            // Hide form, show success message
            document.getElementById('subscribeForm').style.display = 'none';
            document.getElementById('newsletterSuccess').style.display = 'flex';
        } else {
            alert('There was an error: ' + (data.errorText || 'Please try again'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Subscribe';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Subscribe';
    });

    return false;
}

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('newsletterModal');
    var trigger = document.getElementById('newsletterTrigger');
    var closeBtn = document.getElementById('newsletterClose');
    var overlay = modal.querySelector('.newsletter-modal-overlay');
    var form = document.getElementById('subscribeForm');

    // Open modal
    trigger.addEventListener('click', function() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // Close modal - X button
    closeBtn.addEventListener('click', function() {
        closeModal();
    });

    // Close modal - overlay click
    overlay.addEventListener('click', function() {
        closeModal();
    });

    // Close modal - Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        mailerSubscribe();
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
});
