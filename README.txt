ZAYNA CREATION - Setup
1. Copy this folder to C:\xampp\htdocs\zaynaprojetfinale\
2. Start Apache + MySQL in XAMPP.
3. phpMyAdmin -> database "zayna" -> SQL tab -> paste setup_users.sql -> Run.
4. Open http://localhost/zaynaprojetfinale/pagedegarde.html
5. Admin panel: log in with admin@zayna.com / password, then open /admin.php
   (log in with admin account on login.html; you are redirected automatically).
6. Change the admin password: create genhash.php with
   <?php echo password_hash('YourNewPassword', PASSWORD_BCRYPT); ?>
   open it, copy the hash, run in SQL:
   UPDATE users SET password_hash='HASH' WHERE email='admin@zayna.com';
   then DELETE genhash.php.
