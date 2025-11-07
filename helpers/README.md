# Helper Classes Directory

This directory contains reusable security and utility helper classes.

## Available Helpers

### Security Helpers

#### **Session.php**
Secure session management with timeout and regeneration.
```php
Session::init();           // Initialize session
Session::login($username); // Login user
Session::requireAuth();    // Protect pages
Session::destroy();        // Logout
Session::setMessage($msg, $type); // Flash messages
```

#### **CSRF.php**
Cross-Site Request Forgery protection.
```php
echo CSRF::getTokenField(); // Add to forms
CSRF::verifyRequest();      // Verify in handlers
```

#### **Database.php**
Secure database query builder.
```php
$db->select('table', ['id' => 5]);
$db->insert('table', ['name' => 'John']);
$db->update('table', ['name' => 'Jane'], ['id' => 5]);
$db->delete('table', ['id' => 5]);
```

#### **FileUpload.php**
Secure file upload with validation.
```php
$uploader = new FileUpload();
$path = $uploader->upload($_FILES['file'], 'profile');
$uploader->delete($old_path);
```

#### **RateLimiter.php**
Login brute force protection.
```php
$limiter = new RateLimiter();
$limiter->checkLimit($username);
$limiter->recordAttempt($username, $success);
```

### Utility Helpers

#### **Validator.php**
Input validation functions.
```php
Validator::isValidPhoneNumber($phone);
Validator::isValidEmail($email);
Validator::isValidDate($date);
Validator::validateMultiple($data, $rules);
```

## Usage

All helpers are automatically loaded by `db.php`:
```php
require_once 'db.php'; // All helpers available
```

## Adding New Helpers

1. Create new PHP class file in this directory
2. Add `require_once` in `db.php`
3. Document usage here

## Security Notes

- Never expose helper classes to direct web access (protected by `.htaccess`)
- All helpers use error logging instead of displaying errors
- Follow existing patterns for consistency
