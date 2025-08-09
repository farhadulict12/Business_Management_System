<?php
// Start a session to use session variables.
session_start();

// includes/db.php-এর সঠিক পাথ
include('db.php');

// Check if the user is NOT logged in. If they are not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <style>
        /* Basic Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Header & Navigation Bar */
        .main-header {
            background-color: #337ab7; /* A professional blue */
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Logo Styling */
        .main-header .logo-container {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .main-header .logo {
            height: 60px; /* Updated: Set a larger height for the logo */
            width: auto; /* Maintain aspect ratio */
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .main-nav {
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .main-nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .main-nav a:hover {
            background-color: #286090;
        }

        /* Dropdown Menu */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            top: 100%;
            right: 0;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Hamburger Button */
        .hamburger-btn {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            position: relative;
        }

        .hamburger-btn span {
            display: block;
            width: 100%;
            height: 3px;
            background: #fff;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        /* Responsive Design for Mobile */
        @media screen and (max-width: 768px) {
            .main-header {
                flex-wrap: wrap;
            }

            .hamburger-btn {
                display: flex;
            }

            .main-nav {
                flex-direction: column;
                width: 100%;
                display: none;
                background-color: #337ab7;
                margin-top: 10px;
                border-radius: 5px;
            }

            .main-nav.active {
                display: flex;
            }

            .main-nav a,
            .main-nav .dropdown {
                width: 100%;
                text-align: center;
                padding: 10px 0;
                margin: 0;
                border-bottom: 1px solid #286090;
            }
            
            .main-nav a:last-child,
            .main-nav .dropdown:last-child {
                border-bottom: none;
            }

            .dropdown-content {
                position: static;
                min-width: 100%;
                background-color: #fff;
                box-shadow: none;
                border-radius: 0;
            }

            .dropdown-content a {
                padding: 12px 16px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="logo-container">
        <a href="/business_management/public/dashboard.php">
            <img class="logo" src="/business_management/free.jpg" alt="Business Management Logo">
        </a>
    </div>
    <button class="hamburger-btn" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <nav class="main-nav" id="mainNav">
        <a href="/business_management/public/dashboard.php">Dashboard</a>
        
        <div class="dropdown">
            <a href="#" class="dropbtn">Suppliers</a>
            <div class="dropdown-content">
                <a href="/business_management/pages/suppliers/supplier_list.php">Supplier List</a>
                <a href="/business_management/pages/suppliers/purchase.php"> Purchase</a>
                <a href="/business_management/pages/suppliers/payment_to_supplier.php"> Payment to Supplier</a>
            </div>
        </div>
        <a href="/business_management/pages/products/stock_list.php">Stock</a>
        <div class="dropdown">
            <a href="#" class="dropbtn">Customers</a>
            <div class="dropdown-content">
                <a href="/business_management/pages/customers/customer_list.php">Customer List</a>
                <a href="/business_management/pages/customers/sale.php"> Sale</a>
                <a href="/business_management/pages/customers/payment_from_customers.php"> Payment From Customers</a>
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="dropbtn">Profile</a>
            <div class="dropdown-content">
                <a href="/business_management/pages/settings.php">Settings</a>
                <a href="/business_management/public/logout.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<script>
function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('active');
}
</script>
</body>
</html>