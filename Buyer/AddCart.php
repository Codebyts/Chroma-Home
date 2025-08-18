<?php
    session_start();
    include '../db.php';

    // Redirect to login if not log in page if userID is not set
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
    }
    $userID = $_SESSION['userID'];

    // Check if productID is passed
    if(isset($_POST['productID'])) {
        $productID = $_POST['productID'];

        $sqlEntryChecker = "SELECT * FROM cart WHERE userID = ? && productID = ?";
        $stmtEntryChecker = $conn->prepare($sqlEntryChecker);
        $stmtEntryChecker->bind_param("ii", $userID, $productID);

        if ($stmtEntryChecker->execute()) {
            $resultEntryChecker = $stmtEntryChecker->get_result();
            if ($resultEntryChecker->num_rows > 0) {
                header("Location: ProductDetails.php?productID=$productID&message=Product already in cart!");
                exit();
            } else {
                $sql = "INSERT INTO cart (userID, productID, quantity) 
                        VALUES (?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $userID, $productID);

                if ($stmt->execute()) {
                    header("Location: B-HomePage.php?open=cart&message=Product successfully added to cart!");
                    //Edit message variable for popup dialogue
                    exit();
                } else {
                    echo "Error: " . $stmt->error;
                }
            }
        } else {
            header("Location: ProductDetails.php?productID=$productID&message=Error checking cart entry. Please try again.");
            exit();
        }
    } else {
        header("Location: B-HomePage.php?message=Unknown error. Product not found!");
        exit();
    }    
?>