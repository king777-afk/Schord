# ⚠️ SCHoRD 2FA System - Quick Troubleshooting

## 🚀 Quick Start (DO THIS FIRST!)

### Step 1: Create the Database Table
Visit **IMMEDIATELY** in your browser:
```
http://yoursite.com/schord/utils/add_verification_table.php
```

You should see: ✅ "Database table 'verification_codes' created successfully!"

### Step 2: Test the System
After creating the table, visit:
```
http://yoursite.com/schord/utils/test_2fa.php
```

This will:
- ✓ Check if table exists
- ✓ Check if functions are working
- ✓ Generate a test verification code
- ✓ Let you verify the code
- ✓ Show if email is working

### Step 3: Test Real Login
Go to: `http://yoursite.com/schord/auth/login.php`

1. Enter: `admin@schord.com` (or your email)
2. Enter: `admin` (the password)
3. Press Login

**You should now see:**
- ✅ "Verification code sent to your email"
- ✅ A form asking for the 6-digit code
- ✅ Email received with the code

---

## 🔍 Debug Tools Available

### 1. **add_verification_table.php** (Migration)
- **Location:** `/utils/add_verification_table.php`
- **Purpose:** Creates the verification_codes table
- **When to use:** First time only, or if table is missing

### 2. **debug_verification.php** (Comprehensive Debug)
- **Location:** `/utils/debug_verification.php`
- **Purpose:** Tests everything:
  - Database connection
  - Table exists
  - PHP functions
  - Mail availability
  - Session support
  - File modifications
- **When to use:** When something isn't working

### 3. **test_2fa.php** (Interactive Test)
- **Location:** `/utils/test_2fa.php`
- **Purpose:** Step-by-step testing
  - Generate test codes
  - Verify codes
  - See what emails would contain
- **When to use:** To manually test the flow

---

## ❌ Common Issues & Fixes

### Issue 1: "Verification code sent..." but NO email received
**Possible Causes:**
1. mail() function not enabled on server
2. Email going to spam folder
3. Email address typo

**How to Fix:**
1. Check spam/junk folder first
2. Visit `/utils/debug_verification.php` - see "Test 4: Mail Function"
3. If mail() says NOT available:
   - Contact your hosting provider
   - Ask them to enable mail() function
   - Or use a different hosting provider that supports mail()

### Issue 2: Verification form not showing after login
**Possible Causes:**
1. verification_codes table not created
2. Database error
3. Browser cache

**How to Fix:**
1. **First:** Run `/utils/add_verification_table.php`
2. Clear browser cache (Ctrl+Shift+Delete)
3. Try login again
4. If still not working, run `/utils/debug_verification.php` and check errors

### Issue 3: "Invalid verification code" error
**Possible Causes:**
1. Wrong code entered
2. Code expired (15 minute limit)
3. Too many wrong attempts (5 max)
4. Code doesn't exist in database

**How to Fix:**
1. Request a new code (Resend button)
2. Enter the EXACT code from email
3. Try within 15 minutes
4. Check `/utils/test_2fa.php` to generate fresh test codes

### Issue 4: SQL errors in debug tool
**Possible Causes:**
1. Wrong database credentials
2. Database connection lost
3. User doesn't have permission

**How to Fix:**
1. Check `/config/db.php` database credentials
2. Test connection: `/utils/check_db.php` (if exists)
3. Contact hosting provider

### Issue 5: Email sent but code incorrect
**Possible Causes:**
1. Multiple codes generated (old ones not deleted)
2. Database timestamp issue
3. Code mismatch

**How to Fix:**
1. Use the most recent email code
2. Run `/utils/debug_verification.php` to check database
3. Request a fresh code via Resend button

---

## 📋 What Actually Changed

ALL changes are complete. Here's what's running:

### In `/auth/login.php`:
```php
// NEW: Step detection
$step = 'login'; // or 'verify'

// NEW: Verification code sending
if (password correct) {
    createVerificationCode($email); // Generate code
    sendVerificationEmail($email, $code); // Email it
    $step = 'verify'; // Show verification form
}

// NEW: Code verification
if (user submits code) {
    verifyCode($email, $code); // Check if correct
    if correct: $_SESSION['user'] = user; // Login!
}

// NEW: HTML form for verification
<?php if ($step == 'verify'): ?>
    <!-- Shows verification form here -->
<?php endif; ?>
```

### In `/config/db.php`:
```php
generateVerificationCode()      // Makes random 6-digit code
sendVerificationEmail()         // Emails the code
createVerificationCode()        // Saves to database
verifyCode()                    // Checks if correct
cleanupExpiredCodes()           // Removes old codes
```

### In `/database.sql`:
```sql
CREATE TABLE verification_codes (
    id, email, code, expires_at, attempt_count, ...
)
```

---

## ✅ How It Should Work

```
User visits login.php
    ↓
Enters email + password
    ↓
System checks credentials
    ↓
If WRONG: "Invalid credentials" error
If RIGHT:
    ↓
    1. Generate 6-digit code (654321)
    2. Save to database with 15-min expiry
    3. Email code to user's inbox
    4. Show verification form
    ↓
User gets email with code
    ↓
User enters code in form
    ↓
System checks if code is correct
    ↓
If WRONG: "Invalid code" error
If RIGHT:
    ↓
    1. Delete used code
    2. Set $_SESSION['user']
    3. Redirect to dashboard
    ↓
Logged in! ✓
```

---

## 🧪 Manual Verification Steps

**Without Email (For Testing):**

1. Open browser Developer Tools (F12)
2. Go to Console
3. Run this PHP in a test page:
```php
// Get the code from database
$result = mysqli_query($conn, "SELECT code FROM verification_codes 
                              WHERE email='admin@schord.com' 
                              ORDER BY created_at DESC LIMIT 1");
$code = mysqli_fetch_assoc($result);
echo $code['code']; // Shows: 123456
```
4. Use that code in the verification form

**With Email:**
1. Check your email inbox
2. Look for "SCHoRD - Login Verification Code"
3. Copy the 6-digit code
4. Paste into verification form

---

## 📞 Support Check

### System Status:
- ✅ Code changes: **APPLIED**
- ✅ Database schema: **ADDED**
- ✅ Functions: **DEFINED**
- ⚠️ Database table: **NEEDS CREATION** (Run add_verification_table.php)
- ⚠️ Email: **DEPENDS ON SERVER** (Contact hosting if not working)

### Before Asking for Help:

1. ✓ Run `/utils/add_verification_table.php`
2. ✓ Run `/utils/debug_verification.php` and check all tests
3. ✓ Run `/utils/test_2fa.php` and test the flow
4. ✓ Try login at `/auth/login.php`
5. ✓ Check email inbox (and spam folder)

---

## 💡 Quick Reference

| What | Where | Why |
|------|-------|-----|
| Create table | `/utils/add_verification_table.php` | Stores verification codes |
| Debug system | `/utils/debug_verification.php` | Find what's broken |
| Test flow | `/utils/test_2fa.php` | Try it out |
| Check login | `/auth/login.php` | See verification form |
| Check email | Check your inbox | Get the code |

---

**Last Updated:** April 8, 2026
**Status:** Ready to use after running add_verification_table.php
