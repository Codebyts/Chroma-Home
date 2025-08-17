<?php
    session_start();
    include '../db.php';
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
    }
    $sellerID = $_SESSION['userID'];

    // Fetch seller name
    $stmtUser = $conn->prepare("SELECT name FROM users WHERE userID = ?");
    $stmtUser->bind_param("i", $sellerID);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $user = $resultUser->fetch_assoc();
    $sellerName = $user ? $user['name'] : "Seller";

    // Fetch orders
    $sql = "
    SELECT o.orderID, o.buyerID, p.product_name, oi.quantity, oi.price, o.total_price, o.status, o.created_at
    FROM orders o
    JOIN order_items oi ON o.orderID = oi.orderID
    JOIN product p ON oi.productID = p.productID
    WHERE p.sellerID = ?
    ORDER BY o.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sellerID);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🛒 My Orders - <?php echo htmlspecialchars($sellerName); ?> Store</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
    body { background:#f0f6ff; color:#333; }

    /* Navbar */
    .navbar {
        background:linear-gradient(90deg,#004080,#0059b3);
        color:white;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        justify-content:space-between;
        padding:12px 15px;
        box-shadow:0 3px 8px rgba(0,0,0,0.3);
        position:relative;
    }
    .navbar .logo { font-size:20px; font-weight:bold; }

    /* Hamburger */
    .hamburger {
        display:none;
        flex-direction:column;
        cursor:pointer;
        gap:4px;
        position:absolute;
        top:12px;
        right:15px;
        z-index:1000;
    }
    .hamburger div {
        width:25px;
        height:3px;
        background:white;
        border-radius:2px;
    }

    /* Search box */
    .search-box { flex:1; min-width:150px; margin:0 10px; }
    .search-box input { padding:6px 10px; border-radius:6px; border:none; outline:none; width:100%; }

    /* Links */
    .nav-links { display:flex; flex-wrap:wrap; align-items:center; }
    .nav-links a {
        margin-left:0;
        text-decoration:none; font-weight:bold; font-size:14px;
        padding:6px 10px; border-radius:8px; color:white; transition:0.25s;
        display:flex; align-items:center; gap:6px;
    }
    .nav-links a:hover, .nav-links a.active { background:#ffc107; color:#000; }
    .logout-link{ margin-left:8px; }

    /* Container */
    .container { max-width:1200px; margin:20px auto; padding:0 10px; }
    .container h2 { margin-bottom:15px; color:#004080; }

    /* Table */
    .table-wrapper {
        width:100%;
        overflow-x:auto;
        background:white;
        border-radius:10px;
        box-shadow:0 3px 8px rgba(0,0,0,0.1);
    }
    table {
        width:100%;
        min-width:800px;
        border-collapse:collapse;
        font-size:14px;
        table-layout:auto;
    }
    thead { background:#007bff; color:white; }
    th, td { padding:10px; border:1px solid #ddd; text-align:center; word-wrap:break-word; }
    tr:nth-child(even){background:#f9f9f9;}
    tr:hover{background:#eaf3ff;}
    .status { font-weight:bold; padding:3px 6px; border-radius:4px; font-size:13px; }
    .status.pending { background:#ffc107; color:#000; }
    .status.completed { background:#28a745; color:white; }
    .status.cancelled { background:#dc3545; color:white; }

    /* Tablet and Mobile */
    @media (max-width:769px){
        .hamburger { display:flex; }
        .search-box { width:100%; order:2; margin-top:10px; }
        .nav-links { 
            display:none;
            position:absolute;
            top:70px; /* below logo and search */
            right:0;
            background:#004080;
            flex-direction:column;
            width:150px;
            border-radius:0 0 8px 8px;
            padding:8px 0;
            box-shadow:0 3px 8px rgba(0,0,0,0.3);
            z-index:999;
        }
        .nav-links.active { display:flex; }
        .nav-links a { width:100%; padding:10px 15px; margin:0; }
        .navbar { flex-direction:column; align-items:flex-start; }
    }

    @media (max-width:320px){
        table {
            width: 100%;
            table-layout: fixed; 
            font-size: 8px;
        }
        th, td {
            padding: 2px 4px; 
            word-wrap: break-word; /* wrap text inside cells */
            white-space: normal; /* allow wrapping */
        }
        thead th {
            font-size: 8px;
        }
    }
    /* Slightly smaller font for 900px */
    @media (max-width:900px){
        table { font-size:11px; min-width:auto; }
        th, td { padding:6px; }
    }
</style>
</head>
<body>

    <div class="navbar">
        <div class="logo">🏬 <?php echo htmlspecialchars($sellerName); ?> Store</div>

        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <div class="search-box">
            <input type="text" placeholder="🔍 Search Orders...">
        </div>

        <div class="nav-links" id="navLinks">
            <a href="S-HomePage.php">📦 PRODUCTS</a>
            <a href="SellerOrders.php" class="active">🛒 ORDERS</a>
            <a href="../LandingPage/LoginPage.php" class="logout-link">🚪 LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <h2>🛍️ My Orders</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>📌 Order ID</th>
                        <th>🙍 Buyer ID</th>
                        <th>📦 Product</th>
                        <th>🔢 Qty</th>
                        <th>💵 Price (Each)</th>
                        <th>💰 Total</th>
                        <th>📋 Status</th>
                        <th>⏰ Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows>0): ?>
                        <?php while($row=$result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Order ID"><?php echo $row['orderID']; ?></td>
                                <td data-label="Buyer ID"><?php echo $row['buyerID']; ?></td>
                                <td data-label="Product"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td data-label="Qty"><?php echo $row['quantity']; ?></td>
                                <td data-label="Price">₱<?php echo number_format($row['price'],2); ?></td>
                                <td data-label="Total">₱<?php echo number_format($row['total_price'],2); ?></td>
                                <td data-label="Status"><span class="status <?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td data-label="Date"><?php echo $row['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">⚠️ No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleMenu(){
        document.getElementById('navLinks').classList.toggle('active');
    }

    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('table tbody tr').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
</script>
</body>
</html>