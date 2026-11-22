# Cut & Schedule

A system for our barber shop where clients can book appointments and the owner and staff can manage them.

## Frameworks / Libraries

- PHP 8 (using XAMPP)
- Composer
- MySQL

## How to run it

1. Install XAMPP and Composer. When Composer asks where PHP is, pick `C:\xampp\php\php.exe`.
2. Put the project folder inside `C:\xampp\htdocs`.
3. Open a terminal in the project folder and type:

   ```
   composer install
   ```

4. Open XAMPP and start Apache and MySQL.
5. Go to `http://localhost/barber/setup.php` in your browser to make the database. You can also just import `database/cut_schedule.sql` in phpMyAdmin.
6. If your MySQL has a password, change it in `config/config.php`.

If you add a new file in the `classes` folder, run `composer dump-autoload`.
