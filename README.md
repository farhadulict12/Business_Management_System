# Business Management System (BMS)

## Overview

This is a web-based Business Management System (BMS) designed to help small businesses manage their daily operations. The system includes modules for managing customers, suppliers, products, and a complete transaction history for both sales and purchases.

---

## Database Schema

The core of the application is built on a robust MySQL database. The following SQL script, `bms_db.sql`, creates all the necessary tables and defines their relationships. The database is structured to ensure data integrity and efficient querying.

Here are the SQL codes for all the tables in your database, along with a description of what each table and its columns do. This is a great way to document your database for a project.

-----

### **1. `users` Table**

This table stores information for each business owner (user) who uses the system. All other data is linked to a specific user to ensure data is private.

```sql
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile_number` VARCHAR(15) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `2fa_secret` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `mobile_number` (`mobile_number`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **2. `suppliers` Table**

This table keeps a record of all the suppliers your business works with.

```sql
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(255) NOT NULL,
    `mobile_number` VARCHAR(20) NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `fk_suppliers_users` (`user_id`),
    CONSTRAINT `fk_suppliers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **3. `customers` Table**

This table stores all your customer information, including any outstanding balance they owe.

```sql
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `mobile_number` VARCHAR(15) NOT NULL,
    `due_amount` DECIMAL(10,2) NULL DEFAULT '0.00',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_customers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **4. `products` Table**

This table is your inventory. It tracks product details, stock quantity, cost, and selling price.

```sql
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NULL,
    `supplier_id` INT(11) UNSIGNED NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `cost_rate` DECIMAL(10,2) NOT NULL,
    `final_cost_per_unit` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `mrp` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `supplier_id` (`supplier_id`),
    CONSTRAINT `fk_products_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_products_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **5. `sales_transactions` Table**

This table records every single sale transaction made, linking it to a customer and a product.

```sql
CREATE TABLE IF NOT EXISTS `sales_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) UNSIGNED NOT NULL,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `sale_quantity` INT(11) NOT NULL,
    `sale_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `transaction_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `user_id` INT(11) UNSIGNED NOT NULL,
    `sale_date` DATE NOT NULL DEFAULT CURDATE(),
    PRIMARY KEY (`id`),
    KEY `customer_id` (`customer_id`),
    KEY `product_id` (`product_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_sales_transactions_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_transactions_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_transactions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **6. `supplier_transactions` Table**

This table tracks all purchases from suppliers, including quantity, cost, and payment details.

```sql
CREATE TABLE IF NOT EXISTS `supplier_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `supplier_id` INT(11) UNSIGNED NOT NULL,
    `product_id` INT(11) NULL,
    `purchase_quantity` DECIMAL(10,2) NULL,
    `cost_per_unit` DECIMAL(10,2) NULL,
    `total_cost` DECIMAL(10,2) NULL,
    `paid_amount` DECIMAL(10,2) NULL,
    `purchase_date` DATE NULL,
    `notes` TEXT NULL,
    `transaction_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `user_id` INT(11) UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    PRIMARY KEY (`id`),
    KEY `supplier_id` (`supplier_id`),
    KEY `product_id` (`product_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_supplier_transactions_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_transactions_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_transactions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **7. `customer_transactions` Table**

This table is a financial ledger for all money received from customers (payments and sales).

```sql
CREATE TABLE IF NOT EXISTS `customer_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) UNSIGNED NOT NULL,
    `transaction_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `user_id` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `customer_id` (`customer_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_customer_transactions_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_transactions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

-----

### **8. `supplier_payments` Table**

This table is specifically for tracking payments you have made to suppliers.

```sql
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `supplier_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `description` TEXT NULL,
    `user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `supplier_id` (`supplier_id`),
    CONSTRAINT `fk_supplier_payments_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_payments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

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
