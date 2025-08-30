# Halarnati - Modern Sharing Platform

A feature-rich web application for sharing text, code, and files with advanced social features.

## Features

### Security
- CSRF Protection
- Rate Limiting
- Input Validation
- Secure Authentication

### User Experience
- Modal-based Login/Registration
- Password Strength Meter
- Theme Toggle (Light/Dark)
- Responsive Design

### Social Features
- Bookmarking System
- Notification System
- User Following
- Comments with Likes
- Social Media Integration

### Content Management
- Text, Code, and File Sharing
- Categories
- Search Functionality
- Entry Management

### Administrative Features
- User Management
- Entry Management
- Category Management
- Activity Logs
- Settings Management

### API
- RESTful API for entries, users, and categories
- JSON responses
- Authentication required for protected endpoints

### Performance
- Caching System
- Database Indexes
- Optimized Queries

## Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Libraries**: 
  - Font Awesome
  - Prism.js (Code highlighting)
  - Bootstrap

## Installation

1. Clone the repository
2. Set up a web server with PHP and MySQL
3. Create a MySQL database
4. Update `config.php` with your database credentials
5. Run `complete_database_setup.php` to set up the database schema
6. Ensure the `cache` directory is writable
7. Ensure the `uploads` directory is writable

## Directory Structure

```
htdocs/
├── api/                 # API endpoints
├── assets/              # CSS, JS, and image files
├── cache/               # Cache files
├── guru/                # Admin panel
├── includes/            # PHP classes and libraries
├── uploads/             # User uploaded files
├── ...                  # Main application files
```

## API Endpoints

### Entries
- `GET /api/entries` - List entries
- `GET /api/entries/{id}` - Get specific entry
- `POST /api/entries` - Create entry
- `PUT /api/entries/{id}` - Update entry
- `DELETE /api/entries/{id}` - Delete entry

### Users
- `GET /api/users` - List users (admin only)
- `GET /api/users/{id}` - Get specific user
- `POST /api/users` - Create user (registration)
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Categories
- `GET /api/categories` - List categories
- `GET /api/categories/{id}` - Get specific category
- `POST /api/categories` - Create category (admin only)
- `PUT /api/categories/{id}` - Update category (admin only)
- `DELETE /api/categories/{id}` - Delete category (admin only)

## Security Features

- CSRF tokens on all forms
- Rate limiting on authentication attempts
- Password hashing with bcrypt
- Input sanitization
- SQL injection prevention with prepared statements

## Customization

### Theme System
Users can toggle between light and dark themes. The preference is saved in both cookies and the database.

### Social Media Integration
Users can add their social media profiles in their account settings.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Authors

- Zahid Hasan Tonmoy - Initial work

## Acknowledgments

- Bootstrap for the frontend framework
- Font Awesome for icons
- Prism.js for code highlighting