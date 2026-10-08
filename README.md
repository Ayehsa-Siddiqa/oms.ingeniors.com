# Company Operations Management System (OMS)

Core PHP 8 MVC application for an engineering firm operations workflow. It is designed to run on XAMPP with Apache and MySQL.

## Stack

- PHP 8, Core PHP, OOP MVC
- PDO with prepared statements
- MySQL InnoDB UTF8MB4
- Bootstrap 5, Bootstrap Icons, JavaScript
- Apache on XAMPP

## Installation on XAMPP

1. Copy the `oms` folder into:

   `C:\xampp\htdocs\oms`

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open phpMyAdmin and import:

   `database/schema.sql`

4. Confirm database settings in:

   `app/config/database.php`

   Default XAMPP settings are already configured:

   - host: `127.0.0.1`
   - database: `oms`
   - username: `root`
   - password: empty

5. Open the app:

   `http://localhost/oms`

## Sample Users

All seeded users use this password:

`password`

| Role | Email |
| --- | --- |
| Super Admin | superadmin@oms.test |
| Admin | admin@oms.test |
| Employee | employee@oms.test |

## Included Modules

- Secure login, remember me, forgot password token creation, logout, automatic session timeout
- Role-based access rules for Super Admin, Admin, and Employee
- Dashboard with summary cards, status breakdown, recent activity, recent projects
- Projects CRUD with priority, status, progress, start date, deadline, notes
- Employees CRUD
- Departments CRUD
- Requirements CRUD
- Employee fines CRUD
- Company documents CRUD with upload validation
- Reports page with date filtering and print support
- Settings page for company information
- Profile page with password change
- Activity logging for authentication and data changes
- CSRF protection, XSS escaping, prepared SQL statements, upload extension and size validation

## Folder Structure

```text
oms/
app/
  config/
  controllers/
  core/
  helpers/
  middleware/
  models/
  views/
assets/
  css/
  js/
  images/
  uploads/
database/
public/
routes/
storage/
  logs/
```

## Notes

- Apache `mod_rewrite` must be enabled.
- Keep `assets/uploads` writable for document uploads.
- For production, configure HTTPS and change cookie `secure` to `true` in `public/index.php`.
- The forgot password feature creates a reset token record. Connect SMTP before using it to email reset links.
