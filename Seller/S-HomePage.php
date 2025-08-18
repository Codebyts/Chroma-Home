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

    // Fetch products
    $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at
            FROM product 
            WHERE sellerID = ?";
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
    <title>📦 My Products - <?php echo htmlspecialchars($sellerName); ?> Store</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
        body { background:#f0f6ff; color:#333; }

        /* Navbar */
        .navbar {
            background:linear-gradient(90deg,#004080,#0059b3);
            color:white;
            display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
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
            margin-left:8px; margin-top:5px;
            text-decoration:none; font-weight:bold; font-size:14px;
            padding:5px 10px; border-radius:6px; color:white; transition:0.3s;
        }
        .nav-links a:hover, .nav-links a.active { background:#ffc107; color:#000; }

        /* Container */
        .container { max-width:1200px; margin:20px auto; padding:0 10px; }
        .container h2 { margin-bottom:15px; color:#004080; }
        .btn {
            display:inline-block; background:#007bff; color:white;
            padding:6px 12px; border-radius:5px; text-decoration:none;
            margin-bottom:15px; font-size:14px;
        }
        .btn:hover { background:#0056b3; }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 250px));
            gap: 12px;
            justify-content: center;
        }
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
            max-width: 250px;
            width: 100%;
        }
        .product-card:hover { transform:translateY(-3px); box-shadow:0 5px 15px rgba(0,0,0,0.2); }
        .product-card img { width:100%; height:130px; object-fit:cover; }

        .product-info { padding:8px; flex:1; display:flex; flex-direction:column; }
        .product-info h3 { font-size:14px; margin-bottom:4px; color:#004080; }
        .product-info p { font-size:12px; color:#555; margin-bottom:6px; flex-grow:1; line-height:1.2; }
        .product-meta { font-size:11px; color:#444; margin-bottom:3px; }
        .product-price { font-size:14px; font-weight:bold; color:#e60000; }

        .product-actions {
            display:flex; justify-content:space-between;
            padding:6px 8px; border-top:1px solid #eee;
            flex-wrap:wrap; gap:5px;
        }
        .edit, .delete, .orders-btn {
            text-decoration:none; padding:3px 6px; border-radius:5px; font-size:11px;
        }
        .edit { background:#ffc107; color:black; }
        .delete { background:#dc3545; color:white; }
        .orders-btn { background:#007bff; color:white; }
        .edit:hover { background:#e0a800; }
        .delete:hover { background:#c82333; }
        .orders-btn:hover { background:#0056b3; }

        /* Modal */
        .modal {
            display:none; position:fixed; z-index:1000; padding-top:60px;
            left:0; top:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5);
        }
        .modal-content {
            background:white; margin:auto; padding:15px;
            border-radius:10px; width:90%; max-width:400px;
            max-height:70vh; overflow-y:auto; box-shadow:0 5px 15px rgba(0,0,0,0.3);
            white-space:pre-line; font-size:14px;
        }
        .close { float:right; font-size:20px; font-weight:bold; cursor:pointer; color:#333; }
        .close:hover { color:red; }
        .desc-btn {
            display:inline-block; padding:4px 6px;
            background:#17a2b8; color:white; border-radius:5px;
            font-size:11px; text-decoration:none; cursor:pointer;
        }
        .desc-btn:hover { background:#138496; }

        /* Responsive tweaks & hamburger */
        @media (max-width:769px) {
            .hamburger { display:flex; }
            .search-box { width:100%; order:2; margin-top:10px; }
            .nav-links { 
                display:none;
                position:absolute;
                top:70px;
                right:0;
                background:#004080;
                flex-direction:column;
                width:200px;
                border-radius:0 0 8px 8px;
                padding:10px 0;
                box-shadow:0 3px 8px rgba(0,0,0,0.3);
                z-index:999;
            }
            .nav-links.active { display:flex; }
            .nav-links a { width:100%; padding:10px 15px; margin:0; }
            .navbar { flex-direction:column; align-items:flex-start; }
        }

        /* Update the media query for mobile */
        @media (max-width:480px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 250px));
                gap: 8px;
            }
            .product-info h3 { font-size:13px; }
            .product-info p { font-size:11px; }
            .product-meta { font-size:10px; }
            .product-price { font-size:13px; }
            .edit, .delete, .orders-btn { font-size:10px; padding:2px 4px; }
            .btn { font-size:12px; padding:4px 8px; }
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
        <input type="text" placeholder="🔍 Search your products...">
    </div>

    <div class="nav-links" id="navLinks">
        <a href="S-HomePage.php" class="active">📦 PRODUCTS</a>
        <a href="SellerOrders.php">🛒 ORDERS</a>
        <a href="../LandingPage/LoginPage.php">🚪 LOGOUT</a>
    </div>
</div>

<div class="container">
    <h2>📦 My Products</h2>
    <a href="AddProduct.php" class="btn">➕ Add Product</a>
    <a href="EditProfile.php" class="btn" style="background:#28a745;">👤 Edit Profile</a>
    <a href="" class="btn" style="background:#17a2b8;">💬 Messages</a>

    <div class="product-grid">
        <?php while($row=$result->fetch_assoc()): ?>
        <div class="product-card">
            <img src="../uploads/products/<?php echo !empty($row['image']) ? htmlspecialchars($row['image']) : 'no-image.png'; ?>" alt="Product">
            <div class="product-info">
                <h3>🔹 <?php echo htmlspecialchars($row['product_name']); ?></h3>
                <?php
                    $fullDesc = htmlspecialchars($row['description']);
                    $shortDesc = strlen($fullDesc) > 50 ? substr($fullDesc,0,50).'...' : $fullDesc;
                ?>
                <p><?php echo $shortDesc; ?></p>
                <?php if(strlen($fullDesc) > 50): ?>
                <button class="desc-btn" data-description="<?php echo $fullDesc; ?>" onclick="openModal(this)">📖 View Full</button>
                <?php endif; ?>
                <div class="product-meta">📦 Stock: <?php echo htmlspecialchars($row['stock']); ?></div>
                <div class="product-meta">📂 Category: <?php echo htmlspecialchars($row['categoryID']); ?></div>
                <div class="product-meta">📅 Added: <?php echo htmlspecialchars($row['create_at']); ?></div>
                <div class="product-price">💰 ₱<?php echo number_format($row['price'],2); ?></div>
            </div>
            <div class="product-actions">
                <a href="EditProduct.php?id=<?php echo $row['productID']; ?>" class="edit">✏️ Edit</a>
                <a href="DeleteProduct.php?id=<?php echo $row['productID']; ?>" class="delete" onclick="return confirm('Are you sure?');">🗑 Delete</a>
                <a href="SellerOrders.php?product_id=<?php echo $row['productID']; ?>" class="orders-btn">📦 Orders</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div id="descModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>📖 Full Description</h3>
        <p id="fullDesc"></p>
    </div>
</div>

<script>
function openModal(btn){
    document.getElementById('fullDesc').innerText = btn.getAttribute('data-description');
    document.getElementById('descModal').style.display='block';
}
function closeModal(){ document.getElementById('descModal').style.display='none'; }
window.onclick=function(e){ if(e.target===document.getElementById('descModal')) closeModal(); }

function toggleMenu(){
    document.getElementById('navLinks').classList.toggle('active');
}

// Search functionality
const searchInput=document.querySelector('.search-box input');
searchInput.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card=>{
        const name=card.querySelector('h3').innerText.toLowerCase();
        card.style.display=name.includes(q)?'':'none';
    });
});
</script>
</body>
</html>