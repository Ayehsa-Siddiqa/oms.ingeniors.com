# INGENIORS OMS - Subdomain Deployment (oms.ingeniors.com)

1. Create subdomain `oms` -> document root e.g. public_html/oms (or /oms.ingeniors.com)
2. Upload the CONTENTS of this zip into that document root (index.php and .htaccess must sit directly in it)
3. Create MySQL database + user, import database/schema.sql via phpMyAdmin
   (remove the first two lines `CREATE DATABASE...` / `USE oms;` if your host does not allow them; select your DB first instead)
4. Edit app/config/database.php with real DB name / user / password
5. Enable SSL (AutoSSL / Let's Encrypt) for oms.ingeniors.com
6. Open https://oms.ingeniors.com/login
7. Make assets/uploads and storage/logs writable (755, or 775 if uploads fail)
