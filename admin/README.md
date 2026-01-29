# TCFS Admin Panel

## Setup Instructions

### 1. Database Setup

First, ensure your database is set up properly by running the migration file to add the admin role:

```
http://localhost/TCFS/config/add_admin_role.php
```

This will:
- Add a `role` column to the users table
- Create a default admin account

### 2. Default Admin Credentials

```
Email: admin@tcfs.com
Password: admin123
```

**IMPORTANT:** Change the admin password immediately after first login through the Settings page.

### 3. Features

#### Manage Users
- View all registered users
- Edit user information (name, email, DOB, gender, bio)
- Delete users
- Search users by name or email
- View detailed user information

#### Manage Trips
- View all trips
- Edit trip details (name, destination, dates, budget, status)
- Delete trips
- Search trips by name or destination
- View detailed trip information including host details

#### Admin Settings
- Update admin profile
- Change password with validation
- View system statistics
- Database cleanup tools
- Data export options

### 4. Validation Features

#### User Form Validation:
- Name: Minimum 2 characters
- Email: Valid email format check
- Age: Must be between 13-120 years

#### Trip Form Validation:
- Trip name: Minimum 3 characters
- Destination: Minimum 2 characters
- Description: Minimum 10 characters
- End date must be after start date
- Max budget must be greater than min budget
- Budget must be positive numbers

#### Password Validation:
- Minimum 8 characters
- Must contain uppercase letter
- Must contain lowercase letter
- Must contain number
- New password must be different from current

### 5. File Structure

```
admin/
├── adminDashboard.php      - Main dashboard with statistics
├── manageUsers.php         - User management interface
├── manageTrips.php         - Trip management interface
├── adminSettings.php       - Admin settings and profile
├── sidebar.php             - Reusable sidebar navigation
└── api/
    ├── users.php          - User CRUD operations
    ├── trips.php          - Trip CRUD operations
    └── settings.php       - Settings operations

assets/
├── css/
│   └── admin.css          - Admin panel styles
└── js/
    ├── admin-users.js     - User management JS with validation
    ├── admin-trips.js     - Trip management JS with validation
    └── admin-settings.js  - Settings JS with validation

config/
└── add_admin_role.php     - Migration file for admin setup
```

### 6. Security Features

- Session-based authentication
- Admin role verification on all admin pages
- SQL injection protection using prepared statements
- XSS protection with htmlspecialchars()
- Password hashing using PHP password_hash()
- CSRF protection ready

### 7. Responsive Design

The admin panel is fully responsive and works on:
- Desktop (optimized)
- Tablet
- Mobile devices

### 8. Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

### 9. Technologies Used

- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5.3
- Bootstrap Icons
- Vanilla JavaScript (ES6+)
- CSS3 with Grid and Flexbox

### 10. Usage Tips

1. Always backup database before performing cleanup operations
2. Test changes in a development environment first
3. Regular password changes are recommended
4. Monitor system statistics regularly
5. Use search functionality for quick access to specific records

### 11. Troubleshooting

If you encounter issues:

1. Check database connection in `config/db.php`
2. Ensure `role` column exists in users table
3. Verify admin account exists with correct role
4. Check PHP error logs
5. Ensure proper file permissions

### 12. Maintenance

Regular tasks:
- Review and clean old data periodically
- Monitor user and trip growth
- Check for abandoned accounts
- Verify trip completion statuses
- Update admin credentials regularly
