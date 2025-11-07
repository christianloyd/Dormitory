# Moving Files to /pages/ Directory - Implementation Guide

## Overview

This guide explains the considerations and steps for moving all main PHP files from the root directory to a `/pages/` subdirectory.

---

## Why This Wasn't Done Initially

Moving 62 PHP files to `/pages/` requires updating:
- **200+ `header()` redirects** across all files
- **100+ form action attributes**
- **Dozens of `include` paths**
- **JavaScript AJAX endpoints**
- **Sidebar navigation links**
- **Database-stored redirect URLs**

**Risk Level:** HIGH - Could break the entire application if not done carefully.

---

## Current Structure (Implemented)

```
Dormitory/
├── /helpers/              ✅ Security classes
├── /includes/             ✅ Common includes
├── /forms/                ✅ Modal forms
├── /css/, /js/            ✅ Assets
├── /uploads/, /backups/   ✅ Data
├── /logs/, /docs/         ✅ NEW - Organization
├── /config/               ✅ NEW - Future use
├── index.php              ✅ NEW - Entry point
├── login.php, logout.php  ✅ Root level
└── [62 main PHP files]    ⚠️ Still in root
```

**Status:** Organized, secure, functional ✅

---

## Option 1: Keep Current Structure (Recommended)

### Advantages
- ✅ **Zero breaking changes**
- ✅ **Works immediately**
- ✅ **All security improvements active**
- ✅ **Good organization with helpers/includes**
- ✅ **No redirect updates needed**

### When to Use
- You want security and organization NOW
- You have a working production system
- You prefer stability over perfect structure

---

## Option 2: Move to /pages/ (Advanced)

### Structure After Migration
```
Dormitory/
├── /pages/                ← All main PHP files
│   ├── dashboard.php
│   ├── tenants.php
│   ├── rooms.php
│   └── ... (60+ files)
├── index.php              ← Stays in root
├── login.php              ← Stays in root
├── logout.php             ← Stays in root
└── [other directories]
```

### What Needs Updating

#### 1. All Header Redirects (200+ occurrences)
```php
// OLD (throughout codebase)
header('Location: dashboard.php');
header('Location: tenants.php');

// NEW
header('Location: pages/dashboard.php');
header('Location: pages/tenants.php');
```

#### 2. All Form Actions (100+ occurrences)
```php
// OLD
<form action="update_tenant.php" method="POST">

// NEW
<form action="pages/update_tenant.php" method="POST">
```

#### 3. Sidebar Navigation
```php
// sidebar.php - OLD
<a href="dashboard.php">Dashboard</a>
<a href="tenants.php">Tenants</a>

// NEW
<a href="pages/dashboard.php">Dashboard</a>
<a href="pages/tenants.php">Tenants</a>
```

#### 4. Include Paths (in moved files)
```php
// OLD (from root)
require_once 'includes/auth_check.php';
require_once 'db.php';

// NEW (from /pages/)
require_once '../includes/auth_check.php';
require_once '../db.php';
```

#### 5. JavaScript AJAX Calls
```javascript
// OLD
fetch('update_tenant.php', ...)

// NEW
fetch('pages/update_tenant.php', ...)
```

#### 6. CSS/JS Asset Paths
```php
// OLD (from root)
<link href="css/dashboard.css">

// NEW (from /pages/)
<link href="../css/dashboard.css">
```

---

## Implementation Steps (If You Choose Option 2)

### Step 1: Preparation
```bash
# Create pages directory
mkdir pages

# Create a test branch
git checkout -b feature/move-to-pages
```

### Step 2: Move Files
```bash
# Move all main PHP files EXCEPT login.php, logout.php, index.php
mv dashboard.php tenants.php rooms.php billing.php pages/
# ... repeat for all 60+ files
```

### Step 3: Update Redirects Script
Create a helper script to update all redirects:

```php
<?php
// update_paths.php - Run once to update all files

$files_to_update = glob('pages/*.php');
$replacements = [
    // Update header redirects
    "header('Location: " => "header('Location: ../",
    'header("Location: ' => 'header("Location: ../',

    // Update includes from /pages/
    "require_once 'includes/" => "require_once '../includes/",
    "require_once 'db.php'" => "require_once '../db.php'",
    "include 'sidebar.php'" => "include '../sidebar.php'",

    // Update asset paths
    'href="css/' => 'href="../css/',
    'src="js/' => 'src="../js/',
    'src="assets/' => 'src="../assets/',
];

foreach ($files_to_update as $file) {
    $content = file_get_contents($file);
    $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
    file_put_contents($file, $updated);
    echo "Updated: $file\n";
}

echo "Path updates complete!\n";
?>
```

### Step 4: Update Sidebar
```php
// sidebar.php
<li><a href="pages/dashboard.php">Dashboard</a></li>
<li><a href="pages/tenants.php">Tenants</a></li>
<li><a href="pages/rooms.php">Rooms</a></li>
// ... update all links
```

### Step 5: Update Entry Points
```php
// index.php
if (Session::isLoggedIn()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: login.php');
}
```

### Step 6: Test Everything
- [ ] Login works
- [ ] All navigation links work
- [ ] Forms submit correctly
- [ ] Redirects go to right pages
- [ ] File uploads work
- [ ] JavaScript functions work
- [ ] CSS styles load correctly

### Step 7: Fix Broken Links
Manually review and fix:
- Form modal actions
- JavaScript AJAX calls
- Any dynamically generated URLs
- Database-stored redirect URLs

---

## Recommended Approach: Hybrid Solution

**Keep it simple and practical:**

```
Dormitory/
├── /helpers/              ✅ NEW - Helper classes
├── /includes/             ✅ NEW - Common includes
├── /docs/                 ✅ NEW - Documentation
├── /logs/                 ✅ NEW - Log files
├── /forms/, /css/, /js/   ✅ Existing structure
├── [Main PHP files]       ✅ Stay in root (functional)
```

**Benefits:**
- ✅ Improved security (implemented)
- ✅ Better organization (helpers, includes, docs)
- ✅ Zero downtime
- ✅ No broken links
- ✅ Works immediately

---

## When to Move to /pages/

Consider moving to `/pages/` during:
- **Major refactoring** - When you're already updating many files
- **Version 2.0** - Complete rewrite
- **New features** - New pages go in `/pages/` first
- **Slow migration** - Move files gradually, one at a time

---

## Gradual Migration Strategy

Instead of moving all files at once:

1. **New pages** → Create in `/pages/` from start
2. **When editing** → Move file to `/pages/` and update references
3. **One module at a time** → Move tenant module, test, then rooms module, etc.
4. **Keep tracking** → Maintain list of migrated vs non-migrated files

---

## Conclusion

**Current Status:** ✅ Your project now has:
- Secure helper classes
- Organized documentation
- Protected logs directory
- Common includes available
- Entry point (index.php)

**Recommendation:**
- ✅ **Keep current structure** - It's clean, organized, and works
- 🔄 **Gradual migration** - Move files to `/pages/` one module at a time
- ⏰ **Future refactoring** - Plan `/pages/` move for version 2.0

**Your system is now well-organized and secure without the risks of mass file relocation!**

---

**Last Updated:** November 7, 2025
