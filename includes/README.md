# Includes Directory

This directory contains reusable page components and authentication checks.

## Available Includes

### **auth_check.php**
Authentication check for protected pages.
```php
require_once 'includes/auth_check.php';
// Automatically checks if user is logged in, redirects to login if not
```

### **header.php** (Optional)
Common HTML header with navigation.
```php
$page_title = 'Dashboard';
$custom_css = 'dashboard.css'; // Optional
require_once 'includes/header.php';
// Includes: Bootstrap, Font Awesome, Sidebar, Flash messages
```

### **footer.php** (Optional)
Common HTML footer with scripts.
```php
$custom_js = 'tenants.js'; // Optional
require_once 'includes/footer.php';
// Includes: Bootstrap JS, Auto-dismiss alerts
```

## Usage Patterns

### Protected Page Template
```php
<?php
require_once 'includes/auth_check.php';

// Your page logic here
?>
<!DOCTYPE html>
<html>
<!-- Your HTML -->
</html>
```

### Using Common Header/Footer (Optional)
```php
<?php
require_once 'includes/auth_check.php';

$page_title = 'Tenants Management';
$custom_css = 'tenants.css';
$custom_js = 'tenants.js';

require_once 'includes/header.php';
?>

<!-- Your page content here -->

<?php require_once 'includes/footer.php'; ?>
```

## Notes

- `auth_check.php` is required for all protected pages
- `header.php` and `footer.php` are optional (use for new pages or refactoring)
- Existing pages can continue using their current structure
