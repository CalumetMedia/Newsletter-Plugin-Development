// Handling the test email dialog and operations
jQuery(document).ready(function($) {
    // Test email dialog handling
    $('#send-test-email').on('click', function(e) {
        e.preventDefault();
        // Reset dialog state
        $('#test-email').val('');
        $('#email-input-step').show();
        $('#success-step').hide();
        $('#test-email-dialog').fadeIn(200);
    });

    // Close dialog handlers
    $('#cancel-test, #close-success, .dialog-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#test-email-dialog').fadeOut(200);
        }
    });

    // Send test email
    $('#send-test').on('click', function() {
        const testEmail = $('#test-email').val().trim();
        if (!testEmail) {
            alert('Please enter an email address');
            return;
        }

        // Show loading state
        $(this).prop('disabled', true);
        $(this).text('Sending...');

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'send_test_email',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Test email sent successfully!');
                } else {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Error sending test email: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#email-input-step').hide();
                $('#success-step').show().find('p').text('Error connecting to server: ' + error);
            },
            complete: function() {
                // Reset button state
                $('#send-test').prop('disabled', false).text('Send');
            }
        });
    });
});