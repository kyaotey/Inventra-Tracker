# Inventra

**Name Meaning:**

"Inven" comes from the Latin root *invenire*, meaning "to find" or "to discover."

"tra" is a soft, system-oriented suffix, evoking terms like "mantra," "spectra," and "entra" — suggesting a process, flow, or platform.

A secure, modern web application for tracking lost and found items, missing persons, and pets with user authentication and admin management.

## 🆕 **Latest Updates (v2.0)**

### ✨ **New Features**
- 📸 **Multiple Media Upload**: Support for multiple photos and videos per report
- 🎥 **Video Support**: Upload and display videos (MP4, AVI, MOV, WMV, FLV, WebM, MKV)
- 🖱️ **Drag & Drop**: Intuitive drag and drop file upload interface
- 🖼️ **Media Gallery**: Beautiful gallery view with full-screen preview
- 🔍 **Enhanced Search**: Improved search with media preview
- 📱 **Mobile Optimized**: Better mobile experience with touch-friendly interface

### 🛡️ **Security Enhancements**
- 🔒 **Comprehensive Security Audit**: All critical vulnerabilities fixed
- 🛡️ **Advanced CSRF Protection**: Enhanced protection across all forms
- 📊 **Security Logging**: Detailed security event tracking
- 🔐 **Session Security**: Improved session management and timeout handling
- 🚫 **Rate Limiting**: Enhanced protection against brute force attacks

### 🏗️ **Architecture Improvements**
- 🏛️ **MVC Architecture**: Proper separation of concerns
- 🔧 **Dependency Injection**: Improved code maintainability
- 📝 **Comprehensive Documentation**: Detailed guides and architecture docs
- 🧪 **Testing Framework**: Automated testing setup
- 📊 **Performance Optimization**: Database query optimization and caching

## Features

### User Features
- 🔐 Secure user registration and login
- 📝 Report lost or found items, persons, or pets
- 📸 **Multiple photo and video uploads** (NEW!)
- 🎥 **Video support** for better identification (NEW!)
- 🖱️ **Drag & drop file uploads** (NEW!)
- 🖼️ **Media gallery with full-screen preview** (NEW!)
- 🔍 Search and browse by category (Items, Persons, Pets)
- 📱 Responsive design for all devices
- 🎨 Modern glassmorphism UI
- 🏷️ Category-specific forms and fields

### Report Categories
- 📦 **Items**: Lost or found objects (phones, keys, jewelry, etc.)
- 👤 **Persons**: Missing persons with age, gender, and physical description
- 🐾 **Pets**: Lost or found pets with breed, color, size, and microchip info

### Admin Features
- 🛡️ Secure admin dashboard
- 📊 View all reports and statistics by category
- ✅ Mark reports as returned
- 🗑️ Delete inappropriate reports
- 🔍 Advanced search and filtering
- 📈 Activity monitoring
- 🖼️ **Media management** for all reports (NEW!)

### Security Features
- 🔒 CSRF protection on all forms
- 🚫 Rate limiting for login/registration
- 🛡️ SQL injection prevention
- 🔐 Secure password hashing (bcrypt)
- 📝 Input sanitization and validation
- 🖼️ **Enhanced file upload validation** (NEW!)
- 📊 **Comprehensive security logging** (NEW!)
- 🍪 Secure session management
- 🛡️ **Advanced security headers** (NEW!)

## Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd Inventra-Tracker
   ```

2. **Set up the database**
   - Create a MySQL database named `missing_items_db`
   - Import the SQL file: `sql/missing_items_db.sql`

3. **Update existing database (if upgrading)**
   - Run `update_database.php` to add new features to existing installations
   - This will create the new `report_media` table for multiple file uploads

4. **Configure database connection**
   - Edit `config.php` with your database credentials
   - Set environment to 'development' or 'production'

5. **Set up web server**
   - Point your web server to the project directory
   - Ensure PHP has write permissions to the `uploads/` directory

6. **Start the application**
   ```bash
   php -S localhost:8000
   ```

7. **Access the application**
   - Main site: http://localhost:8000
   - Admin panel: Login with admin@example.com / admin123

## 🆕 **New Media Features**

### Multiple File Upload
- **Supported Formats**: JPEG, PNG, GIF, WebP, MP4, AVI, MOV, WMV, FLV, WebM, MKV
- **File Size**: Up to 10MB per file
- **Multiple Files**: Upload several files at once
- **Drag & Drop**: Intuitive file upload interface
- **Preview**: Real-time preview before upload
- **Remove Files**: Remove individual files before submission

### Media Gallery
- **Responsive Gallery**: Beautiful grid layout for all media
- **Full-Screen View**: Click any media for full-screen preview
- **Video Controls**: Built-in video player controls
- **Primary Media**: First uploaded file marked as primary
- **Backward Compatibility**: Existing single photos continue to work

## Report Types

### Items
- Standard item reports with description, location, and contact info
- **Multiple photo and video upload support** (NEW!)
- Search by keywords, location, and status

### Persons
- Missing person reports with detailed information:
  - Age and gender
  - Physical description (height, hair color, eye color, clothing)
  - Last seen date and time
  - Contact information for reporting
- **Multiple photo and video upload for identification** (NEW!)

### Pets
- Lost or found pet reports with comprehensive details:
  - Pet type (dog, cat, bird, rabbit, etc.)
  - Breed and color
  - Size classification (small, medium, large)
  - Detailed description including markings and collar info
  - Microchip information
  - Last seen date and time
- **Multiple photo and video upload for identification** (NEW!)

## Security Configuration

### Production Deployment
Before deploying to production, please review and implement the security measures in `SECURITY.md`:

- [ ] Change default admin credentials
- [ ] Enable HTTPS
- [ ] Configure proper file permissions
- [ ] Set up database security
- [ ] Enable error logging
- [ ] Configure firewall rules
- [ ] **Review security audit report** (NEW!)

### Default Admin Account
- **Email**: admin@example.com
- **Password**: admin123

**⚠️ CRITICAL**: Change these credentials immediately in production!

## Project Structure (Updated)

- `index.php` — Main dashboard/homepage with category filtering and **media gallery** (NEW!)
- `login.php`, `register.php`, `logout.php` — Auth pages
- `edit_profile.php` — User profile editing
- `report.php` — Report submission with category-specific forms and **multiple media upload** (NEW!)
- `report_item.php`, `report_person.php`, `report_pet.php` — Category-specific report forms with **media support** (NEW!)
- `view.php` — Report details with category-specific display and **media gallery** (NEW!)
- `search_items.php` — AJAX search/filter endpoint with **media preview** (NEW!)
- `update_database.php` — Database migration script for **new media features** (NEW!)
- `admin/` — Admin dashboard and authentication
    - `dashboard.php`, `auth.php`
- `includes/` — Shared PHP includes (DB, security, auth)
    - `db.php`, `auth.php`, `security.php` with **media upload functions** (NEW!)
- `uploads/` — User-uploaded files
    - `profile_photos/` — User profile images
    - **Media files for reports** (NEW!)
- `sql/` — Database schema
    - `missing_items_db.sql` (updated with **media support**)
    - `complete_database_schema.sql` (NEW!)
- `SECURITY.md`, `README.md`, `.htaccess` — Docs and config
- `MULTIPLE_MEDIA_FEATURE.md` — **New media feature documentation** (NEW!)
- `PROJECT_AUDIT_REPORT.md` — **Security audit results** (NEW!)
- `SYSTEM_ARCHITECTURE.md` — **Architecture documentation** (NEW!)
- `PROJECT_TIMELINE.md` — **Development roadmap** (NEW!)
- `DEPLOYMENT_CHECKLIST.md` — **Deployment guide** (NEW!)
- `AGILE_METHODOLOGY.md` — **Development methodology** (NEW!)
- `create_admin.php`, `test_db.php` — Setup/testing scripts

## 🆕 **New Database Schema**

### Reports Table (Enhanced)
```sql
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    category ENUM('item', 'person', 'pet') NOT NULL DEFAULT 'item',
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact_info TEXT,
    photo VARCHAR(255) DEFAULT NULL, -- Legacy support
    status ENUM('pending', 'returned') DEFAULT 'pending',
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### New: Report Media Table
```sql
CREATE TABLE report_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_file_type (file_type)
);
```

## Security Features

### Authentication & Authorization
- Secure password hashing using bcrypt
- Session management with regeneration
- CSRF protection on all forms
- Rate limiting for login/registration attempts
- Input sanitization and validation

### File Upload Security (Enhanced)
- **Multiple file type validation** (images and videos)
- **Enhanced file size limits** (10MB per file)
- **Comprehensive MIME type checking**
- **Secure file naming with unique identifiers**
- **Upload directory protection**
- **Virus scanning integration** (NEW!)

### Session Security
- HttpOnly cookies
- Secure session configuration
- Session regeneration on login
- Session timeout handling

### Headers & Protection
- XSS protection headers
- Content Security Policy
- Frame options (clickjacking protection)
- Content type sniffing protection
- Referrer policy

### Logging & Monitoring (Enhanced)
- **Comprehensive security event logging**
- **Failed login attempt tracking**
- **Registration monitoring**
- **Error logging (not displayed to users)**
- **Media upload activity tracking** (NEW!)

## API Endpoints

### Public Endpoints
- `GET /` - Main page with category filtering and **media gallery** (NEW!)
- `GET /login.php` - Login form
- `GET /register.php` - Registration form
- `GET /view.php?id=X` - View report details with **media gallery** (NEW!)

### Protected Endpoints
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `POST /report.php` - Submit report with **multiple media upload** (NEW!)
- `POST /report_item.php`, `POST /report_person.php`, `POST /report_pet.php` - Category-specific reports (NEW!)
- `GET /admin/dashboard.php` - Admin dashboard (requires admin)

## 🆕 **New Documentation**

### Technical Documentation
- `MULTIPLE_MEDIA_FEATURE.md` - Complete guide to media upload features
- `SYSTEM_ARCHITECTURE.md` - Detailed system architecture and development framework
- `PROJECT_TIMELINE.md` - 32-week development roadmap with sprints
- `AGILE_METHODOLOGY.md` - Scrum framework implementation guide

### Security & Deployment
- `SECURITY.md` - Comprehensive security configuration guide
- `PROJECT_AUDIT_REPORT.md` - Security audit results and fixes
- `DEPLOYMENT_CHECKLIST.md` - Step-by-step deployment guide
- `DEPLOYMENT_GUIDE.md` - Detailed deployment instructions

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Security

If you discover a security vulnerability, please:
1. **Do not** create a public issue
2. Contact the maintainers privately
3. Allow time for the issue to be addressed

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Check the documentation
- Review the security guide
- Contact the development team

---

**⚠️ Security Notice**: This application includes comprehensive security features, but proper configuration and maintenance are essential for production use. Always follow the security guidelines in `SECURITY.md`.

**🎉 New Features**: Version 2.0 introduces multiple media uploads, enhanced security, improved architecture, and comprehensive documentation. Check the new documentation files for detailed information about all improvements. 