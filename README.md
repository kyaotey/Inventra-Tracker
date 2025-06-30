# Inventra

**Name Meaning:**

"Inven" comes from the Latin root *invenire*, meaning "to find" or "to discover."

"tra" is a soft, system-oriented suffix, evoking terms like "mantra," "spectra," and "entra" — suggesting a process, flow, or platform.

A secure, modern web application for tracking lost and found items, missing persons, and pets with user authentication and admin management.

## Features

### User Features
- 🔐 Secure user registration and login
- 📝 Report lost or found items, persons, or pets
- 📸 Upload photos with reports
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

### Security Features
- 🔒 CSRF protection on all forms
- 🚫 Rate limiting for login/registration
- 🛡️ SQL injection prevention
- 🔐 Secure password hashing (bcrypt)
- 📝 Input sanitization and validation
- 🖼️ Secure file upload validation
- 📊 Security event logging
- 🍪 Secure session management

## Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd missing-items-tracker
   ```

2. **Set up the database**
   - Create a MySQL database named `missing_items_db`
   - Import the SQL file: `sql/missing_items_db.sql`

3. **Update existing database (if upgrading)**
   - Run `update_database.php` to add category support to existing installations

4. **Configure database connection**
   - Edit `includes/db.php` with your database credentials

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

## Report Types

### Items
- Standard item reports with description, location, and contact info
- Photo upload support
- Search by keywords, location, and status

### Persons
- Missing person reports with detailed information:
  - Age and gender
  - Physical description (height, hair color, eye color, clothing)
  - Last seen date and time
  - Contact information for reporting
- Photo upload for identification

### Pets
- Lost or found pet reports with comprehensive details:
  - Pet type (dog, cat, bird, rabbit, etc.)
  - Breed and color
  - Size classification (small, medium, large)
  - Detailed description including markings and collar info
  - Microchip information
  - Last seen date and time
- Photo upload for identification

## Security Configuration

### Production Deployment
Before deploying to production, please review and implement the security measures in `SECURITY.md`:

- [ ] Change default admin credentials
- [ ] Enable HTTPS
- [ ] Configure proper file permissions
- [ ] Set up database security
- [ ] Enable error logging
- [ ] Configure firewall rules

### Default Admin Account
- **Email**: admin@example.com
- **Password**: admin123

**⚠️ CRITICAL**: Change these credentials immediately in production!

## Project Structure (Updated)

- `index.php` — Main dashboard/homepage with category filtering
- `login.php`, `register.php`, `logout.php` — Auth pages
- `edit_profile.php` — User profile editing
- `report.php` — Report submission with category-specific forms
- `view.php` — Report details with category-specific display
- `search_items.php` — AJAX search/filter endpoint
- `update_database.php` — Database migration script
- `admin/` — Admin dashboard and authentication
    - `dashboard.php`, `auth.php`
- `includes/` — Shared PHP includes (DB, security, auth)
    - `db.php`, `auth.php`, `security.php`
- `uploads/` — User-uploaded files
    - `profile_photos/` — User profile images
    - (report images in root of uploads)
- `sql/` — Database schema
    - `missing_items_db.sql` (updated with category support)
- `SECURITY.md`, `README.md`, `.htaccess` — Docs and config
- `create_admin.php`, `test_db.php` — Setup/testing scripts

## Security Features

### Authentication & Authorization
- Secure password hashing using bcrypt
- Session management with regeneration
- CSRF protection on all forms
- Rate limiting for login/registration attempts
- Input sanitization and validation

### File Upload Security
- File type validation (images only)
- File size limits (5MB max)
- MIME type checking
- Secure file naming
- Upload directory protection

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

### Logging & Monitoring
- Security event logging
- Failed login attempt tracking
- Registration monitoring
- Error logging (not displayed to users)

## API Endpoints

### Public Endpoints
- `GET /` - Main page with category filtering
- `GET /login.php` - Login form
- `GET /register.php` - Registration form
- `GET /view.php?id=X` - View report details

### Protected Endpoints
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `POST /report.php` - Submit report (requires login)
- `GET /admin/dashboard.php` - Admin dashboard (requires admin)

## Database Schema

### Reports Table
```sql
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    category ENUM('item', 'person', 'pet') NOT NULL DEFAULT 'item',
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact_info TEXT,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'returned') DEFAULT 'pending',
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
);
```

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