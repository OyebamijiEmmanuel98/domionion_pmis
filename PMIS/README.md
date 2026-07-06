# Personnel Management Information System (PMIS)
## Dominion University, Ibadan

---

## 📋 PROJECT OVERVIEW

This is a comprehensive web-based Personnel Management Information System developed as a final year academic project for Dominion University, Ibadan. The system is designed to centralize and streamline personnel/staff record management in an academic institution.

### Purpose
The PMIS addresses common challenges in manual personnel management:
- Slow retrieval of staff information
- Paper-based leave processing delays
- Duplicate and inconsistent records
- Poor data security
- Poor record preservation
- Weak access control
- Difficulty tracking staff assessments

---

## 🏗️ SYSTEM ARCHITECTURE

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: Core PHP (No frameworks)
- **Database**: MySQL
- **Server Environment**: XAMPP/WAMP

### Three-Tier Architecture
1. **Presentation Layer**: HTML, CSS, JavaScript, Forms, Tables, Dashboards
2. **Application Layer**: PHP Business Logic, Validation, Session Management
3. **Database Layer**: MySQL Tables, Foreign Keys, Queries, Indexes

---

## 📁 PROJECT STRUCTURE

```
pmis/
│
├── admin/                  # Admin-specific pages
│   ├── dashboard.php      # Admin dashboard
│   └── users.php          # User management
│
├── hr/                    # HR-specific pages
│   ├── dashboard.php      # HR dashboard
│   └── add_staff.php      # Add staff (shortcut)
│
├── hod/                   # HOD-specific pages
│   ├── dashboard.php      # HOD dashboard
│   └── review_leave.php   # Review leave (shortcut)
│
├── staff/                 # Staff-specific pages
│   ├── dashboard.php      # Staff dashboard
│   ├── profile.php        # View/Edit profile
│   └── apply_leave.php    # Apply for leave (shortcut)
│
├── modules/               # Shared module pages
│   ├── staff/            # Staff management
│   ├── departments/      # Department management
│   ├── leave/           # Leave management
│   ├── assessments/     # Assessment management
│   ├── reports/         # Reports
│   ├── users/          # User management
│   └── logs/           # System logs
│
├── includes/             # Shared PHP files
│   ├── header.php       # HTML header
│   ├── footer.php       # HTML footer
│   ├── sidebar.php      # Navigation sidebar
│   ├── auth.php         # Authentication functions
│   ├── functions.php    # Common functions
│   ├── session.php      # Session management
│   └── role_check.php   # Role-based access control
│
├── config/              # Configuration files
│   └── db.php          # Database connection
│
├── assets/             # Static assets
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   ├── images/        # Images
│   └── uploads/       # User uploads
│
├── database/           # Database files
│   └── pmis.sql       # Database schema
│
├── index.php          # Landing page
├── login.php          # Login page
├── logout.php         # Logout handler
├── dashboard.php      # Main dashboard redirect
├── change_password.php # Password change
├── access_denied.php  # Access denied page
└── README.md         # This file
```

---

## 👥 USER ROLES AND PERMISSIONS

### 1. System Administrator
**Capabilities:**
- Create/edit user accounts
- Assign roles
- Activate/deactivate users
- Reset passwords
- View all system logs
- Manage system-wide settings

### 2. Human Resources (HR)
**Capabilities:**
- Register new staff
- Edit staff records
- Manage leave types/policies
- Add staff assessments
- Generate university-wide reports
- Search and manage all personnel records

### 3. Head of Department (HOD)
**Capabilities:**
- View staff records in their department only
- Review and approve/reject leave requests
- Generate department-only reports
- Add assessments for department staff

### 4. Staff (Academic/Non-Academic)
**Capabilities:**
- View own profile
- Update limited personal information
- Apply for leave
- View leave history/status
- View own assessments

---

## 🗄️ DATABASE DESIGN

### Core Tables

#### 1. roles
- Stores system user roles
- Fields: id, role_name, description, created_at

#### 2. departments
- Stores university departments
- Fields: id, department_name, department_code, hod_staff_id, description

#### 3. staff
- Stores all personnel records
- Fields: id, staff_id, first_name, last_name, gender, date_of_birth, 
  department_id, rank, employment_condition, basic_salary, staff_type, status, etc.

#### 4. users
- Stores system user accounts
- Fields: id, username, email, password_hash, role_id, staff_id, status

#### 5. leave_types
- Stores different types of leave
- Fields: id, leave_name, max_days, description

#### 6. leave_applications
- Stores leave applications
- Fields: id, staff_id, leave_type_id, start_date, end_date, total_days, 
  reason, status, hod_comment, applied_at, reviewed_at

#### 7. assessments
- Stores staff performance assessments
- Fields: id, staff_id, assessor_user_id, assessment_date, report, recommendation

#### 8. activity_logs
- System audit trail
- Fields: id, user_id, action, table_name, record_id, description, ip_address, created_at

#### 9. login_logs
- Tracks user login/logout
- Fields: id, user_id, login_time, logout_time, ip_address, user_agent

---

## 🚀 INSTALLATION INSTRUCTIONS

### Prerequisites
- XAMPP or WAMP installed
- Web browser (Chrome, Firefox, Edge)
- Basic knowledge of PHP and MySQL

### Step-by-Step Setup

#### 1. Install XAMPP/WAMP
- Download and install XAMPP from https://www.apachefriends.org
- Start Apache and MySQL services

#### 2. Setup Project
```bash
# Copy project folder to htdocs
copy pmis folder to C:\xampp\htdocs\ (Windows)
or /opt/lampp/htdocs/ (Linux)
```

#### 3. Create Database
```
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "New" to create database
3. Name: pmis_dominion
4. Collation: utf8mb4_unicode_ci
5. Click "Create"
```

#### 4. Import Database Schema
```
1. Select pmis_dominion database
2. Click "Import" tab
3. Choose File: database/pmis.sql
4. Click "Go"
```

#### 5. Configure Database Connection
Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'pmis_dominion');
```

#### 6. Access the Application
```
Open browser and navigate to:
http://localhost/pmis/

Default Login Credentials:
- Admin:      username: admin          password: Password123!
- HR:         username: hr_manager     password: Password123!
- HOD:        username: hod_english    password: Password123!
- Staff:      username: staff_001      password: Password123!
```

---

## 🔒 SECURITY FEATURES

### Implemented Security Measures

1. **Password Security**
   - Passwords hashed using `password_hash()` (bcrypt)
   - Verified using `password_verify()`
   - Minimum password length enforcement

2. **SQL Injection Prevention**
   - All database queries use prepared statements
   - User input never directly concatenated into SQL

3. **XSS Prevention**
   - All output escaped using `htmlspecialchars()`
   - Input sanitized before display

4. **CSRF Protection**
   - CSRF tokens generated for all forms
   - Tokens validated on form submission

5. **Session Security**
   - Secure session cookie parameters
   - Session regeneration periodically
   - Session timeout after inactivity
   - HttpOnly cookies

6. **Access Control**
   - Role-based access control on all pages
   - Unauthorized access redirected to access denied page
   - Direct URL access to protected pages blocked

7. **Audit Logging**
   - All important actions logged
   - Login/logout tracking
   - IP address recording

---

## 📊 CORE MODULES

### 1. Authentication Module
- Login form with validation
- Session management
- Password hashing
- Role-based redirect
- Logout functionality

### 2. Dashboard Module
- Role-specific dashboards
- Statistics and summaries
- Quick action buttons
- Recent activities

### 3. Staff Management Module
- Add new staff
- Edit staff records
- View staff profiles
- Search and filter
- Department assignment

### 4. Department Module
- Create/edit departments
- Assign HOD
- View department staff
- Department statistics

### 5. Leave Management Module
- Apply for leave
- Review/approve/reject leave
- Leave history
- Leave types management
- Auto-calculate leave days

### 6. Assessment Module
- Add staff assessments
- View assessment history
- Recommendations tracking

### 7. Reports Module
- Staff reports
- Leave reports
- Department reports
- Printable formats

### 8. User Management Module
- Create user accounts
- Assign roles
- Reset passwords
- Activate/deactivate users

### 9. Activity Logs Module
- System audit trail
- Login history
- Action tracking

---

## 🧪 TESTING GUIDE

### Test Cases

#### Authentication Testing
| Test ID | Action | Expected Result |
|---------|--------|-----------------|
| AUTH-01 | Login with valid credentials | Successful login, redirect to dashboard |
| AUTH-02 | Login with invalid password | Error message displayed |
| AUTH-03 | Login with inactive account | Error: Account is inactive |
| AUTH-04 | Access protected page without login | Redirect to login page |
| AUTH-05 | Logout | Session destroyed, redirect to login |

#### Staff Management Testing
| Test ID | Action | Expected Result |
|---------|--------|-----------------|
| STAFF-01 | Add staff with valid data | Staff created successfully |
| STAFF-02 | Add staff with duplicate ID | Error: Staff ID exists |
| STAFF-03 | Edit staff record | Changes saved successfully |
| STAFF-04 | View staff profile | Profile displayed correctly |

#### Leave Management Testing
| Test ID | Action | Expected Result |
|---------|--------|-----------------|
| LEAVE-01 | Apply for leave | Application saved as pending |
| LEAVE-02 | Approve leave application | Status changed to approved |
| LEAVE-03 | Reject leave application | Status changed to rejected |
| LEAVE-04 | Invalid date range | Error: End date must be after start date |

---

## 🔧 TROUBLESHOOTING

### Common Issues

#### 1. Database Connection Error
**Solution:**
- Check if MySQL service is running
- Verify database credentials in config/db.php
- Ensure database exists

#### 2. "Access Denied" Page
**Solution:**
- Check user role permissions
- Ensure user is logged in
- Verify session hasn't expired

#### 3. CSS/JS Not Loading
**Solution:**
- Check file paths in browser console
- Ensure .htaccess allows static files
- Clear browser cache

#### 4. Session Timeout Too Quickly
**Solution:**
- Adjust session timeout in includes/session.php
- Check server session configuration

---

## 📈 FUTURE ENHANCEMENTS

### Possible Improvements
1. **Email Notifications** - Send email alerts for leave approvals
2. **Document Upload** - Upload and manage staff documents
3. **Advanced Search** - Full-text search across all records
4. **Data Export** - Export reports to PDF, Excel
5. **API Integration** - REST API for mobile apps
6. **Two-Factor Authentication** - Enhanced security
7. **Backup System** - Automated database backups
8. **Salary Management** - Complete payroll module
9. **Attendance Tracking** - Integration with biometric systems
10. **Mobile Responsive** - Better mobile experience

---

## 👨‍💻 DEVELOPMENT NOTES

### Code Standards
- All PHP files use `<?php` opening tags
- Consistent indentation (4 spaces)
- Descriptive variable and function names
- Comprehensive comments
- Error handling with try-catch blocks

### Naming Conventions
- Files: lowercase_with_underscores.php
- Functions: camelCase
- Database tables: lowercase, plural
- Database columns: lowercase, snake_case

### Security Checklist
- [ ] All forms have CSRF protection
- [ ] All database queries use prepared statements
- [ ] All output is escaped
- [ ] Passwords are hashed
- [ ] Session security enabled
- [ ] Role checks on all protected pages

---

## 📞 SUPPORT

For questions or issues:
1. Check this README first
2. Review the code comments
3. Check the database schema
4. Consult your project supervisor

---

## 📄 LICENSE

This project is developed for academic purposes as a final year project at Dominion University, Ibadan.

---

## 🙏 ACKNOWLEDGMENTS

- Dominion University, Ibadan
- Project Supervisor
- Department of Computer Science
- All contributors

---

**Project Version:** 1.0  
**Last Updated:** March 2026  
**Developer:** Final Year Student
"# domionion_pmis" 
