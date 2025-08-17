<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

$sellerID = $_SESSION['userID'];

// ✅ Fetch seller name
$stmtUser = $conn->prepare("SELECT name FROM users WHERE userID = ?");
$stmtUser->bind_param("i", $sellerID);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();
$sellerName = $user ? $user['name'] : "Seller";

// Fetch product
if (isset($_GET['id'])) {
    $productID = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM product WHERE productID = ? AND sellerID = ?");
    $stmt->bind_param("ii", $productID, $sellerID);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        die("Product not found or you don't have permission to edit.");
    }
}

// Update product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['product_name'];
    $desc = $_POST['description'];
    $stock = $_POST['stock'];
    $categoryID = $_POST['categoryID'];
    $price = $_POST['price'];

    $imageName = $product['image']; // keep old image if none uploaded
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/products/";
        $imageName = basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetDir . $imageName);
    }

    $stmt = $conn->prepare("UPDATE product SET product_name=?, description=?, stock=?, categoryID=?, price=?, image=? WHERE productID=? AND sellerID=?");
    $stmt->bind_param("ssiidssi", $name, $desc, $stock, $categoryID, $price, $imageName, $productID, $sellerID);
    $stmt->execute();

    header("Location: S-HomePage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - <?php echo htmlspecialchars($sellerName); ?> Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f0f6ff; color: #333; }

        /* Navbar */
        .navbar {
            background: linear-gradient(90deg, #004080, #0059b3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 30px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }
        .navbar .logo { font-size: 22px; font-weight: bold; }
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            padding: 6px 12px;
            border-radius: 6px;
            color: white;
            transition: 0.3s;
        }
        .nav-links a:hover,
        .nav-links a.active { background: #ffc107; color: #000; }

        /* Container */
        .container { max-width: 600px; margin: 30px auto; padding: 0 15px; }
        h2 { margin-bottom: 20px; color: #004080; }

        /* Form Card */
        form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        form label { font-weight: bold; margin-top: 10px; display: block; }
        form input, form textarea, form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        form textarea { resize: vertical; min-height: 80px; }

        .current-image img {
            margin-top: 5px;
            max-width: 150px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        button {
            background: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            transition: 0.3s;
        }
        button:hover { background: #0056b3; }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
            color: #007bff;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo">🏬 <?php echo htmlspecialchars($sellerName); ?> Store</div>
    <div class="nav-links">
        <a href="ViewProduct.php">📦 PRODUCTS</a>
        <a href="SellerOrders.php">🛒 ORDERS</a>
        <a href="../LandingPage/LoginPage.php">🚪 LOGOUT</a>
    </div>
</div>

<div class="container">
    <h2>✏️ Edit Product</h2>
    <a class="back-link" href="view_product.php">← Back to Products</a>
    <form method="POST" enctype="multipart/form-data">
        <label>Product Name</label>
        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>

        <label>Description</label>
        <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>

        <label>Stock</label>
        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>

        <label>Category ID</label>
        <input type="number" name="categoryID" value="<?php echo $product['categoryID']; ?>" required>

        <label>Price</label>
        <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>

        <?php if (!empty($product['image'])): ?>
            <div class="current-image">
                <label>Current Image</label>
                <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
            </div>
        <?php endif; ?>

        <label>Upload New Image (optional)</label>
        <input type="file" name="image">

        <button type="submit">Update Product</button>
    </form>
</div>

</body>
</html>