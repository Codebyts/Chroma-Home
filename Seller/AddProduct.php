<?php
session_start();
include '../db.php';

// Make sure user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

$userID = $_SESSION['userID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $categoryID = $_POST['categoryID'];
    $price = $_POST['price'];

    // Handle image upload
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('item_', true) . '.' . strtolower($image_ext);
        $uploadFile = $uploadDir . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            die("Error uploading image");
        }
    }

    $sql = "INSERT INTO product (sellerID, product_name, description, stock, categoryID, price, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issiiis", $userID, $product_name, $description, $stock, $categoryID, $price, $image_name);

    if ($stmt->execute()) {
        header("Location: view_product.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Get categories from DB
$categories = [];
$result = $conn->query("SELECT categoryID, name FROM category");
while ($row = $result->fetch_assoc()) $categories[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- ✅ CRITICAL for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>➕ Add Product - Seller Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --card-bg: #fff;
            --page-bg: #f0f6ff;
            --brand: #004080;
            --btn: #007bff;
            --btn-hover: #0056b3;
            --shadow: 0 6px 18px rgba(0,0,0,0.15);
        }

        body { 
            font-family: Arial, sans-serif; 
            background: var(--page-bg); 
            margin: 0; 
            padding: 0; 
        }

        /* Base container: fluid with a safe max-width */
        .container { 
            width: 100%;
            max-width: 700px;
            margin: 50px auto; 
            background: var(--card-bg); 
            border-radius: 12px; 
            box-shadow: var(--shadow);
            padding: 30px; 
            box-sizing: border-box;
        }

        h2 { 
            text-align: center; 
            color: var(--brand); 
            margin-bottom: 25px; 
        }

        form { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
        }

        label { 
            font-weight: bold; 
            color: #333; 
            margin-bottom: 5px; 
        }

        input[type="text"], 
        input[type="number"], 
        select, 
        textarea {
            padding: 10px; 
            border-radius: 8px; 
            border: 1px solid #ccc; 
            outline: none;
            font-size: 14px; 
            width: 100%; 
            box-sizing: border-box;
        }

        textarea { 
            resize: vertical; 
            min-height: 100px; 
        }

        input[type="file"] { 
            padding: 5px; 
        }

        button {
            padding: 12px; 
            border: none; 
            border-radius: 8px; 
            background: var(--btn);
            color: white; 
            font-size: 16px; 
            cursor: pointer; 
            transition: 0.3s;
            width: 100%;
        }

        button:hover { 
            background: var(--btn-hover); 
        }

        .back-btn {
            text-decoration: none; 
            display: inline-block; 
            margin-bottom: 20px; 
            color: #007bff;
            font-weight: bold; 
            transition: 0.3s;
        }

        .back-btn:hover { 
            color: #0056b3; 
        }

        /* ---------- Tablet (≤ 768px) ---------- */
        /* Medium screens (tablet) */
/* Medium screens (tablet) */
/* Medium screens (tablet) */
@media (max-width: 768px) {
    .container {
        max-width: 85%;
        margin: 20px auto;
        padding: 16px;
    }
    h2 { font-size: 18px; }
    input[type="text"], input[type="number"], select, textarea { font-size: 13px; padding: 7px; }
    button { font-size: 13px; padding: 9px; }
    .back-btn { font-size: 13px; }
}

/* Small screens (mobile) */
@media (max-width: 480px) {
    .container {
        max-width: 90%;   /* leaves more background visible */
        margin: 12px auto;
        padding: 12px;
    }

    h2 { font-size: 16px; }
    label { font-size: 12px; }

    input[type="text"],
    input[type="number"],
    select,
    textarea,
    input[type="file"],
    button {
        width: 100%;
        font-size: 12px;
        padding: 6px;
    }
}

/* Ultra-small devices (under 320px) */
@media (max-width: 320px) {
    .container {
        max-width: 92%;   /* don’t stretch edge-to-edge */
        margin: 8px auto;
        padding: 10px;
    }

    h2 { font-size: 15px; }
    label { font-size: 11px; }

    input[type="text"],
    input[type="number"],
    select,
    textarea,
    input[type="file"],
    button {
        width: 100%;
        font-size: 11px;
        padding: 5px;
    }
}




        /* ---------- Super tiny (≤ 250px) ---------- */
        @media (max-width: 250px) {
            .container {
                margin: 8px 6px !important;
                padding: 12px !important;
                width: calc(100% - 12px) !important;
                max-width: 250px !important;
            }
            h2 { font-size: 10px !important; }
            label { font-size: 12px !important; }
            input[type="text"],
            input[type="number"],
            select,
            textarea,
            input[type="file"],
            button {
                font-size: 15px !important;
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="S-HomePage.php" class="back-btn">← Back to Products</a>
    <h2>➕ Add Product</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Product Name:</label>
        <input type="text" name="product_name" required>

        <label>Description:</label>
        <textarea name="description" required></textarea>

        <label>Stock:</label>
        <input type="number" name="stock" required>

        <label>Category:</label>
        <select name="categoryID" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['categoryID'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Price (₱):</label>
        <input type="number" step="1" name="price" required>

        <label>Product Image:</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">Add Product</button>
    </form>
</div>

</body>
</html>