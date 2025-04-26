# Calyda Management System Documentation

## System Overview
The Calyda Management System is a web-based application built using:
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Development Environment**: XAMPP, VS Code

## System Requirements
1. **Server**: XAMPP (Apache, MySQL)
2. **PHP**: 7.4 or higher
3. **MySQL**: 5.7 or higher
4. **Browser**: Chrome, Firefox, or Edge (latest versions)

## Complete File Structure
```
/calyd
├── admin/                # Admin panel
│   ├── client.php         # Client management
│   ├── dashboard.php      # Admin dashboard
│   ├── dashboard_styles.php # Dashboard styles
│   ├── database_schema.sql # Database schema
│   ├── employee.php       # Employee management
│   ├── enquiry.php        # Enquiry handling
│   ├── get_activities.php # Activity tracking
│   ├── get_dashboard_stats.php # Dashboard statistics
│   ├── logout.php        # Admin logout
│   ├── sidebar.php        # Admin sidebar
│   ├── style.css          # Admin styles
│   ├── task.php           # Task management
│   └── update_session.php  # Session management

├── client/               # Client interface
│   ├── dashboard.php      # Client dashboard
│   ├── dashboard_scripts.php # Dashboard scripts
│   ├── dashboard_styles.php # Dashboard styles
│   ├── enquiries.php      # Client enquiries
│   ├── feedback.php       # Feedback system
│   ├── logout.php         # Client logout
│   ├── profile.php        # Client profile
│   ├── style.css          # Client styles
│   ├── submit_enquiry.php  # Enquiry submission
│   └── tasks.php          # Task management

├── employee/              # Employee interface
│   ├── clients.php        # Client management
│   ├── dashboard.php      # Employee dashboard
│   ├── dashboard_scripts.php # Dashboard scripts
│   ├── dashboard_styles.php # Dashboard styles
│   ├── enquiries.php      # Enquiry handling
│   ├── feedback.php       # Feedback system
│   ├── logout.php         # Employee logout
│   ├── profile.php        # Employee profile
│   ├── read_first_35_lines.py # File processing
│   ├── style.css          # Employee styles
│   ├── submit_enquiry.php  # Enquiry submission
│   ├── tasks.php          # Task management
│   └── uploads/            # File uploads directory

├── auth.php               # Authentication logic
├── config.php             # Database configuration
├── index.html             # Landing page
├── login.php              # Login handler
├── logout.php             # Main logout
├── main.css               # Global styles
├── signin.php             # Login page
├── test.html              # Test page
└── update_session.php      # Session management
```

## Database Schema
Key tables include:
- **users**: Stores user credentials and roles
- **clients**: Client information
- **employees**: Employee details
- **tasks**: Task management
- **enquiries**: Client enquiries

See `admin/database_schema.sql` for complete schema details.

## Setup Instructions
1. **Install XAMPP**
   - Download and install XAMPP from https://www.apachefriends.org
   - Start Apache and MySQL services

2. **Database Setup**
   - Import `database_schema.sql` into phpMyAdmin
   - Update database credentials in `config.php`

3. **Project Setup**
   - Place project files in `htdocs/calyd`
   - Access system via `http://localhost/calyd/signin.php`

## Development Guidelines
1. **Frontend**
   - Use semantic HTML5
   - Follow BEM CSS methodology
   - Use ES6+ JavaScript features

2. **Backend**
   - Follow PSR-12 coding standards
   - Use prepared statements for database queries
   - Implement proper error handling

3. **Security**
   - Use password_hash() for password storage
   - Validate and sanitize all user inputs
   - Implement CSRF protection

## Testing
1. **Unit Testing**
   - Test individual PHP functions
   - Verify database queries

2. **Integration Testing**
   - Test user authentication flow
   - Verify form submissions
   - Test role-based access

3. **Browser Testing**
   - Test on Chrome, Firefox, and Edge
   - Verify mobile responsiveness

## Deployment
1. **Production Server**
   - Use a secure hosting provider
   - Configure HTTPS
   - Set proper file permissions

2. **Database**
   - Export production database
   - Import to production server
   - Update connection details

## Maintenance
1. **Backups**
   - Schedule regular database backups
   - Backup application files

2. **Updates**
   - Keep PHP and MySQL updated
   - Monitor security advisories

## Troubleshooting
Common issues:
- **Connection Errors**: Verify database credentials
- **File Permissions**: Ensure proper file permissions
- **Caching Issues**: Clear browser cache
- **Error Logs**: Check Apache and PHP error logs

## Version Control
- Use Git for version control
- Follow Git Flow branching model
- Maintain proper commit messages

## Contact
For support or questions, contact:
- Email: support@calyda.com
- Phone: +254 700 000 000
