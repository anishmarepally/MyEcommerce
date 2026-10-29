# MyEcommerce 🛒

A simple e-commerce website built using PHP, MySQL, HTML, CSS, and JavaScript. The project includes product browsing, a shopping cart, checkout, and an admin panel for managing products and orders.

## Features

- **Product Listing:** Display products with prices, images, and stock availability.
- **Shopping Cart:** Add products to the cart and review selected items.
- **Checkout:** Enter customer details and place an order.
- **Order Management:** Store orders and order items in the database.
- **Stock Management:** Update product stock when an order is placed.
- **Admin Panel:** Log in to manage products and view customer orders.
- **Product Images:** Display product images from the local assets folder.

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** Apache using XAMPP
- **Version Control:** Git and GitHub

## Project Structure

```text
MyEcommerce/
├── admin/
│   ├── dashboard.php
│   ├── edit_product.php
│   ├── login.php
│   ├── logout.php
│   ├── order_details.php
│   ├── orders.php
│   └── products.php
├── assets/
│   └── Images/
├── config/
│   └── database.php
├── cart.php
├── checkout.php
├── index.php
├── .gitignore
└── README.md
```

## Requirements

- Windows, macOS, or Linux
- XAMPP or another PHP and MySQL environment
- A web browser
- Git (optional)

## Installation and Setup

### 1. Clone the Repository

Open a terminal and run:

```bash
git clone https://github.com/anishmarepally/MyEcommerce.git
```

For XAMPP on Windows, place the project folder inside:

```text
C:\xampp\htdocs\
```

The project path should be:

```text
C:\xampp\htdocs\MyEcommerce
```

### 2. Start XAMPP

Open the XAMPP Control Panel and start:

- Apache
- MySQL

### 3. Create the Database

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named `my_ecommerce`.
3. Import your database SQL backup using the **Import** tab.

The SQL backup should contain the required tables, including `products`, `orders`, and `order_items`.

### 4. Configure the Database

Create or configure `config/database.php` with your local MySQL connection settings.

Use your actual database host, database name, username, and password. Do not commit database credentials or other secrets to GitHub.

### 5. Run the Website

Open the following URL in your browser:

```text
http://localhost/MyEcommerce/
```

To open the admin login page:

```text
http://localhost/MyEcommerce/admin/login.php
```

Use the admin account configured for your local database.

## Order Workflow

1. Browse products on the homepage.
2. Add products to the shopping cart.
3. Review the cart.
4. Enter customer details at checkout.
5. Place the order.
6. View the saved order through the admin panel.

The current checkout is a demonstration using Cash on Delivery (COD). Online payment gateway integration is not included.

## Deployment

This project requires a hosting provider that supports PHP and MySQL. For deployment:

1. Upload the project files to the hosting web root.
2. Create an online MySQL database.
3. Import your database backup.
4. Configure the database connection with the hosting provider's credentials.
5. Test the storefront, cart, checkout, and admin panel.

Keep configuration files and admin credentials private. Do not upload local database passwords or setup/reset scripts to a public repository.

## Repository

GitHub: https://github.com/anishmarepally/MyEcommerce

## License

No license has been specified yet.
