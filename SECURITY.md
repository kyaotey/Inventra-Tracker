# Security Configuration Guide

## Production Security Checklist

### 1. Database Security
- [ ] Change default MySQL root password
- [ ] Create a dedicated database user with minimal privileges
- [ ] Update `includes/db.php` with production credentials
- [ ] Enable MySQL SSL/TLS connections
- [ ] Regularly backup database

### 2. Server Security
- [ ] Enable HTTPS/SSL certificate
- [ ] Update `.htaccess` to force HTTPS (uncomment lines)
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Configure firewall rules
- [ ] Keep server software updated

### 3. Application Security
- [ ] Set `display_errors = Off` in php.ini
- [ ] Enable error logging to file
- [ ] Set secure session configuration
- [ ] Update admin password from default
- [ ] Remove or secure `create_admin.php` file

### 4. File Security
- [ ] Set uploads directory permissions to 755
- [ ] Ensure uploads directory is not executable
- [ ] Regularly scan for malicious files
- [ ] Monitor security.log file

### 5. Access Control
- [ ] Implement IP whitelisting for admin access
- [ ] Set up two-factor authentication (recommended)
- [ ] Regular security audits
- [ ] Monitor failed login attempts

## Security Features Implemented

### Authentication & Authorization
- ✅ Secure password hashing (bcrypt)
- ✅ Session management with regeneration
- ✅ CSRF protection on all forms
- ✅ Rate limiting for login/registration
- ✅ Input sanitization and validation
- ✅ SQL injection prevention (prepared statements)

### File Upload Security
- ✅ File type validation
- ✅ File size limits
- ✅ MIME type checking
- ✅ Secure file naming
- ✅ Upload directory protection

### Session Security
- ✅ HttpOnly cookies
- ✅ Secure session configuration
- ✅ Session regeneration on login
- ✅ Session timeout handling

### Headers & Protection
- ✅ XSS protection headers
- ✅ Content Security Policy
- ✅ Frame options (clickjacking protection)
- ✅ Content type sniffing protection
- ✅ Referrer policy

### Logging & Monitoring
- ✅ Security event logging
- ✅ Failed login attempt tracking
- ✅ Registration monitoring
- ✅ Error logging (not displayed to users)

## Default Admin Credentials
- **Email**: admin@example.com
- **Password**: admin123

**⚠️ IMPORTANT**: Change these credentials immediately in production!

## File Permissions
```bash
# Directories
chmod 755 uploads/
chmod 755 includes/

# Files
chmod 644 *.php
chmod 644 .htaccess
chmod 600 security.log
```

## Monitoring
- Check `security.log` regularly for suspicious activity
- Monitor failed login attempts
- Review error logs
- Set up automated security scanning

## Backup Strategy
- Regular database backups
- File system backups
- Test restore procedures
- Off-site backup storage

## Emergency Procedures
1. **Security Breach**: Immediately change all passwords
2. **Database Compromise**: Restore from clean backup
3. **File Upload Attack**: Scan and clean uploads directory
4. **DDoS Attack**: Enable rate limiting and monitoring

## Contact
For security issues, contact your system administrator immediately. 