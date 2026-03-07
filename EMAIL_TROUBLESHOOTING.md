# Email Configuration Troubleshooting Guide

## Problem: Emails are not being sent

### Current Status
- Email templates are working (variables are being replaced)
- Email data is being properly mapped
- But emails are failing to send (showing "FAILED" in logs)

### Root Causes & Solutions

## 1. SMTP Server Issues

**Problem**: The SMTP server `gw.sgitech.com.vn:25` may not be accessible or credentials may be wrong.

**Solutions**:
- Check if the SMTP server is reachable
- Verify username and password
- Try different port (587 for TLS, 465 for SSL)
- Test with a different SMTP service

## 2. PHP Mail Function Not Configured

**Problem**: XAMPP's PHP mail function may not be configured to send emails.

**Solutions**:
### Option A: Configure XAMPP Mercury Mail
1. Open XAMPP Control Panel
2. Start Mercury Mail service
3. Configure Mercury to relay emails
4. Update `php.ini` to use Mercury

### Option B: Use Gmail SMTP (Recommended)
Update `lib/EmailHelper.php`:
```php
$this->config = array(
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-gmail@gmail.com',
    'password' => 'your-app-password', // Use app password, not regular password
    'from_email' => 'your-gmail@gmail.com',
    'from_name' => 'IT Service Request System'
);
```

### Option C: Use SendGrid (Production Ready)
```php
$this->config = array(
    'host' => 'smtp.sendgrid.net',
    'port' => 587,
    'username' => 'apikey',
    'password' => 'YOUR_SENDGRID_API_KEY',
    'from_email' => 'noreply@yourcompany.com',
    'from_name' => 'IT Service Request System'
);
```

## 3. Quick Fix for Testing

**Temporary Solution**: Modify EmailHelper to always succeed for testing:

```php
public function sendEmail($to, $toName, $subject, $body) {
    // Log the email for testing
    $this->logEmail($to, $subject, $body, 'TEST_MODE');
    
    // Return true to prevent blocking the application
    return true;
}
```

## 4. Testing Email Configuration

Run the email test: `http://localhost/it-service-request/test-email.php`

This will test:
- PHP mail function availability
- SMTP server connectivity
- EmailHelper functionality
- Recent email logs

## 5. Recommended Production Setup

For production use, consider:

1. **SendGrid** - Reliable, easy to set up
2. **Mailgun** - Good for transactional emails
3. **Amazon SES** - Cost-effective for high volume
4. **Company SMTP** - If you have a corporate mail server

## 6. Security Notes

- Never commit email passwords to version control
- Use environment variables for credentials
- Use app passwords for Gmail (not regular passwords)
- Consider using OAuth2 for better security

## 7. Current Email Flow

1. User creates request → 
2. Service request saved to database → 
3. EmailHelper.sendNewRequestNotification() called → 
4. Template variables replaced → 
5. Email sent via SMTP or PHP mail → 
6. Status logged to `logs/email_activity.log`

## 8. Debug Steps

1. Check `logs/email_activity.log` for recent failures
2. Run `test-email.php` to diagnose issues
3. Check XAMPP error logs
4. Test with simple PHP mail() function
5. Verify SMTP credentials and connectivity

## 9. Immediate Fix

For immediate testing, you can:

1. Open `test-email.php` in browser
2. See which email method works
3. Update EmailHelper configuration accordingly
4. Test creating a new service request

The email system is properly structured - we just need to configure the delivery method!
