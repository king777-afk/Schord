# SCHoRD Email Verification System (2FA)

## Overview
The login system now includes a two-factor authentication (2FA) process with email verification codes. This adds a security layer by requiring users to verify their identity via an email code before gaining access.

## How It Works

### Step 1: Email & Password Login
- User enters their email and password on the login page
- System validates the email format and checks credentials
- If credentials are correct, a 6-digit verification code is **generated and emailed** to the user

### Step 2: Email Verification
- User receives an email with a verification code
- User enters the 6-digit code on the verification page
- System validates the code (must be entered within 15 minutes)
- If verification is successful, user is logged in and redirected to their dashboard

### Features
✅ **Email Validation** - Ensures valid email format before sending codes
✅ **6-Digit Verification Codes** - Random, secure codes sent to user's email
✅ **15-Minute Expiry** - Codes automatically expire for security
✅ **Attempt Limiting** - Maximum 5 failed attempts per code
✅ **Resend Option** - Users can request a new code if needed
✅ **Beautiful UI** - Seamless verification form with clear instructions
✅ **Error Handling** - User-friendly error messages

## Setup Instructions

### 1. Add Verification Table to Database
Run the migration script to create the verification_codes table:

```bash
# Via browser: Visit your site and navigate to:
http://yoursite.com/schord/utils/add_verification_table.php

# Or via PHP command line:
php add_verification_table.php
```

### 2. Ensure mail() is Enabled
The system uses PHP's built-in `mail()` function. Make sure:
- Your hosting provider has mail() enabled
- SPF/DKIM records are configured (for better delivery)
- Check if emails are going to spam

### 3. Test the System
1. Visit the login page: `/auth/login.php`
2. Enter a valid email and password
3. Check your inbox for the verification code
4. Enter the code to complete login

## Files Modified/Created

### Modified Files
- **[auth/login.php](../auth/login.php)** - Added two-step verification flow
- **[config/db.php](../config/db.php)** - Added email functions
- **[database.sql](../database.sql)** - Added verification_codes table schema

### New Files
- **[utils/add_verification_table.php](./add_verification_table.php)** - Migration script

## Database Schema

```sql
CREATE TABLE verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempt_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY (email),
    KEY (expires_at)
);
```

## Functions Added to db.php

### `generateVerificationCode()`
Generates a random 6-digit verification code.
```php
$code = generateVerificationCode(); // Returns: "123456"
```

### `sendVerificationEmail($email, $code)`
Sends a verification code email to the user.
```php
$sent = sendVerificationEmail("user@bsu.edu.ph", "123456");
```

### `createVerificationCode($email)`
Creates and stores a verification code in the database.
```php
$result = createVerificationCode("user@bsu.edu.ph");
// Returns: ['success' => true, 'code' => '123456']
```

### `verifyCode($email, $code)`
Verifies if the entered code matches the stored code.
```php
$result = verifyCode("user@bsu.edu.ph", "123456");
// Returns: ['success' => true] or ['success' => false, 'error' => '...']
```

### `cleanupExpiredCodes()`
Removes expired verification codes from the database.
```php
cleanupExpiredCodes();
```

## User Session Flow

### Login Session Variables
During the verification process:
- `$_SESSION['pending_verification']` - Indicates user is in verification step
- `$_SESSION['pending_email']` - Email waiting for verification
- `$_SESSION['pending_user']` - User data stored temporarily

After successful verification:
- Variables cleared
- `$_SESSION['user']` - Set with user data
- User redirected to their dashboard

## Email Template
The verification email includes:
- Professional HTML formatting
- Clear code display
- Expiry time (15 minutes)
- Security warning
- SCHoRD branding

## Security Considerations

🔐 **Code Generation** - Uses `random_int()` for cryptographically secure random codes
🔐 **Code Storage** - Plain text in database (6 digits, minimal risk)
🔐 **Code Expiry** - Automatic expiry after 15 minutes
🔐 **Attempt Limiting** - Maximum 5 failed attempts per code
🔐 **Input Validation** - Strict validation of email format and code format
🔐 **SQL Injection Prevention** - Uses `sanitize()` function for all inputs
🔐 **Session Protection** - Temporary session variables cleared after verification

## Troubleshooting

### Emails Not Sending
- Check if hosting provider has mail() enabled
- Look for emails in spam folder
- Check mail log on server: `tail -f /var/log/mail.log`
- Test with: `mail('test@example.com', 'Test', 'Test email');`

### Code Verification Failing
- Ensure user copied entire 6-digit code
- Check that code hasn't expired (15-minute limit)
- Verify database table exists: `SHOW TABLES;`
- Check error message for specific issue

### Users Locked Out
- Code can be resent using "Resend Code" button
- Failed codes are automatically cleaned up after expiry
- Clear verification session if needed (see Cancel button)

### Database Issues
- Ensure `verification_codes` table exists
- Run migration script: `add_verification_table.php`
- Check table status: `DESCRIBE verification_codes;`

## Future Enhancements

Potential improvements:
- SMS verification option
- Remember device option (skip 2FA on trusted devices)
- Admin dashboard to manage verification codes
- Login attempt analytics
- Rate limiting on failed attempts
- TOTP (Time-based One-Time Password) support

## Support
For issues or questions, check the troubleshooting section above or contact the development team.

---
**Last Updated**: April 8, 2026
**System**: SCHoRD v2.0
**Security Level**: ⭐⭐⭐⭐ (4/5)
