# TeamHub - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

### Prerequisites
- XAMPP/WAMP installed and running
- PHP 8.0+ 
- MySQL/MariaDB
- Modern web browser

### Step 1: Database Setup
1. Start Apache and MySQL in XAMPP
2. Create database: `teamhub`
3. Import database:
   ```bash
   mysql -u root teamhub < "database/teamhub.sql"
   ```

### Step 2: Configuration
1. Edit `config/db.php` if needed (default should work with XAMPP)
2. Ensure audio files are in `assets/sounds/` (see REAL_TIME_FEATURES.md)

### Step 3: Access Application
1. Open browser to: `http://localhost/teamhub/`
2. Login with:
   - **Admin**: username: `admin`, password: `Admin@123`
   - **Employee**: username: `employee` (or `employee2`), password: `Employee@123`

## 📋 Default Users

| Username | Role | Default Password |
|----------|------|------------------|
| admin | Admin | Admin@123 |
| employee | Employee | Employee@123 |
| employee2 | Employee | Employee@123 |

## 🎯 Quick Test Scenario

### As Admin:
1. Login with admin credentials
2. Go to Admin Dashboard
3. Assign a new task to an employee
4. Check Records page to see the task

### As Employee:
1. Login with employee credentials  
2. Go to Tasks page
3. See the assigned task with countdown
4. Mark task as completed
5. Check dashboard for updated stats

## 🔧 Troubleshooting Quick Fixes

### Common Issues:
1. **Database connection error**: Check XAMPP MySQL is running
2. **Page not found**: Ensure files are in `htdocs/teamhub/`
3. **Audio not playing**: Check browser permissions for audio
4. **Real-time not working**: Ensure JavaScript is enabled

### Quick Checks:
```bash
# Check PHP version
php -v

# Check MySQL
mysql --version

# Check file permissions
ls -la /path/to/teamhub/
```

## 📞 Support
- Refer to `TEAMHUB_COMPLETE_DOCUMENTATION.md` for detailed info
- Check `REAL_TIME_FEATURES.md` for audio file requirements
- Review database structure in `database/teamhub.sql`

## ⚡ Performance Tips
- Use Chrome/Firefox for best real-time performance
- Keep browser console open to monitor JavaScript errors
- Test with one admin and one employee simultaneously

---

*This quick start guide helps you get TeamHub running in minutes. For full documentation, refer to the complete documentation file.*
