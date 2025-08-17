<?php
session_start();
include '../db.php'; // DB connection

// Make sure user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

$userID = $_SESSION['userID'];

/* ---------------------- AJAX for dependent dropdowns ---------------------- */
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'regions') {
        $rows = [];
        $res = $conn->query("SELECT DISTINCT region FROM location ORDER BY region");
        while ($r = $res->fetch_assoc()) $rows[] = $r['region'];
        echo json_encode($rows);
        exit();
    }

    if ($_GET['action'] === 'provinces' && isset($_GET['region'])) {
        $rows = [];
        $sql = "SELECT DISTINCT province FROM location WHERE region = ? ORDER BY province";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $_GET['region']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = $r['province'];
        echo json_encode($rows);
        exit();
    }

    if ($_GET['action'] === 'cities' && isset($_GET['region'], $_GET['province'])) {
        $rows = [];
        $sql = "SELECT DISTINCT city FROM location WHERE region = ? AND province = ? ORDER BY city";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $_GET['region'], $_GET['province']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = $r['city'];
        echo json_encode($rows);
        exit();
    }

    echo json_encode([]);
    exit();
}
/* ------------------------------------------------------------------------- */

// 🔹 Get user & current location (if any)
$sqlUser = "
    SELECT u.role, u.name, u.profile, u.locationID, l.region, l.province, l.city
    FROM users u
    LEFT JOIN location l ON u.locationID = l.id
    WHERE u.userID = ?
";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userID);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();

if (!$userData || $userData['role'] !== 'Buyer') {
    echo "❌ Only Buyer can edit profiles.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $params = [];
    $types  = "";

    // Name (optional)
    if (isset($_POST['name']) && $_POST['name'] !== '') {
        $updates[] = "name = ?";
        $params[] = $_POST['name'];
        $types .= "s";
    }

    // Profile Picture (optional)
    if (isset($_FILES['profilepicture']) && $_FILES['profilepicture']['error'] === UPLOAD_ERR_OK) {
        // Add file validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileType = $_FILES['profilepicture']['type'];
        $fileSize = $_FILES['profilepicture']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            die("Error: Invalid file type. Only JPG, PNG and GIF are allowed.");
        }

        if ($fileSize > $maxSize) {
            die("Error: File size too large. Maximum size is 5MB.");
        }

        // Fix upload directory path
        $uploadDir = __DIR__ . '../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['profilepicture']['tmp_name'];
        $fileName = uniqid('profile_') . '.' . pathinfo($_FILES['profilepicture']['name'], PATHINFO_EXTENSION);
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $updates[] = "profile = ?";
            $params[] = 'uploads/profiles/' . $fileName; // Fix path for database storage
            $types .= "s";
        }
    }

    // Password (optional)
    if (!empty($_POST['password'])) {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $hashed;
        $types .= "s";
    }

    // Region/Province/City (each optional; only update if all set)
    $region   = isset($_POST['region']) ? trim($_POST['region']) : '';
    $province = isset($_POST['province']) ? trim($_POST['province']) : '';
    $city     = isset($_POST['city']) ? trim($_POST['city']) : '';

    if ($region !== '' && $province !== '' && $city !== '') {
        $sqlFind = "SELECT id FROM location WHERE region = ? AND province = ? AND city = ? LIMIT 1";
        $stmt = $conn->prepare($sqlFind);
        $stmt->bind_param("sss", $region, $province, $city);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && isset($res['id'])) {
            $updates[] = "locationID = ?";
            $params[] = (int)$res['id'];
            $types .= "i";
        }
    }

    // Apply updates if provided
    if (!empty($updates)) {
        $sqlUpd = "UPDATE users SET " . implode(", ", $updates) . " WHERE userID = ? AND role = 'Buyer'";
        $stmt = $conn->prepare($sqlUpd);
        $types .= "i";
        $params[] = $userID;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }

    header("Location: B-HomePage.php");
    exit();
}

// For JS preselect
$currentRegion   = $userData['region']   ?? '';
$currentProvince = $userData['province'] ?? '';
$currentCity     = $userData['city']     ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4A90E2, #50E3C2);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            min-height: 100vh;
        }
        .form-container {
            background: #fff;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
        }
        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            color: #444;
        }
        input, select {
            width: 100%;
            max-width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            border-radius: 6px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-save { background: #4A90E2; color: #fff; }
        .btn-save:hover { background: #357ABD; }
        .btn-back { background: #ccc; color: #333; }
        .btn-back:hover { background: #aaa; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>">

        <label>Profile Picture:</label>
        <input type="file" name="profilepicture" accept="image/*">

        <label>Password:</label>
        <input type="password" name="password">

        <label>Region:</label>
        <select name="region" id="region">
            <option value="">-- Select Region --</option>
        </select>

        <label>Province:</label>
        <select name="province" id="province">
            <option value="">-- Select Province --</option>
        </select>

        <label>City:</label>
        <select name="city" id="city">
            <option value="">-- Select City --</option>
        </select>

        <button type="submit" class="btn btn-save">Save Profile</button>
    </form>

    <form action="B-HomePage.php" method="get">
        <button type="submit" class="btn btn-back">Back</button>
    </form>
</div>

<script>
// Current selections from PHP (for preselecting)
const CURRENT = {
    region:   <?= json_encode($currentRegion) ?>,
    province: <?= json_encode($currentProvince) ?>,
    city:     <?= json_encode($currentCity) ?>
};

const regionSel   = document.getElementById('region');
const provinceSel = document.getElementById('province');
const citySel     = document.getElementById('city');

async function fetchJSON(url) {
    const res = await fetch(url, {cache: 'no-store'});
    if (!res.ok) return [];
    return res.json();
}

function setOptions(selectEl, items, placeholder) {
    selectEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    selectEl.appendChild(opt);

    items.forEach(v => {
        const o = document.createElement('option');
        o.value = v;
        o.textContent = v;
        selectEl.appendChild(o);
    });
}

async function loadRegions() {
    const regions = await fetchJSON('?action=regions');
    setOptions(regionSel, regions, '-- Select Region --');
    if (CURRENT.region && regions.includes(CURRENT.region)) {
        regionSel.value = CURRENT.region;
        await loadProvinces(); // then provinces & cities
    }
}

async function loadProvinces() {
    const region = regionSel.value;
    setOptions(provinceSel, [], '-- Select Province --');
    setOptions(citySel, [], '-- Select City --');
    if (!region) return;

    const provinces = await fetchJSON(`?action=provinces&region=${encodeURIComponent(region)}`);
    setOptions(provinceSel, provinces, '-- Select Province --');

    if (CURRENT.province && provinces.includes(CURRENT.province)) {
        provinceSel.value = CURRENT.province;
        await loadCities();
    }
}

async function loadCities() {
    const region = regionSel.value;
    const province = provinceSel.value;
    setOptions(citySel, [], '-- Select City --');
    if (!region || !province) return;

    const cities = await fetchJSON(`?action=cities&region=${encodeURIComponent(region)}&province=${encodeURIComponent(province)}`);
    setOptions(citySel, cities, '-- Select City --');

    if (CURRENT.city && cities.includes(CURRENT.city)) {
        citySel.value = CURRENT.city;
        // Clear after first preselect so subsequent changes don't override user choices
        CURRENT.city = '';
        CURRENT.province = '';
        CURRENT.region = '';
    }
}

regionSel.addEventListener('change', loadProvinces);
provinceSel.addEventListener('change', loadCities);

// Init
loadRegions();
</script>
</body>
</html>
