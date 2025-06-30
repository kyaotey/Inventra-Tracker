# Deployment Guide for Missing Items Tracker

## Free Hosting Options

### 1. InfinityFree (Recommended)
**URL**: https://infinityfree.net/

**Steps:**
1. **Sign Up**: Create a free account at InfinityFree
2. **Create Hosting Account**: 
   - Choose a subdomain (e.g., `yourproject.infinityfreeapp.com`)
   - Or connect your own domain
3. **Access Control Panel**: Use the File Manager or FTP
4. **Upload Files**: Upload all project files to the `htdocs` folder
5. **Create Database**:
   - Go to MySQL Databases in control panel
   - Create a new database
   - Note down: database name, username, password, and host
6. **Import Database**: Use phpMyAdmin to import `sql/missing_items_db.sql`
7. **Update Configuration**: Edit `includes/db.php` with your database credentials

### 2. 000webhost (Alternative)
**URL**: https://www.000webhost.com/

**Steps:**
1. Sign up for free account
2. Create a new website
3. Upload files via File Manager
4. Create MySQL database
5. Import SQL file
6. Update database configuration

## Pre-Deployment Checklist

### ✅ Required Changes

1. **Database Configuration** (`includes/db.php`):
   ```php
   $host = 'your_host'; // Usually 'localhost'
   $user = 'your_username';
   $pass = 'your_password';
   $dbname = 'your_database_name';
   ```

2. **Error Reporting**: Already updated to hide errors in production

3. **File Permissions**: Ensure `uploads/` directory is writable (755 or 777)

### ✅ Files to Upload
- All PHP files
- CSS/JS files
- Images and assets
- `sql/missing_items_db.sql` (for database import)

### ✅ Database Setup
1. Create MySQL database in hosting control panel
2. Import `missing_items_db.sql` using phpMyAdmin
3. Verify tables are created correctly

## Post-Deployment Steps

### 1. Test Your Application
- Visit your website URL
- Test registration and login
- Test item reporting functionality
- Test file uploads

### 2. Security Considerations
- Change default admin password (currently: `admin123`)
- Update admin email in database
- Ensure `security.log` is writable
- Consider enabling HTTPS if available

### 3. Performance Optimization
- Enable caching if available
- Optimize images in `uploads/` folder
- Consider using CDN for Bootstrap and Font Awesome

## Troubleshooting

### Common Issues:

1. **Database Connection Error**:
   - Verify database credentials in `includes/db.php`
   - Check if database exists and is accessible
   - Ensure MySQL service is running

2. **File Upload Issues**:
   - Check `uploads/` directory permissions (755 or 777)
   - Verify PHP upload settings in hosting control panel
   - Check file size limits

3. **Page Not Found (404)**:
   - Ensure files are uploaded to correct directory (`htdocs` or `public_html`)
   - Check if `.htaccess` file is present (if using URL rewriting)

4. **White Screen**:
   - Check error logs in hosting control panel
   - Temporarily enable error display for debugging
   - Verify PHP version compatibility

## Hosting Provider Specific Notes

### InfinityFree
- Database host: Usually `localhost`
- PHP version: 8.1
- File upload limit: 10MB
- Database size limit: 1GB

### 000webhost
- Database host: Usually `localhost`
- PHP version: 8.0
- File upload limit: 2MB
- Database size limit: 1GB

## Support Resources

- **InfinityFree Support**: https://infinityfree.net/support/
- **000webhost Support**: https://www.000webhost.com/support
- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/

## Backup Strategy

1. **Regular Database Backups**: Export database monthly
2. **File Backups**: Download project files regularly
3. **Configuration Backup**: Keep local copy of database config

---

**Note**: Free hosting services may have limitations and occasional downtime. For production use, consider upgrading to a paid hosting plan for better reliability and support. 