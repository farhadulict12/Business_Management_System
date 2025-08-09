# Business Management System (BMS)

## Overview

This is a web-based Business Management System (BMS) designed to help small businesses manage their daily operations. The system includes modules for managing customers, suppliers, products, and a complete transaction history for both sales and purchases.

---

## Database Schema

The core of the application is built on a robust MySQL database. The following SQL script, `bms_db.sql`, creates all the necessary tables and defines their relationships. The database is structured to ensure data integrity and efficient querying.

### Key Tables & Relationships

The database consists of several interconnected tables:

#### `users`
* **Purpose:** Stores user authentication details for a multi-tenant system. Each user's data is isolated and secure.
* **Columns:** `id`, `mobile_number`, `password`, `email`, `created_at`, `2fa_secret`.

#### `suppliers`
* **Purpose:** Manages a list of all suppliers.
* **Relationships:**
    * **`user_id`**: A foreign key linking to the `users` table, ensuring each supplier is associated with a specific business owner.

#### `customers`
* **Purpose:** Manages a list of all customers, including their outstanding balances.
* **Relationships:**
    * **`user_id`**: A foreign key linking to the `users` table.

#### `products`
* **Purpose:** Manages the product inventory, including stock quantity, costs, and selling prices.
* **Relationships:**
    * **`user_id`**: Links the product to a specific user.
    * **`supplier_id`**: Identifies which supplier a product was purchased from.

#### `sales_transactions`
* **Purpose:** Logs every product sale, recording details such as quantity sold, price, and payment status.
* **Relationships:**
    * **`customer_id`**: Links the transaction to a specific customer.
    * **`product_id`**: Identifies the product sold.
    * **`user_id`**: Links the transaction to a specific user.

#### `customer_transactions`
* **Purpose:** A detailed ledger for all financial activities with customers (e.g., sales and payments).
* **Relationships:**
    * **`customer_id`**: Links the transaction to a specific customer.
    * **`user_id`**: Links the transaction to a specific user.

#### `supplier_transactions`
* **Purpose:** A detailed ledger for all financial activities with suppliers (e.g., purchases and payments).
* **Relationships:**
    * **`supplier_id`**: Links the transaction to a specific supplier.
    * **`product_id`**: Identifies the product purchased (can be `NULL` for payments).
    * **`user_id`**: Links the transaction to a specific user.

#### `supplier_payments`
* **Purpose:** A separate table to specifically track payments made to suppliers.
* **Relationships:**
    * **`supplier_id`**: Links the payment to a specific supplier.
    * **`user_id`**: Links the payment to a specific user.

---

## Getting Started

### Prerequisites
* A web server (e.g., Apache)
* PHP (version 8.x recommended)
* MySQL/MariaDB database server

### Installation

1.  **Clone the repository:**
    ```bash
    git clone [Your-Repository-URL]
    ```

2.  **Set up the database:**
    * Import the provided `bms_db.sql` file into your MySQL database using a tool like phpMyAdmin or MySQL Workbench.
    * Alternatively, you can run the SQL script from the command line:
        ```bash
        mysql -u [username] -p [database_name] < bms_db.sql
        ```

3.  **Configure the application:**
    * Open `includes/db.php` and update the database connection details to match your setup.

    ```php
    $servername = "localhost";
    $username = "your_db_username";
    $password = "your_db_password";
    $dbname = "bms_db";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    ```

4.  **Launch the application:**
    * Place the project files in your web server's root directory (e.g., `C:\xampp\htdocs\business_management`).
    * Open your web browser and navigate to `http://localhost/business_management/`.

---

## Contributing

We welcome contributions! Please feel free to fork this repository, make your changes, and submit a pull request.
