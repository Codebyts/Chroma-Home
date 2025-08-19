<?php
    session_start();
    include '../../db.php';

    if (!isset($_SESSION['userID'])) {
        header("Location: login.php");
        exit();
    }

    $userID = $_SESSION['userID'];

    // Fetch profile image for logged-in user
    $sqlProfile = "SELECT profile FROM users WHERE userID = ?";
    $stmtProfile = $conn->prepare($sqlProfile);
    $stmtProfile->bind_param("i", $userID);
    $stmtProfile->execute();
    $resultProfile = $stmtProfile->get_result();
    $userData = $resultProfile->fetch_assoc();

    $profileImage = (!empty($userData['profile'])) ? $userData['profile'] : "uploads/profiles/default.png";


    $sql = "SELECT p.* 
            FROM favorites f
            JOIN product p ON f.productID = p.productID
            WHERE f.userID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>

    <!-- Bootstrap and font awesome -->
    <link rel="stylesheet" href="../../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../GlobalFile/fontawesome-free-7.0.0-web/css/all.min.css">

    <!-- Global styles -->
    <link rel="stylesheet" href="../../GlobalFile/global.css">
    <link rel="stylesheet" href="../../GlobalFile/nav-side.css">

    <!-- Home Page styles -->
    <link rel="stylesheet" href="../styles/FavP-style.css">
</head>

<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <ul class="side-menu">
            <li><a href="../B-HomePage.php" ><i class="fas fa-solid fa-house" style="color: #ffffff;"></i> Home</a></li>
            <li><a href="../MessagePage/MessagePage.php"><i class="fas fa-solid fa-message"
                        style="color: #ffffff;"></i> Message</a></li>
            <li><a href="Favorites.php" class="active"><i class="fas fa-solid fa-heart" style="color: #ffffff;"></i>
                    Favorite</a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <nav>
            <button class="toggle-sidebar btn btn-outline-dark">☰</button>
            <form action="#">
                <div class="form-group">
                    <input type="text" placeholder="Search...">
                    <i class="fas fa-solid fa-magnifying-glass"></i>
                </div>
            </form>

            <span class="divider"></span>
            <div class="profile">
    <img src="../<?php echo htmlspecialchars($profileImage); ?>" 
     alt="Profile" class="rounded-circle" 
     style="width:40px; height:40px; object-fit:cover;">

    <ul class="profile-link">
        <li><a href="../B-EditProfile.php"><i class="fas fa-regular fa-circle-user"></i> Profile</a></li>
        <li><a href="../../index.php"><i class="fas fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main class="container-fluid py-3">
            <!-- Product Cards -->
            <div class="row g-3 mt-4">
                <?php $_SESSION['previous-page'] = 'favorites';
                while ($row = $result->fetch_assoc()): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100 text-center p-2">
                            <a href="../ProductDetails.php?productID=<?php echo $row['productID']; ?>">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="Product" class="img-fluid rounded">
                                <?php else: ?>
                                    <img src="../uploads/products/no-image.png" alt="No Image">
                                <?php endif; ?>
                                <hr>
                                <h2><?php echo htmlspecialchars($row['product_name']); ?></h2>
                                <?php if ($row['stock'] === 0) { ?>
                                    <p class="text-danger">OUT OF STOCK</p>
                                <?php } else { ?>
                                    <p>₱<?php echo number_format($row['price'], 2); ?></p>
                                <?php } ?>
                            </a>
                            <form action="AddFavorite.php" method="post">
                                <input type="hidden" name="productID" value="<?php echo $row['productID']; ?>">
                                <input type="hidden" name="status" value="favorite">
                                <button>Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <!-- End of Product Cards -->
            <!-- End of Product Cards -->
        </main>
    </section>

    <script src="../../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css"></script>
    <script src="../../GlobalFile/fontawesome-free-7.0.0-web/js/all.js"></script>
    <script src="../../GlobalFile/nav-side.js"></script>
</body>

</html>