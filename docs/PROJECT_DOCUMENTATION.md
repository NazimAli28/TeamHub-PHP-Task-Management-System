please# TeamHub Project Documentation

## Project Overview
TeamHub is a comprehensive task management system designed specifically for managing designer workflows, tracking task assignments, and providing real-time collaboration between administrators and employees. The system features role-based access control, real-time updates, advanced task tracking, and performance analytics.

## Project Structure
```
teamhub/
├── 📁 admin/                   # Admin panel
│   ├── 📁 api/                # Admin-specific APIs
│   ├── 📁 css/                # Admin stylesheets
│   ├── 📁 includes/           # Admin includes
│   ├── 📁 js/                 # Admin JavaScript
│   └── 📁 pages/              # Admin pages
├── 📁 api/                    # API endpoints and data processing
├── 📁 assets/                 # Static assets (images, sounds)
├── 📁 backup/                 # Backup files
├── 📁 config/                 # Configuration files
├── 📁 css/                    # Global stylesheets
├── 📁 employee/               # Employee panel
│   ├── 📁 css/                # Employee stylesheets
│   ├── 📁 includes/           # Employee includes
│   ├── 📁 js/                 # Employee JavaScript
│   └── 📁 pages/              # Employee pages
├── 📁 includes/               # PHP includes and components
├── 📁 js/                     # Global JavaScript files
├── 📁 pages/                  # Main page files
├── 📁 tests/                  # Test files
├── .htaccess                  # Apache configuration
├── index.php                  # Login page
└── README.md                  # Project overview
```

## Key Features

### Core Functionality
1. **User Authentication**: Secure login system with role-based access control for admins and employees.
2. **Task Management**: Complete workflow from task assignment to completion with real-time tracking.
3. **Points System**: Automated points calculation based on time allocation and performance metrics.
4. **Real-Time Updates**: Instant notifications and updates across all user sessions.

### Advanced Features
5. **Bonus Points System**: Admin can award bonus points to employees for exceptional work with detailed logging.
6. **Pause/Resume Functionality**: Tasks can be paused and resumed with accurate time tracking.
7. **Employee Analytics**: Comprehensive overview and detail pages for employee performance tracking.
8. **Advanced Filtering**: Multi-criteria filtering for tasks, employees, and date ranges.
9. **Data Export**: Export functionality for reports and data analysis.
10. **Sound Notifications**: Audio feedback for important events and notifications.

## Database Structure
The database includes the following key tables:
- `users`: User accounts with role-based permissions
- `designer_points`: Main tasks table with time tracking and points
- `bonus_logs`: Bonus points history and reasons
- `pause_logs`: Pause/resume events tracking
- `auto_points_plan`: Points configuration for different work types

## Real-Time Features
- **AJAX-based Operations**: Seamless task assignments and completions
- **LocalStorage Communication**: Cross-page notifications and updates
- **Live Countdown Timers**: Dynamic timers with pause/resume support
- **Sound Notifications**: Audio alerts for task events
- **Auto-refresh**: Real-time data synchronization

## User Roles and Permissions

### Admin Role
- Task assignment and management
- Employee performance monitoring
- Bonus points management
- System-wide analytics and reporting
- Data export capabilities

### Employee Role
- View assigned tasks with countdown timers
- Mark tasks as completed
- Access personal performance dashboard
- Receive real-time notifications

## Technical Implementation
- **Backend**: PHP 8.0+ with MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS + Chart.js)
- **Database**: MySQL with proper indexing and relationships
- **Real-time Communication**: localStorage-based event system
- **Security**: Prepared statements, password hashing, session management

## Setup Instructions
1. **Prerequisites**: XAMPP/WAMP with PHP 8.0+, MySQL/MariaDB
2. **Database Setup**: Import `database/teamhub.sql`
3. **Configuration**: Update database credentials in `config/db.php`
4. **Audio Files**: Place notification sounds in `assets/sounds/`
5. **Permissions**: Ensure proper file and folder permissions
6. **Testing**: Verify all features work across different browsers

## New Features (Latest Updates)

### Bonus Points System
- Admin can add bonus points to completed tasks
- Detailed logging with reasons and timestamps
- Bonus points included in performance calculations
- Accessible via bonus management page

### Pause/Resume Functionality
- Tasks can be paused and resumed by admins
- Accurate time tracking with pause duration calculation
- Real-time countdown timer adjustments
- Pause logs for audit trail

### Employee Overview & Analytics
- Comprehensive employee performance dashboard
- Individual employee detail pages
- Advanced filtering and sorting capabilities
- Export functionality for employee data

### Enhanced User Experience
- Improved responsive design
- Better navigation and user interface
- Advanced search and filtering options
- Real-time notifications and updates

## API Endpoints
- `add_record.php`: Create new task assignments
- `complete_task.php`: Mark tasks as completed
- `pause_resume.php`: Pause or resume tasks
- `add_bonus_points.php`: Add bonus points to tasks
- `export.php`: Export filtered data
- `get_task_status.php`: Get real-time task information

## Troubleshooting
- **Audio Issues**: Check browser autoplay policies and file paths
- **Real-time Updates**: Verify localStorage is enabled
- **Database Connection**: Check credentials and server status
- **Permission Errors**: Verify file and folder permissions
- **AJAX Failures**: Check network connectivity and API endpoints

## Performance Optimization
- Database indexing for fast queries
- Efficient AJAX calls and caching
- Minified CSS and JavaScript
- Proper error handling and logging

## Security Features
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control

## Future Enhancements
- Mobile application development
- Advanced reporting and analytics
- Integration with external tools
- Push notification system
- Time tracking with screenshots

## Documentation Resources
- **Complete Documentation**: `TEAMHUB_COMPLETE_DOCUMENTATION.md` - Detailed technical documentation
- **Quick Start Guide**: `QUICK_START_GUIDE.md` - Fast setup instructions
- **Database Schema**: Detailed table structures and relationships
- **API Documentation**: Complete endpoint specifications

## Conclusion
TeamHub represents a modern, feature-rich task management solution specifically designed for creative workflows. With its comprehensive feature set including bonus points, pause/resume functionality, and advanced analytics, it provides a complete solution for managing designer teams and tracking project progress effectively.

For detailed technical information, refer to `TEAMHUB_COMPLETE_DOCUMENTATION.md`.
