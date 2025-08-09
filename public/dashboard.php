<?php
// Include the header file first. This starts the session and checks for login.
include '../includes/header.php';

// At this point, the session is active and we know the user is logged in.
$user_id = $_SESSION['user_id'];

// Include the database configuration file.
include '../includes/config.php';

// Set default values for year and month
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : null;

// Handle AJAX requests (This block is kept but won't be used since the UI is removed)
if (isset($_GET['data_type']) && isset($_GET['year'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid request'];

    if ($_GET['data_type'] == 'monthly') {
        $sql = "SELECT MONTH(sale_date) as month, 
                         COALESCE(SUM(total_price), 0) as total_sales,
                         COALESCE(SUM(total_price - (sale_quantity * (SELECT cost_rate FROM products WHERE id = sales_transactions.product_id))), 0) as total_profit
                 FROM sales_transactions
                 WHERE user_id = ? AND YEAR(sale_date) = ?
                 GROUP BY MONTH(sale_date)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $response = ['status' => 'success', 'data' => $data];
        $stmt->close();
    } elseif ($_GET['data_type'] == 'daily' && isset($_GET['month'])) {
        $sql = "SELECT DATE(sale_date) as day, 
                         COALESCE(SUM(total_price), 0) as total_sales,
                         COALESCE(SUM(total_price - (sale_quantity * (SELECT cost_rate FROM products WHERE id = sales_transactions.product_id))), 0) as total_profit
                 FROM sales_transactions
                 WHERE user_id = ? AND YEAR(sale_date) = ? AND MONTH(sale_date) = ?
                 GROUP BY DATE(sale_date)
                 ORDER BY day DESC
                 LIMIT 5";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $selected_year, $selected_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $response = ['status' => 'success', 'data' => $data];
        $stmt->close();
    }
    echo json_encode($response);
    exit;
}

// Initialize profit variables for the summary cards
$today_profit = 0;
$monthly_profit = 0;
$yearly_profit = 0;
$total_profit_lifetime = 0;

// Get profit data for summary cards - CORRECTED QUERIES
$sql_today = "SELECT SUM(st.sale_quantity * (st.sale_price - p.cost_rate)) AS total_profit FROM sales_transactions st JOIN products p ON st.product_id = p.id WHERE st.user_id = ? AND DATE(st.sale_date) = CURDATE()";
$stmt_today = $conn->prepare($sql_today);
$stmt_today->bind_param("i", $user_id);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
if ($result_today->num_rows > 0) { $row = $result_today->fetch_assoc(); $today_profit = $row['total_profit'] ?? 0; }
$stmt_today->close();

$sql_monthly = "SELECT SUM(st.sale_quantity * (st.sale_price - p.cost_rate)) AS total_profit FROM sales_transactions st JOIN products p ON st.product_id = p.id WHERE st.user_id = ? AND MONTH(st.sale_date) = MONTH(CURDATE()) AND YEAR(st.sale_date) = YEAR(CURDATE())";
$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param("i", $user_id);
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();
if ($result_monthly->num_rows > 0) { $row = $result_monthly->fetch_assoc(); $monthly_profit = $row['total_profit'] ?? 0; }
$stmt_monthly->close();

$sql_yearly = "SELECT SUM(st.sale_quantity * (st.sale_price - p.cost_rate)) AS total_profit FROM sales_transactions st JOIN products p ON st.product_id = p.id WHERE st.user_id = ? AND YEAR(st.sale_date) = YEAR(CURDATE())";
$stmt_yearly = $conn->prepare($sql_yearly);
$stmt_yearly->bind_param("i", $user_id);
$stmt_yearly->execute();
$result_yearly = $stmt_yearly->get_result();
if ($result_yearly->num_rows > 0) { $row = $result_yearly->fetch_assoc(); $yearly_profit = $row['total_profit'] ?? 0; }
$stmt_yearly->close();

// Calculate total lifetime profit from sales_transactions table (more reliable)
$sql_total = "SELECT SUM(st.sale_quantity * (st.sale_price - p.cost_rate)) AS total_profit FROM sales_transactions st JOIN products p ON st.product_id = p.id WHERE st.user_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
if ($result_total->num_rows > 0) { $row = $result_total->fetch_assoc(); $total_profit_lifetime = $row['total_profit'] ?? 0; }
$stmt_total->close();

// Fetch recent sales activity - CORRECTED QUERY
$recent_sales = [];
$sql_recent_sales = "SELECT st.id, p.product_name AS product_name, st.sale_quantity, st.total_price, st.sale_date FROM sales_transactions st JOIN products p ON st.product_id = p.id WHERE st.user_id = ? ORDER BY st.sale_date DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent_sales);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
while ($row = $result_recent->fetch_assoc()) {
    $recent_sales[] = $row;
}
$stmt_recent->close();

// --- HTML PART STARTS HERE ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #50E3C2;
            --dark-text: #333;
            --light-text: #666;
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow-light: rgba(0, 0, 0, 0.05);
            --shadow-medium: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--dark-text);
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background-color: #2c3e50; /* A darker, professional background */
            color: #ecf0f1;
            box-shadow: 2px 0 15px rgba(0,0,0,0.2);
            padding: 20px 0;
            position: fixed;
            height: 100%;
        }

        .sidebar h2 {
            color: #3498db;
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 1.2rem;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar nav ul li {
            margin-bottom: 5px;
        }

        .sidebar nav ul li a {
            text-decoration: none;
            color: #ecf0f1;
            font-weight: 500;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            border-left: 5px solid transparent;
        }

        .sidebar nav ul li a i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .sidebar nav ul li a:hover, .sidebar nav ul li a.active {
            background-color: #34495e;
            color: #ffffff;
            border-left: 5px solid var(--primary-color);
        }

        /* Main Content Styling */
        .main-content {
            flex-grow: 1;
            margin-left: 250px; /* Sidebar-এর সমান মার্জিন */
            padding: 30px;
        }

        /* Main Header Styling - Removed white background */
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .main-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0;
        }

        .main-header .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .main-header .user-info .profile-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-medium);
        }

        .card h4 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            color: var(--light-text);
            font-weight: 600;
        }

        .card p {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }

        /* Recent Activities */
        .recent-activities {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .recent-activities h3 {
            font-size: 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .recent-activities table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-activities th, .recent-activities td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .recent-activities th {
            background-color: var(--bg-color);
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .recent-activities tbody tr:last-child td {
            border-bottom: none;
        }
        
        .recent-activities p {
            text-align: center;
            color: var(--light-text);
            font-style: italic;
        }

        /* Add icons to summary cards for better visual appeal */
        .card .icon {
            float: right;
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.2;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <h2>Business Management</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="../pages/products/stock_list.php"><i class="fas fa-box"></i> <span>Stock List</span></a></li>
                <li><a href="../pages/customers/customer_list.php"><i class="fas fa-users"></i> <span>Customer List</span></a></li>
                <li><a href="/business_management/pages/suppliers/purchase.php"><i class="fas fa-truck"></i> <span> Purchase</span></a></li>
                <li><a href="/business_management/pages/customers/sale.php"><i class="fas fa-cash-register"></i> <span> Sale</span></a></li>
                <li><a href="/business_management/pages/settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1>Dashboard</h1>
        </header>

        <section class="summary-cards">
            <div class="card">
                <i class="fas fa-chart-line icon"></i>
                <h4>Total Lifetime Profit</h4>
                <p>৳ <?php echo number_format($total_profit_lifetime, 2); ?></p>
            </div>
            <div class="card">
                <i class="fas fa-calendar-day icon"></i>
                <h4>Today's Profit</h4>
                <p>৳ <?php echo number_format($today_profit, 2); ?></p>
            </div>
            <div class="card">
                <i class="fas fa-calendar-week icon"></i>
                <h4>Monthly Profit</h4>
                <p>৳ <?php echo number_format($monthly_profit, 2); ?></p>
            </div>
            <div class="card">
                <i class="fas fa-chart-bar icon"></i>
                <h4>Yearly Profit</h4>
                <p>৳ <?php echo number_format($yearly_profit, 2); ?></p>
            </div>
        </section>

        <section class="recent-activities">
            <h3>Recent Activities</h3>
            <?php if (count($recent_sales) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($sale['sale_quantity']); ?></td>
                                <td>৳ <?php echo number_format($sale['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($sale['sale_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent sales activity to show.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>
<?php include('../includes/footer.php'); ?>