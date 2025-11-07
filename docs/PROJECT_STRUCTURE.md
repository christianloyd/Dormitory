# Project Structure Guide

## Directory Organization

```
Dormitory/
│
├── 📁 /helpers/              NEW - Security & Utility Classes
│   ├── Session.php           - Secure session management
│   ├── CSRF.php              - CSRF protection
│   ├── Database.php          - Secure query builder
│   ├── FileUpload.php        - Secure file uploads
│   ├── RateLimiter.php       - Brute force protection
│   ├── Validator.php         - Input validation
│   └── README.md             - Helper documentation
│
├── 📁 /includes/             NEW - Common Includes
│   ├── auth_check.php        - Authentication check
│   ├── header.php            - Common HTML header (optional)
│   ├── footer.php            - Common HTML footer (optional)
│   └── README.md             - Includes documentation
│
├── 📁 /forms/                Modal Forms & UI Components
│   ├── add_bill_form.php
│   ├── add_tenant_modal_form.php
│   ├── edit_tenant_modal_form.php
│   └── ... (other modal forms)
│
├── 📁 /css/                  Stylesheets
│   ├── sidebar.css
│   ├── dashboard.css
│   ├── tenants.css
│   └── ... (page-specific CSS)
│
├── 📁 /js/                   JavaScript Files
│   ├── room.js
│   ├── tenants.js
│   └── ... (page-specific JS)
│
├── 📁 /assets/               Static Assets (Images, etc.)
│   ├── login-bg.jpg
│   ├── nature.jpg
│   └── profile.jpg
│
├── 📁 /uploads/              User Uploaded Files
│   └── .htaccess             - Prevents PHP execution
│
├── 📁 /backups/              Database Backups
│
├── 📁 /logs/                 NEW - Application Logs
│   ├── .htaccess             - Protects log files
│   └── README.md             - Logging documentation
│
├── 📁 /docs/                 NEW - Documentation
│   ├── SECURITY_IMPROVEMENTS.md
│   ├── ENV_CONFIGURATION_GUIDE.md
│   ├── PAYMENT_REMINDER_FIX.md
│   ├── ROOMS_SEPARATION_GUIDE.md
│   └── SMS_PESO_SIGN_FIX.md
│
├── 📁 /config/               NEW - Configuration (Future Use)
│
├── 📄 Main Application Files (Root)
│   ├── index.php             NEW - Entry point
│   ├── login.php             - Login page (SECURED)
│   ├── logout.php            - Logout handler (SECURED)
│   ├── dashboard.php         - Main dashboard
│   ├── tenants.php           - Tenant management
│   ├── rooms.php             - Room management
│   ├── billing.php           - Billing/calendar
│   ├── user.php              - User settings
│   └── ... (62+ PHP files)
│
├── 📄 Core Configuration
│   ├── .env                  - Environment variables (PROTECTED)
│   ├── .env.example          - Environment template
│   ├── config.php            - Environment loader
│   ├── db.php                - Database connection (SECURED)
│   └── sms_helper.php        - SMS functionality
│
├── 📄 Security Files
│   ├── .htaccess             NEW - Root security config
│   └── .gitignore            - Git ignore rules
│
└── 📄 Documentation
    ├── README.md             - Project overview
    ├── PROJECT_STRUCTURE.md  - This file
    ├── database.txt          - Database schema
    └── FLOW SA SYSTEM.txt    - System flow notes
```

---

## Directory Purposes

### 🔒 **Security Directories**

#### `/helpers/` - NEW ✨
**Purpose:** Reusable security and utility classes
**Protected:** Yes (via root `.htaccess`)
**Auto-loaded:** Yes (by `db.php`)

Contains all security helpers:
- Session management
- CSRF protection
- Database security
- File upload security
- Rate limiting
- Input validation

#### `/includes/` - NEW ✨
**Purpose:** Common page components and authentication
**Protected:** Partially (auth_check.php auto-loads from db.php)
**Usage:** Include in pages for consistent functionality

### 📝 **Application Directories**

#### `/forms/`
**Purpose:** Modal forms and reusable UI components
**Usage:** Included in main pages for forms/modals

#### `/css/` & `/js/`
**Purpose:** Frontend assets
**Organization:** Page-specific files (e.g., `tenants.css`, `tenants.js`)

#### `/assets/`
**Purpose:** Static images and resources
**Contents:** Login backgrounds, default images, logos

### 💾 **Data Directories**

#### `/uploads/`
**Purpose:** User-uploaded files (tenant photos, proofs)
**Protected:** Yes (`.htaccess` prevents PHP execution)
**Security:** File validation enforced by FileUpload helper

#### `/backups/`
**Purpose:** Database backup files
**Automated:** Via backup_restore.php

#### `/logs/` - NEW ✨
**Purpose:** Application and error logs
**Protected:** Yes (`.htaccess` blocks web access)
**Usage:** `error_log("message")` in PHP

### 📚 **Documentation Directories**

#### `/docs/` - NEW ✨
**Purpose:** All project documentation and guides
**Contents:**
- Security improvements guide
- Configuration guides
- Feature implementation guides
- System flow documentation

---

## File Loading Order

```
1. index.php or login.php (entry)
   ↓
2. db.php (or includes/auth_check.php)
   ↓
3. config.php (.env loader)
   ↓
4. ALL helpers loaded:
   - Session.php (session started)
   - CSRF.php
   - Database.php
   - FileUpload.php
   - RateLimiter.php
   - Validator.php
   ↓
5. Database connection created
   ↓
6. SMS helper loaded
   ↓
7. Your page logic
```

---

## Migration Status

### ✅ Completed (Phase 1 - Security)
- [x] Created `/helpers/` directory
- [x] Created security helper classes
- [x] Created `/includes/` directory
- [x] Created `auth_check.php`
- [x] Updated core files (db.php, login.php, logout.php)
- [x] Fixed critical security vulnerabilities
- [x] Added `.htaccess` files

### ✅ Completed (Phase 2 - Structure)
- [x] Created `/logs/` directory
- [x] Created `/docs/` directory
- [x] Organized documentation files
- [x] Created `index.php` entry point
- [x] Added README files to directories
- [x] Created common includes (header.php, footer.php)

### 🔄 Optional (Future Improvements)
- [ ] Apply CSRF to remaining forms
- [ ] Migrate pages to use common header/footer
- [ ] Add comprehensive error logging
- [ ] Implement audit logging
- [ ] Add unit tests

---

## Best Practices

### For New Files
```php
<?php
require_once 'includes/auth_check.php';

// Optional: Use common header
$page_title = 'Page Title';
$custom_css = 'custom.css';
require_once 'includes/header.php';

// Your content here

// Optional: Use common footer
require_once 'includes/footer.php';
?>
```

### For Existing Files
- Keep current structure (no need to change)
- Gradually migrate to use helpers
- Add CSRF protection as you modify forms
- Use Database helper for new queries

---

## Security Boundaries

### Protected from Web Access
- `/helpers/` - Via root `.htaccess`
- `/uploads/` - PHP execution blocked
- `/logs/` - Via `.htaccess`
- `.env` file - Via root `.htaccess`
- `.git/` folder - Via root `.htaccess`

### Public Access Required
- All main PHP files in root
- `/css/`, `/js/`, `/assets/` directories
- `/forms/` directory (included, not accessed directly)

---

## Adding New Features

1. **New helper class:** Add to `/helpers/`, load in `db.php`
2. **New common include:** Add to `/includes/`
3. **New page:** Create in root, use `auth_check.php`
4. **New form:** Create in `/forms/`, add CSRF protection
5. **New documentation:** Add to `/docs/`

---

## Maintenance Notes

- **Logs:** Monitor and rotate logs in `/logs/` regularly
- **Backups:** `/backups/` should be backed up externally
- **Uploads:** `/uploads/` may grow large, monitor disk space
- **Documentation:** Keep `/docs/` updated with system changes

---

**Last Updated:** November 7, 2025
**Structure Version:** 2.0 (Post-Security Implementation)
