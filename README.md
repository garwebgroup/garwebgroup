# Legon Hills Hotel - Information System

This is the codebase for the Legon Hills Hotel Information System website, designed with a premium, aesthetic look utilizing HTML, CSS, JavaScript, and a PHP Backend.

## Features Included:
- **Home Page**: Features the 10 group members using professional Phosphor icons.
- **Image Swapper**: A 5-second interval JavaScript slider showcasing hotel imagery.
- **Scrolling Text**: Important announcements scrolling smoothly across the bottom of the home page.
- **Pop-ups**: 3 interactive JS popups (Alert, Confirm, Custom HTML Modal) demonstrated on the Contact Page.
- **Login & Registration**: Full UI with a PHP backend connection to handle authentication.
- **Dashboard CRUD**: A complete interface to Retrieve, Add, Update, and Delete database records without page reloads using the Fetch API.

## How to Connect Your Designed Database

Since you mentioned you have already designed your database and will map the tables later, I have provided a structured way to integrate it using the APIs.

### Step 1: Set up the Local Server
You will need a local PHP server like **XAMPP**, **MAMP**, or **WAMP** to run this project since it relies on PHP for the backend.
1. Copy the entire `New Work` folder into your server's web root (e.g., `htdocs` for XAMPP or `www` for WAMP).
2. Start the Apache and MySQL services in your control panel.

### Step 2: Configure Database Credentials
Open the `api/config.php` file in a text editor and update the following variables to match your existing database:
```php
$host = 'localhost';
$db_name = 'your_database_name'; // Change to your actual DB name
$username = 'root';              // Change if you use a specific DB user
$password = '';                  // Enter your DB password if applicable
```

### Step 3: Map Your Tables in the PHP API
Currently, the API endpoints are hardcoded to generic tables named `users` and `records`. Once you are ready to map your actual tables:

**For Authentication (`api/auth.php`):**
If your employee/admin table is called `employees` instead of `users`:
- Open `api/auth.php`
- Change `INSERT INTO users...` to `INSERT INTO employees...`
- Change `SELECT ... FROM users...` to `SELECT ... FROM employees...`
- Ensure the column names (`name`, `email`, `password`) match your table schema.

**For the Information System CRUD (`api/records.php`):**
If you want to manage `reservations` or `guests` instead of `records`:
- Open `api/records.php`
- Change `SELECT * FROM records` to `SELECT * FROM reservations`
- Change the `INSERT INTO records` statement to match your table's exact column names.
- Change the `UPDATE records` and `DELETE FROM records` statements accordingly.

### Step 4: Update Frontend Form fields (If necessary)
If your database requires different fields (e.g., "Room Number", "Check-in Date" instead of "Details"), you will need to:
1. Open `dashboard.html` and change the HTML `<input>` fields in the form.
2. Open `js/api.js` and update the JavaScript `FormData` append methods to collect the new field values.
3. Update `api/records.php` to accept the new `$_POST` variables and bind them in the SQL statements.

---

> **Design Note:** The website uses a beautiful dark teal and peach color scheme, avoiding generic colors and implementing modern glassmorphism components as requested. No emojis were used; professional Phosphor Icons were utilized throughout.
