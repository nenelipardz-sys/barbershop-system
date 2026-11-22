# Cut & Schedule

A system for our barber shop where clients can book appointments and the owner and staff can manage them.

## Frameworks / Libraries

- CodeIgniter 4 (PHP framework)
- PHP 8 (using XAMPP)
- Composer
- MySQL

## How to run it

1. Install XAMPP and Composer. When Composer asks where PHP is, pick `C:\xampp\php\php.exe`.
2. In `C:\xampp\php\php.ini`, remove the `;` in front of `extension=intl`, then restart Apache.
3. Put the project folder inside `C:\xampp\htdocs`.
4. Open a terminal in the project folder and type:

   ```
   composer install
   ```

5. Open XAMPP and start Apache and MySQL.
6. Go to `http://localhost/barber/setup` in your browser to make the database. You can also just import `database/cut_schedule.sql` in phpMyAdmin.
7. If your MySQL has a password, change it in `app/Config/Database.php`.

## Where things are

- `app/Controllers` - the pages (owner, staff, client, api)
- `app/Models` - the classes like User, Appointment, Service
- `app/Views` - the HTML of each page
- `app/Config/Routes.php` - the list of page links
- `public` - css, js, images and uploaded photos
