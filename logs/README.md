# Logs Directory

This directory stores application log files.

## Files

- **error.log** - PHP errors and warnings
- **security.log** - Security events (failed logins, file upload attempts, etc.)
- **application.log** - General application logs

## Security

- This directory is protected by `.htaccess` to prevent web access
- Logs should be monitored regularly for security issues
- Old logs should be archived and deleted periodically

## Configuration

PHP error logging is configured in `.htaccess` (root) and `db.php`:
```php
error_log("Message here");
```
