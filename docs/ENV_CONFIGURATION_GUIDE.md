# Environment Configuration Guide

## ✅ Credentials Now Secured!

Your API keys and database credentials are now stored in a separate `.env` file for better security!

---

## 🔒 Why Use .env Files?

### Security Benefits:

1. **Keep Secrets Out of Code** ✅
   - API keys not visible in source code
   - Can't accidentally share credentials
   - Safe to commit code to Git

2. **Easy Configuration** ✅
   - Change settings without editing code
   - Different settings for dev/production
   - One file to manage all credentials

3. **Best Practice** ✅
   - Industry standard approach
   - Recommended by security experts
   - Prevents credential leaks

---

## 📁 Files Created

### 1. [.env](c:\xampp\htdocs\dorm_system\.env) - Your Actual Credentials
**Contains your real API keys and passwords**

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=dorm_db

# SMS API Configuration (IPROG)
SMS_ENABLED=true
SMS_API_URL=https://sms.iprogtech.com/api/v1/sms_messages
SMS_API_TOKEN=b3372e928050d30de930b74a8bf86321b21ccc74
SMS_SENDER=BEN & SOF Dormitory
```

**⚠️ IMPORTANT:**
- ❌ **NEVER commit this file to Git**
- ❌ **NEVER share this file publicly**
- ✅ Keep it secure on your server only
- ✅ Already added to .gitignore

---

### 2. [.env.example](c:\xampp\htdocs\dorm_system\.env.example) - Template
**Template file without sensitive data**

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=dorm_db

# SMS API Configuration (IPROG)
SMS_ENABLED=true
SMS_API_URL=https://sms.iprogtech.com/api/v1/sms_messages
SMS_API_TOKEN=your_iprog_api_token_here
SMS_SENDER=BEN & SOF Dormitory
```

**Purpose:**
- ✅ Safe to commit to Git
- ✅ Shows what settings are needed
- ✅ Template for new installations

---

### 3. [config.php](c:\xampp\htdocs\dorm_system\config.php) - Environment Loader
**Loads variables from .env file**

**Key Functions:**

```php
// Load all environment variables
loadEnv(__DIR__ . '/.env');

// Get environment variable with default fallback
env('SMS_API_TOKEN', 'default_value');

// Convert string to boolean
envBool(env('SMS_ENABLED', 'true'));
```

---

### 4. [db.php](c:\xampp\htdocs\dorm_system\db.php) - Updated Database Config
**Now uses environment variables instead of hardcoded values**

**Before:**
```php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "dorm_db";

define('SMS_API_TOKEN', 'b3372e928050d30de930b74a8bf86321b21ccc74');
```
❌ Credentials visible in code

**After:**
```php
require_once __DIR__ . '/config.php';

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbname = env('DB_NAME', 'dorm_db');

define('SMS_API_TOKEN', env('SMS_API_TOKEN', ''));
```
✅ Credentials loaded from .env

---

### 5. [.gitignore](c:\xampp\htdocs\dorm_system\.gitignore) - Git Security
**Prevents .env from being committed to Git**

```gitignore
# Environment Configuration (IMPORTANT!)
.env

# PHP Error Logs
php_error_log
error_log

# IDE Files
.vscode/
.idea/
```

---

## 🎯 How It Works

### Flow Diagram:

```
User accesses page
    ↓
db.php is loaded
    ↓
config.php is loaded
    ↓
.env file is read
    ↓
Environment variables loaded into constants
    ↓
Database connection uses env variables
    ↓
SMS API uses env variables
    ↓
Application runs with secure credentials ✅
```

---

## 🔧 Usage

### Accessing Environment Variables:

**In your code:**
```php
// Get database host
$host = env('DB_HOST', 'localhost');

// Get SMS API token
$token = env('SMS_API_TOKEN');

// Get boolean value
$smsEnabled = envBool(env('SMS_ENABLED', 'true'));

// Or use constants (already defined in db.php)
$token = SMS_API_TOKEN;
$url = SMS_API_URL;
```

---

## ⚙️ Configuration Options

### Database Settings:

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `DB_HOST` | Database server | localhost | localhost |
| `DB_USER` | Database username | root | root |
| `DB_PASS` | Database password | (empty) | mypassword |
| `DB_NAME` | Database name | dorm_db | dorm_db |

---

### SMS Settings:

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `SMS_ENABLED` | Enable/disable SMS | true | true/false |
| `SMS_API_URL` | IPROG API endpoint | https://sms.iprogtech.com/api/v1/sms_messages | (URL) |
| `SMS_API_TOKEN` | Your IPROG API key | (none) | b3372e92... |
| `SMS_SENDER` | Sender name | BEN & SOF Dormitory | Your Name |

---

## 📝 Modifying Settings

### To Change API Token:

1. Open `.env` file
2. Find line: `SMS_API_TOKEN=b3372e928050d30de930b74a8bf86321b21ccc74`
3. Replace with new token
4. Save file
5. Refresh your application

**No code changes needed!** ✅

---

### To Disable SMS:

1. Open `.env` file
2. Change: `SMS_ENABLED=false`
3. Save file
4. All SMS sending will stop

---

### To Change Database:

1. Open `.env` file
2. Update database settings:
```env
DB_HOST=localhost
DB_USER=admin
DB_PASS=newpassword
DB_NAME=new_database
```
3. Save file
4. Application uses new database

---

## 🚀 Setting Up on New Server

### Steps for New Installation:

1. **Copy project files** to new server

2. **Create .env from template:**
```bash
cp .env.example .env
```

3. **Edit .env** with your credentials:
```bash
nano .env  # or use text editor
```

4. **Update these values:**
   - `SMS_API_TOKEN` - Your IPROG token
   - `DB_PASS` - Your database password (if any)
   - Other settings as needed

5. **Done!** Application automatically uses your settings

---

## 🔒 Security Best Practices

### DO ✅

1. **Keep .env secure**
   - Store only on your server
   - Set file permissions: `chmod 600 .env`
   - Never email or share publicly

2. **Use .env.example**
   - Commit to Git as template
   - Update when adding new settings
   - Share with team safely

3. **Backup .env separately**
   - Keep secure backup
   - Store encrypted
   - Don't include in code backups

4. **Use different tokens**
   - Dev server: Test token
   - Production: Live token
   - Never use production token in dev

---

### DON'T ❌

1. **Never commit .env to Git**
   - Already in .gitignore
   - Check before pushing code
   - Remove if accidentally committed

2. **Never hardcode credentials**
   - Use env() function instead
   - Keep all secrets in .env
   - No API keys in code files

3. **Never share .env publicly**
   - Don't post in forums
   - Don't send via email
   - Don't commit to GitHub

4. **Never use production credentials in dev**
   - Use separate test API tokens
   - Prevent accidental charges
   - Avoid data mixing

---

## 🧪 Testing Configuration

### Verify Environment Variables Loaded:

Create `test_config.php`:
```php
<?php
require_once 'config.php';

echo "Database Host: " . env('DB_HOST') . "\n";
echo "Database Name: " . env('DB_NAME') . "\n";
echo "SMS Enabled: " . env('SMS_ENABLED') . "\n";
echo "SMS API URL: " . env('SMS_API_URL') . "\n";
echo "SMS Token: " . substr(env('SMS_API_TOKEN'), 0, 10) . "..." . "\n";
?>
```

Run:
```bash
php test_config.php
```

**Expected Output:**
```
Database Host: localhost
Database Name: dorm_db
SMS Enabled: true
SMS API URL: https://sms.iprogtech.com/api/v1/sms_messages
SMS Token: b3372e9280...
```

---

## 🔄 Migration from Old System

### What Changed:

**Before:**
```php
// db.php (hardcoded credentials)
$host = "localhost";
$user = "root";
$pass = "";
define('SMS_API_TOKEN', 'b3372e928050d30de930b74a8bf86321b21ccc74');
```

**After:**
```php
// db.php (loads from .env)
require_once __DIR__ . '/config.php';
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
define('SMS_API_TOKEN', env('SMS_API_TOKEN', ''));
```

**Your Application:**
- ✅ Works exactly the same
- ✅ No changes needed to other files
- ✅ All existing code still works
- ✅ Just more secure now!

---

## 📊 File Structure

```
dorm_system/
├── .env                    ← Your credentials (NEVER commit)
├── .env.example            ← Template (safe to commit)
├── .gitignore              ← Blocks .env from Git
├── config.php              ← Loads environment variables
├── db.php                  ← Updated to use env vars
├── sms_helper.php          ← Uses constants from db.php
└── (other files)           ← No changes needed
```

---

## 🎯 Summary

### What You Got:

1. ✅ **Secure credential storage** - API keys in .env file
2. ✅ **Easy configuration** - Edit one file, no code changes
3. ✅ **Git-safe** - .env excluded from version control
4. ✅ **Best practices** - Industry standard approach
5. ✅ **Backward compatible** - All existing code works

### Your Credentials:

**Database:**
- Host: localhost
- User: root
- Password: (none)
- Database: dorm_db

**IPROG SMS:**
- API Token: `b3372e928050d30de930b74a8bf86321b21ccc74`
- API URL: `https://sms.iprogtech.com/api/v1/sms_messages`
- Sender: BEN & SOF Dormitory
- Status: Enabled

---

## 🚨 Important Reminders

1. **Never commit .env to Git** - Already protected by .gitignore
2. **Backup .env securely** - Keep encrypted copy
3. **Use .env.example for sharing** - Template without secrets
4. **Change tokens if exposed** - Regenerate on IPROG dashboard
5. **Check .gitignore** - Ensure .env is listed

---

## 📞 Quick Reference

**To change API token:**
```bash
# Edit .env file
nano .env
# Find: SMS_API_TOKEN=old_token
# Replace with: SMS_API_TOKEN=new_token
# Save and reload
```

**To disable SMS:**
```bash
# Edit .env file
nano .env
# Change: SMS_ENABLED=false
# Save
```

**To add new setting:**
```bash
# 1. Add to .env
MY_NEW_SETTING=value

# 2. Use in code
$setting = env('MY_NEW_SETTING', 'default');
```

---

**Status**: ✅ **CONFIGURED**
**Security**: ✅ **IMPROVED**
**Ease of Use**: ✅ **ENHANCED**

Your credentials are now secure and easy to manage! 🎉
