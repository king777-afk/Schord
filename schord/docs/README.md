# 📋 SCHoRD - School Health & Record Database

A comprehensive web-based system for managing student health records, clinic visits, and student information in educational institutions.

## ✨ Features

- **👥 Student Management** - Add, edit, and manage student information
- **📝 Clinic Visit Records** - Track medical visits and treatments
- **📊 Health Records** - Maintain student allergies and medical conditions
- **🔐 User Authentication** - Secure login system with role-based access
- **📈 Dashboard** - View statistics and recent activities at a glance
- **🎨 Modern Design** - Beautiful, responsive UI with professional styling

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache, MySQL, PHP) - Already installed!
- Web Browser

### Installation & Setup

#### Step 1: Start the Services
The project is already set up at `C:\xampp\htdocs\schord`

**Start Apache and MySQL:**
1. Run `C:\xampp\apache_start.bat` (in administrator mode)
2. Run `C:\xampp\mysql_start.bat` (in administrator mode)

Or use the XAMPP Control Panel:
- Open `C:\xampp\xampp-control.exe`
- Click "Start" next to Apache and MySQL

#### Step 2: Initialize the Database
1. Open your browser and go to: `http://localhost/schord/check_db.php`
2. The page will automatically create the database and tables
3. A demo admin user will be created

#### Step 3: Login
1. Open: `http://localhost/schord/`
2. You'll be redirected to the login page
3. Use these demo credentials:
   - **Email:** admin@schord.com
   - **Password:** admin123

## 📖 User Guide

### Dashboard
The main dashboard shows:
- Total students enrolled
- Total clinic visits recorded
- Ongoing visits needing attention
- Total staff members
- Recent clinic visit history

### 👥 Student Management
- **Add Student:** Click "Add Student" to register new students
- **View Students:** See all registered students in a table
- **Edit Student:** Modify student information
- **Delete Student:** Remove student records (if no health data exists)
- **View Health:** Quick link to view student's health records

### 📝 Clinic Visits
- **Record Visit:** Log new clinic visits with complaints and treatments
- **Edit Visit:** Update existing visit records
- **View All Visits:** See complete history of all clinic visits
- **Status Tracking:** Mark visits as "Ongoing" or "Completed"

### 📋 Health Records
- **Add Health Record:** Document student allergies and medical conditions
- **Edit Record:** Update health information
- **View Records:** See all students' health data for quick reference
- **Delete Record:** Remove outdated health information

### 🔑 User Management
- **Register New Staff:** Create new user accounts with different roles
- **Roles Available:**
  - **Admin:** Full system access
  - **Nurse:** Can manage visits and health records
  - **Staff:** Can view and add basic information

## 🎨 Design Highlights

- **Modern Color Scheme:** Professional purple gradient background with clean white cards
- **Responsive Layout:** Works perfectly on desktop, tablet, and mobile
- **Intuitive Navigation:** Easy-to-use top navigation bar
- **Status Badges:** Visual indicators for visit status (Ongoing/Completed)
- **Empty States:** Helpful messages when no data exists
- **Quick Actions:** Card-based dashboard for rapid access to key features
- **Form Validation:** Client-side and server-side validation
- **Error Handling:** Clear error and success messages

## 📁 Project Structure

```
schord/
├── index.php              # Entry point (redirects to login/dashboard)
├── dashboard.php          # Main dashboard
├── students.php           # Student management
├── visits.php             # Clinic visit records
├── health_records.php     # Health records management
├── check_db.php          # Database initialization
├── database.sql          # SQL schema
├── auth/
│   ├── login.php         # Login page
│   ├── register.php      # Registration page
│   └── logout.php        # Logout handler
├── config/
│   └── db.php            # Database configuration
├── includes/
│   ├── header.php        # Navigation header
│   └── footer.php        # Footer
├── assets/
│   └── style.css         # Main stylesheet
└── README.md             # This file
```

## 🔐 Security Features

- **Password Hashing:** Bcrypt password encryption
- **Session Management:** Secure session handling
- **Input Sanitization:** Protection against SQL injection
- **Authentication Check:** Login required for all main pages
- **Password Validation:** Minimum 6 characters, confirmation check

## 🗄️ Database Schema

### Users Table
- Stores user accounts and credentials
- Roles: admin, nurse, staff

### Students Table
- Student information (name, number, course, age)

### Health Records Table
- Medical allergies and existing conditions
- Linked to students

### Clinic Visits Table
- Visit logs with date, complaint, treatment
- Status tracking (ongoing/completed)
- Linked to students

## 💡 Tips & Tricks

### Create Multiple Test Accounts
1. Go to the login page
2. Click "Register here"
3. Fill in the form with different roles
4. Use for testing different user types

### Add Test Data
1. On the Dashboard, click "Manage Students"
2. Add several test students
3. Go to "Record Visit" and create visits for them
4. Check the dashboard to see statistics

### Export Data
All data is stored in MySQL database at: `C:\xampp\mysql\data\schord_db\`

You can access MySQL data using phpMyAdmin at: `http://localhost/phpmyadmin`

## 🛠️ Troubleshooting

### Services Won't Start
- Run XAMPP Control Panel as Administrator
- Check if ports 80 (Apache) or 3306 (MySQL) are already in use
- Restart your computer if issues persist

### Database Connection Error
1. Make sure MySQL is running
2. Visit `http://localhost/schord/check_db.php` to initialize
3. Check `config/db.php` for correct credentials

### Login Not Working
1. Visit `check_db.php` first to ensure database is set up
2. Use correct credentials: admin@schord.com / admin123
3. Make sure MySQL is running

### Page Won't Load
1. Check that Apache is running
2. Verify the URL is: `http://localhost/schord/`
3. Check browser console for errors (F12)

## 📝 Notes

- All dates are recorded in the server's timezone
- Deleting a student requires no clinics visits (they will be preserved separately)
- Password must be at least 6 characters
- Email addresses must be unique

## 🔄 Backup Your Data

To backup your database:
1. Use phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `schord_db` database
3. Click "Export"
4. Save the SQL file

## 🎓 Future Enhancements

Potential features to add:
- User profile management
- Report generation and export
- Student search and filters
- Multiple clinic facility support
- Email notifications
- Mobile app
- API for integration
- Analytics dashboard
- Prescription management
- Medical history timeline

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section
2. Verify all services are running
3. Clear browser cache (Ctrl+Shift+Delete)
4. Restart XAMPP services

## ⚖️ License

This project is created for educational purposes.

---

**Happy using SCHoRD! 📋✨**

Made with ❤️ for School Health Management
