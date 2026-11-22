# Cut & Schedule

A system for our barber shop. Clients can book appointments online, and the owner and staff can manage the bookings, clients, services and sales.

## Frameworks / Libraries

- CodeIgniter 4 (PHP framework)
- Composer
- PHP 8 (using XAMPP)
- MySQL

## What you need first

- XAMPP (it has PHP, Apache and MySQL)
- Composer (download it at https://getcomposer.org/download/)
- Git, if you want to clone with the terminal

## How to set it up after cloning

1. Go to your htdocs folder and clone the project:

   ```
   cd C:\xampp\htdocs
   git clone https://github.com/nenelipardz-sys/barbershop-system.git
   ```

2. Turn on the intl extension. CodeIgniter needs this.
   - Open `C:\xampp\php\php.ini` in Notepad.
   - Find the line `;extension=intl`.
   - Remove the `;` at the start so it says `extension=intl`.
   - Save it.

3. Install Composer. When it asks where PHP is, choose `C:\xampp\php\php.exe`.

4. Open a terminal inside the project folder and type:

   ```
   cd C:\xampp\htdocs\barbershop-system
   composer install
   ```

   This creates the `vendor` folder. The app will not open without it.

5. Open the XAMPP Control Panel and start Apache and MySQL. If Apache was already running, stop it and start it again so it reads the php.ini change.

6. Open `http://localhost/barbershop-system/setup` in your browser and click Install Database. You can also import `database/cut_schedule.sql` in phpMyAdmin instead.

7. Go to `http://localhost/barbershop-system/login` and sign in.

If your MySQL has a password, put it in `app/Config/Database.php`.

### Accounts you can use

| Role   | Username          | Password  |
|--------|-------------------|-----------|
| Owner  | admin1            | admin2026 |
| Staff  | mark / jay / luis | staff123  |
| Client | arizza / paulo    | client123 |

## What is Composer?

Composer is a tool that downloads the PHP stuff your project needs. Instead of copying CodeIgniter into the project by hand, we just list it in `composer.json`, and Composer downloads it for you when you run `composer install`.

That is why the `vendor` folder is not on GitHub. It is big, and anyone can get it back with one command. `composer.lock` makes sure everyone downloads the exact same version (CodeIgniter 4.4.8).

Composer also loads our classes (User, Appointment, Service and so on) automatically, so we don't need to write `require` for every file.

Note: Composer will say CodeIgniter 4.4.8 has security warnings. We kept this version on purpose because it's the last one that works with PHP 8.0, which is what our XAMPP has. The warnings are about parts of CodeIgniter we don't use, and the system only runs on localhost.

## What is CodeIgniter and how it works here

CodeIgniter is a PHP framework. It gives the project a set structure so the code is more organized. It uses MVC, which means each page is split into 3 parts:

- **Model** - the data and the rules. These are our classes in `app/Models` like `User`, `Appointment`, `Service` and `Fee`. They talk to the database.
- **View** - the HTML that you see on the screen. These are in `app/Views`.
- **Controller** - the middle part. It gets the request, uses the models to get or save data, then shows the right view. These are in `app/Controllers`.

This is what happens when you open a page, for example `owner/clients`:

1. All requests go to `public/index.php` first. The `.htaccess` file does this, so you don't see `public` in the link.
2. CodeIgniter checks `app/Config/Routes.php` to find which controller handles that link.
3. Before the page opens, the filters in `app/Filters` run. They check that MySQL is on and that you are logged in with the right role. If a staff tries to open an owner page, they get sent back to their own dashboard.
4. The controller gets the data from the models.
5. The controller sends the data to the view, and the view shows the page.

## What we changed when we moved to CodeIgniter

Before, every page was one PHP file that had both the logic and the HTML, like `owner/clients.php`. Now:

- The logic of each page moved to a controller, and the HTML moved to a view.
- `includes/header.php` and `footer.php` became one layout file, `app/Views/layouts/app.php`. The login pages use `app/Views/layouts/auth.php`.
- The classes from `classes/` moved to `app/Models`. Auth, Database and ValidationException moved to `app/Libraries`.
- The helper functions moved to `app/Helpers`.
- CSS, JS, images and uploaded photos moved to the `public` folder.
- Links don't have `.php` anymore. For example, `owner/dashboard.php` is now just `owner/dashboard`.
- The CSRF security check for forms is now done by CodeIgniter.
- The database settings moved from `config/config.php` to `app/Config/Database.php`.

The look and the features of the system are the same as before.

## Where things are

| Folder / File              | What's inside                                          |
|----------------------------|--------------------------------------------------------|
| `app/Controllers`          | The pages (Owner, Staff, Client, Api folders)          |
| `app/Models`               | Classes like User, Appointment, Service, Fee, Report   |
| `app/Views`                | The HTML of each page                                  |
| `app/Config/Routes.php`    | The list of all page links                             |
| `app/Config/Database.php`  | MySQL settings                                         |
| `app/Filters`              | Login, role and database checks                        |
| `public`                   | index.php, CSS, JS, images and uploaded photos         |
| `database/cut_schedule.sql`| The database with sample data                          |
| `writable`                 | Sessions and logs (CodeIgniter makes these itself)     |
| `vendor`                   | CodeIgniter itself (made by `composer install`)        |

## If something goes wrong

- **Blank page or error about vendor** - you forgot to run `composer install`.
- **Error about intl** - turn on `extension=intl` in php.ini (step 2) and restart Apache.
- **"Cannot connect to MySQL"** - start MySQL in XAMPP.
- **Page not found when clicking links** - make sure the `.htaccess` file is in the project folder and Apache is on.
