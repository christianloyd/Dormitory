# BEN and SOF Dormitory Management System

A comprehensive web-based dormitory management system built with PHP and MySQL, designed to streamline operations for BEN and SOF Dormitory. This system handles tenant management, room assignments, billing, payments, and reporting.

## Features

### 🏠 Room Management
- Manage different room types (Bed Spacer, Whole Room)
- Track room capacity and availability
- Support for upper and lower deck bed configurations
- Customizable room pricing
- Real-time room occupancy tracking

### 👥 Tenant Management
- Complete tenant profile management with photo uploads
- Track tenant personal information and emergency contacts
- Active/Inactive tenant status management
- Tenant customization options
- Link tenants to specific rooms and deck assignments

### 💰 Billing & Payment System
- Automated bill generation
- Flexible billing components:
  - Base rent
  - Utility fees
  - Additional charges
  - Interest on late payments
  - Previous balance tracking
  - Credit balance management
- Multiple payment methods support
- Payment status tracking (Pending, Partial, Settled)
- Monthly payment records
- Outstanding balance tracking

### 📊 Reports & Analytics
- Dashboard with real-time statistics
- Collection reports
- Outstanding balance reports
- Billing summary reports
- Per-tenant payment history
- Monthly income tracking
- Export reports functionality

### 📅 Calendar & Reminders
- Due date calendar view
- SMS reminder system for tenants and guardians
- Payment notifications
- Due date tracking

### 🔔 Notification System
- Real-time notification center
- Payment confirmations
- Reminder messages
- Read/unread status tracking

### 🔒 Security
- Secure admin authentication
- Password hashing
- Session management
- Role-based access control

### 💾 Backup & Restore
- Database backup functionality
- Restore from previous backups
- Backup history tracking

## Technology Stack

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3.0
- **Server**: XAMPP (Apache + MySQL)

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Web Browser (Chrome, Firefox, Edge, Safari)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/dorm_system.git
   ```

2. **Move to XAMPP htdocs directory**
   ```bash
   # Windows
   xcopy /E /I dorm_system C:\xampp\htdocs\dorm_system
   
   # Linux/Mac
   cp -r dorm_system /opt/lampp/htdocs/dorm_system
   ```

3. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `dorm_db`
   - Import the SQL schema from `database.txt` or use the provided SQL commands

4. **Configure Database Connection**
   - Open `db.php`
   - Update database credentials if needed:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $dbname = "dorm_db";
     ```

5. **Set up Admin Account**
   - Default credentials are already set in the database
   - **Username**: `admin`
   - **Password**: `admin`
   - **Important**: Change the password after first login using `hash_password.php`

6. **Configure Permissions**
   - Ensure `uploads/` and `backups/` directories have write permissions

7. **Access the System**
   - Navigate to: `http://localhost/dorm_system/login.php`
   - Login with admin credentials

## Project Structure

```
dorm_system/
├── admin/                      # Admin-specific modules
├── assets/                     # Images and static assets
├── backups/                    # Database backup files
├── css/                        # Custom stylesheets
├── forms/                      # Modal forms and UI components
├── js/                         # JavaScript files
├── uploads/                    # Uploaded tenant photos and documents
├── dashboard.php               # Main dashboard
├── login.php                   # Admin login page
├── rooms.php                   # Room management
├── tenants.php                 # Tenant management
├── billing.php                 # Billing management
├── report.php                  # Reports interface
├── calendar.php                # Calendar view
├── backup_restore.php          # Backup/restore functionality
├── db.php                      # Database connection
└── README.md                   # This file
```

## Database Schema

### Main Tables
- **admin_account** - Admin user credentials
- **rooms** - Room information and availability
- **tenants** - Tenant personal information
- **billing** - Billing records and payment tracking
- **payments** - Payment history
- **notifications** - System notifications
- **sms_logs** - SMS reminder logs
- **settings** - System configuration
- **invoices** - Invoice generation data

## Key Features Details

### Billing System
The billing system supports complex calculations including:
- Automatic credit balance carryover
- Previous balance tracking
- Interest calculation on late payments
- Partial payment handling
- Multiple charge types (utilities, additional fees)

### Room Types
1. **Bed Spacer**: Individual bed rental with upper/lower deck options
2. **Whole Room**: Entire room rental

### Payment Status
- **Pending**: No payment received
- **Partial**: Partial payment received
- **Settled**: Fully paid

## Security Considerations

- Change default admin password immediately after installation
- Keep PHP and MySQL updated
- Restrict file upload types
- Regular database backups
- Use HTTPS in production environment
- Validate and sanitize all user inputs

## Customization

### Changing Login Background
1. Upload new image to `assets/` directory
2. Update in database: `settings` table, `profile_image` field

### Modifying Room Types
Edit the ENUM values in the `rooms` table schema

### Adding New Billing Components
Modify the billing calculation logic in `dashboard.php` and related billing files

## Troubleshooting

### Login Issues
- Verify database connection in `db.php`
- Check that `admin_account` table exists and has data
- Ensure sessions are enabled in PHP

### Upload Errors
- Check `uploads/` directory permissions (755 or 777)
- Verify PHP upload settings in `php.ini`

### Database Connection Failed
- Ensure MySQL service is running
- Verify database credentials
- Check if `dorm_db` database exists

## Future Enhancements

- Online payment gateway integration
- Mobile-responsive design improvements
- Email notification system
- Tenant portal for self-service
- Advanced analytics and reporting
- Multi-language support

## Contributing

Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is developed for BEN and SOF Dormitory. All rights reserved.

## Support

For issues, questions, or suggestions, please create an issue in the GitHub repository.

## Credits

Developed for dormitory management operations with a focus on efficiency and ease of use.

---

**Note**: This system is designed to run on a local XAMPP server. For production deployment, additional security measures and server configurations are required.
