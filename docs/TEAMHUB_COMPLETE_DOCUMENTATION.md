# TeamHub - Complete Project Documentation

## 📋 Project Overview

TeamHub is a comprehensive task management system designed specifically for managing designer workflows, tracking task assignments, and providing real-time collaboration between administrators and employees. The system features role-based access control, real-time updates, and advanced task tracking capabilities.

## 🏗️ System Architecture

### Technology Stack
- **Backend**: PHP 8.0+ with MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS + Chart.js)
- **Database**: MySQL with InnoDB engine
- **Server**: Apache with .htaccess configuration
- **Real-time Communication**: localStorage-based event system

### Directory Structure
```
teamhub/
├── 📁 api/                    # API endpoints (RESTful)
│   ├── add_bonus_points.php
│   ├── add_record.php
│   ├── complete_task.php
│   ├── delete_record.php
│   ├── export.php
│   ├── get_completed_tasks.php
│   ├── get_last_update.php
│   ├── get_task_status.php
│   └── pause_resume.php
├── 📁 assets/                 # Static assets
│   ├── 📁 images/            # User profile pictures
│   │   ├── default_profile.png
│   │   └── fav_icon.png
│   └── 📁 sounds/            # Notification sounds
│       ├── new_task.mp3
│       ├── task_completed.mp3
│       └── README.md
├── 📁 backup/                 # Backup files
│   ├── records copy.php
│   └── tasks copy.php
├── 📁 config/                 # Configuration files
│   ├── add_bonus_points_column.php
│   ├── add_pause_start_time.sql
│   ├── db.php
│   ├── hash.php
│   ├── optimize_database.php
│   ├── setup_bonus_logs.php
│   ├── setup_database.php
│   └── setup_pause_resume.sql
├── 📁 css/                    # Stylesheets
│   ├── admin_stylesheet.css
│   ├── edit_stylesheet.css
│   ├── employee_stylesheet.css
│   ├── login_stylesheet.css
│   ├── records_stylesheet.css
│   ├── sidebar_stylesheet.css
│   ├── styles.css
│   └── tasks_stylesheet.css
├── 📁 database/                # Database schema & demo data
│   ├── teamhub.sql
│   └── demo_data.sql
├── 📁 includes/               # PHP includes and components
│   ├── navbar_admin.php
│   ├── sidebar.php
│   ├── verify_admin.php
│   ├── verify_employee.php
│   └── verify_session.php
├── 📁 js/                     # JavaScript files
│   ├── admin_script.js
│   └── backToTop.js
├── 📁 pages/                  # Main application pages
│   ├── admin_dashboard.php
│   ├── bonus_points.php
│   ├── edit_record.php
│   ├── employee_dashboard.php
│   ├── progress_display.php
│   ├── records.php
│   ├── summary.php
│   └── tasks.php
├── 📁 tests/                  # Test files
│   ├── test_edit_functionality.php
│   └── test.php
├── .htaccess                  # Apache configuration
├── index.php                  # Login page
├── logout.php                 # Logout functionality
├── README.md                  # Basic project overview
└── REAL_TIME_FEATURES.md      # Real-time features documentation
```

## 🗄️ Database Schema

### Tables Structure

#### 1. `users` Table
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
);
```

#### 2. `designer_points` Table (Main Tasks Table)
```sql
CREATE TABLE `designer_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designer_name` varchar(100) DEFAULT NULL,
  `order_no` tinytext DEFAULT NULL,
  `work` text DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `allotted_points` float DEFAULT NULL,
  `points_gained` float DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `work_type` varchar(50) DEFAULT NULL,
  `allotted_time` int(11) DEFAULT NULL,
  `assigned_time` datetime DEFAULT current_timestamp(),
  `completion_time` datetime DEFAULT NULL,
  `completed_time` int(11) DEFAULT NULL,
  `is_paused` tinyint(1) DEFAULT 0,
  `pause_start_time` datetime DEFAULT NULL,
  `paused_duration` int(11) DEFAULT 0,
  `bonus_points` float DEFAULT 0,
  PRIMARY KEY (`id`)
);
```

#### 3. `auto_points_plan` Table (Points Configuration)
```sql
CREATE TABLE `auto_points_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `points` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id`)
);
```

#### 4. `bonus_logs` Table
```sql
CREATE TABLE `bonus_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designer_name` varchar(100) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `bonus_points` float NOT NULL,
  `reason` text DEFAULT NULL,
  `added_by` varchar(100) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bonus_logs_designer` (`designer_name`),
  KEY `idx_bonus_logs_task` (`task_id`),
  KEY `idx_bonus_logs_time` (`added_at`),
  CONSTRAINT `bonus_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `designer_points` (`id`) ON DELETE SET NULL
);
```

#### 5. `pause_logs` Table
```sql
CREATE TABLE `pause_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designer_point_id` int(11) NOT NULL,
  `pause_time` datetime NOT NULL,
  `resume_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designer_point_id` (`designer_point_id`),
  CONSTRAINT `pause_logs_ibfk_1` FOREIGN KEY (`designer_point_id`) REFERENCES `designer_points` (`id`) ON DELETE CASCADE
);
```

## 👥 User Roles and Permissions

### Admin Role
- Access to admin dashboard
- Task assignment and management
- View all records with filtering
- Pause/resume tasks
- Add bonus points
- Export data
- User management

### Employee Role
- Access to employee dashboard
- View assigned tasks
- Mark tasks as completed
- View personal statistics and charts
- Receive real-time notifications

## 🔄 Workflow Processes

### 1. Task Assignment Flow
1. Admin logs in and accesses admin dashboard
2. Fills task assignment form with:
   - Designer name
   - Order number
   - Work type and description
   - Allotted time
3. System automatically calculates allotted points (time/10)
4. Task is saved to database via AJAX
5. Real-time notification sent to employee

### 2. Task Completion Flow
1. Employee views tasks page
2. Clicks "Mark Completed" on a task
3. System calculates:
   - Actual time used (considering pauses)
   - Points gained based on performance
4. Updates task status in database
5. Sends real-time notification to admin

### 3. Pause/Resume Flow
1. Admin pauses a task from records page
2. System records pause time and updates task status
3. Countdown timer stops for employee
4. When resumed, system calculates paused duration
5. Countdown timer resumes with extended deadline

## 🎯 Core Features

### Real-Time Features
- **AJAX-based operations** for seamless user experience
- **LocalStorage communication** between admin and employee pages
- **Live countdown timers** with pause/resume support
- **Sound notifications** for important events
- **Auto-refresh** functionality every 30 seconds

### Task Management
- **Multi-work selection** in task assignment
- **Points system** based on time allocation and performance
- **Bonus points** system for exceptional work
- **Advanced filtering** and search capabilities
- **Data export** functionality

### Reporting and Analytics
- **Daily and monthly statistics** for employees
- **Chart.js integration** for visual data representation
- **Performance tracking** with assigned vs gained points
- **Task completion rate** monitoring

## 🛠️ API Endpoints

### 1. `add_record.php`
- **Method**: POST
- **Purpose**: Create new task assignment
- **Parameters**: designer_name, order_no, work, work_type, amount, allotted_time
- **Response**: JSON with success status and task details

### 2. `complete_task.php`
- **Method**: POST
- **Purpose**: Mark task as completed
- **Parameters**: task_id
- **Response**: JSON with completion details and points calculation

### 3. `pause_resume.php`
- **Method**: POST
- **Purpose**: Pause or resume a task
- **Parameters**: task_id, action (pause/resume)
- **Response**: JSON with updated task status

### 4. `delete_record.php`
- **Method**: GET/POST
- **Purpose**: Delete a task record
- **Parameters**: id
- **Response**: Redirect or JSON response

### 5. `export.php`
- **Method**: GET
- **Purpose**: Export filtered data to CSV
- **Parameters**: Filter parameters from records page
- **Response**: CSV file download

## 🔧 Configuration

### Database Configuration (`config/db.php`)
```php
<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'teamhub';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to Pakistan time (UTC+5)
mysqli_query($conn, "SET time_zone = '+05:00'");
?>
```

### Security Features
- **Password hashing** using PHP's password_hash()
- **Session management** with verification includes
- **Role-based access control**
- **SQL injection prevention** with prepared statements
- **XSS prevention** with htmlspecialchars()

## 🎨 User Interface

### Admin Dashboard Features
- Task assignment form with dynamic work selection
- Real-time success/error messages
- Sound notifications for task assignments
- Multi-work selection capability

### Employee Dashboard Features
- Personal statistics display
- Interactive charts for performance tracking
- Task list with completion functionality
- Real-time pause/resume status updates

### Records Page Features
- Advanced filtering system
- Real-time countdown timers
- Pause/resume controls
- Pagination and export functionality
- Toast notifications

## 📊 Points System

### Calculation Logic
- **Allotted Points** = Allotted Time (minutes) / 10
- **Gained Points** = Allotted Points + (Time Saved * 0.1)
- **Time Saved** = Allotted Time - Actual Time Used
- **Bonus Points** = Manually added by admin

### Performance Metrics
- **Efficiency Ratio**: Gained Points / Allotted Points
- **Completion Rate**: Completed Tasks / Total Tasks
- **Average Time**: Total Time Used / Completed Tasks

## 🔄 Real-Time Communication

### localStorage Events
1. **newTaskAssigned**: Notifies employees of new tasks
2. **taskCompleted**: Notifies admin of task completions
3. **taskPauseResume**: Notifies employees of pause/resume actions

### Sound Notifications
- **task_assigned.mp3**: Admin task assignment
- **new_task.mp3**: Employee new task notification
- **task_completed.mp3**: Task completion notification

## 🚀 Installation and Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL/MariaDB database
- Apache web server
- Modern web browser with JavaScript enabled

### Setup Steps
1. **Database Setup**:
   ```bash
   mysql -u root -p teamhub < DB\ Backup/teamhub.sql
   ```

2. **Configuration**:
   - Update `config/db.php` with your database credentials
   - Set proper file permissions

3. **Audio Files**:
   - Place required MP3 files in `assets/sounds/`
   - Ensure browser autoplay policies are configured

4. **Testing**:
   - Test login functionality
   - Verify real-time features work
   - Check all API endpoints

### Default Users
| Username | Role | Default Password |
|----------|------|------------------|
| admin | Admin | Admin@123 |
| employee | Employee | Employee@123 |
| employee2 | Employee | Employee@123 |

## 🐛 Troubleshooting

### Common Issues
1. **Audio not playing**: Check browser autoplay policies
2. **Real-time updates not working**: Verify localStorage is enabled
3. **Database connection errors**: Check config/db.php settings
4. **Permission errors**: Verify file and folder permissions

### Debugging Tips
- Check browser console for JavaScript errors
- Enable PHP error reporting in development
- Verify database table structure matches expectations
- Test API endpoints directly using curl or Postman

## 📈 Performance Optimization

### Database Optimization
- Proper indexing on frequently queried columns
- Regular database maintenance
- Efficient query design with prepared statements

### Frontend Optimization
- Minified CSS and JavaScript
- Efficient DOM manipulation
- Proper event delegation
- Lazy loading where appropriate

## 🔒 Security Considerations

### Implemented Security Measures
- Prepared statements for all database queries
- Password hashing with bcrypt
- Session validation on every page
- Role-based access control
- Input validation and sanitization

### Recommended Enhancements
- HTTPS implementation
- CSRF protection tokens
- Rate limiting on API endpoints
- Regular security audits

## 📝 Future Enhancements

### Planned Features
- Mobile app version
- Push notifications
- Advanced reporting dashboard
- Integration with external project management tools
- Time tracking with screenshots
- Team collaboration features
- Advanced analytics and forecasting

### Technical Improvements
- Migration to modern framework (Laravel/React)
- RESTful API redesign
- Database optimization
- Caching implementation
- Load balancing for scalability

## 📞 Support and Maintenance

### Documentation
- This comprehensive documentation
- Code comments throughout the system
- API documentation
- User guides for admin and employees

### Maintenance Procedures
- Regular database backups
- Software updates and security patches
- Performance monitoring
- User training and support

---

*This documentation covers all aspects of the TeamHub project as of August 2025. For the most current information, refer to the actual codebase and recent commit history.*
