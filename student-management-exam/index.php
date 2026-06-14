<?php
$host = getenv("DB_HOST") ?: "mysql";
$dbname = getenv("DB_NAME") ?: "exam_db";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "root";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            major VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = "";
$error = "";

if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? "";
    $name = trim($_POST["name"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $major = trim($_POST["major"] ?? "");

    if ($name === "" || $gender === "" || $email === "" || $phone === "" || $major === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match("/^[0-9+\-\s]{6,20}$/", $phone)) {
        $error = "Invalid phone number.";
    } else {
        try {
            if ($id !== "") {
                $stmt = $pdo->prepare("UPDATE students SET name=?, gender=?, email=?, phone=?, major=? WHERE id=?");
                $stmt->execute([$name, $gender, $email, $phone, $major, $id]);
                header("Location: index.php?msg=updated");
                exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO students (name, gender, email, phone, major) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $gender, $email, $phone, $major]);
                header("Location: index.php?msg=added");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Email already exists or database error.";
        }
    }
}

if (isset($_GET["msg"])) {
    if ($_GET["msg"] === "added") $message = "Student added successfully.";
    if ($_GET["msg"] === "updated") $message = "Student updated successfully.";
    if ($_GET["msg"] === "deleted") $message = "Student deleted successfully.";
}

$editStudent = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $editStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}

$search = trim($_GET["search"] ?? "");
if ($search !== "") {
    $stmt = $pdo->prepare("SELECT * FROM students 
                           WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR major LIKE ?
                           ORDER BY id DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Management System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 30px; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 25px; border-radius: 14px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        h1 { text-align: center; color: #1f2937; }
        form { margin-bottom: 20px; }
        input, select { padding: 10px; margin: 6px; width: 180px; border: 1px solid #ccc; border-radius: 8px; }
        button, .btn { padding: 10px 14px; border: none; border-radius: 8px; background: #2563eb; color: white; text-decoration: none; cursor: pointer; }
        .btn-delete { background: #dc2626; }
        .btn-edit { background: #16a34a; }
        .btn-clear { background: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #111827; color: white; }
        th, td { border: 1px solid #ddd; padding: 11px; text-align: center; }
        .success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; }
        .search-box { text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <h1>Student Management System</h1>

    <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editStudent["id"] ?? "") ?>">

        <input type="text" name="name" placeholder="Student Name"
               value="<?= htmlspecialchars($editStudent["name"] ?? "") ?>" required>

        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= (($editStudent["gender"] ?? "") === "Male") ? "selected" : "" ?>>Male</option>
            <option value="Female" <?= (($editStudent["gender"] ?? "") === "Female") ? "selected" : "" ?>>Female</option>
        </select>

        <input type="email" name="email" placeholder="Email"
               value="<?= htmlspecialchars($editStudent["email"] ?? "") ?>" required>

        <input type="text" name="phone" placeholder="Phone"
               value="<?= htmlspecialchars($editStudent["phone"] ?? "") ?>" required>

        <input type="text" name="major" placeholder="Major"
               value="<?= htmlspecialchars($editStudent["major"] ?? "") ?>" required>

        <button type="submit"><?= $editStudent ? "Update Student" : "Add Student" ?></button>

        <?php if ($editStudent): ?>
            <a class="btn btn-clear" href="index.php">Cancel</a>
        <?php endif; ?>
    </form>

    <form class="search-box" method="GET">
        <input type="text" name="search" placeholder="Search student..."
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a class="btn btn-clear" href="index.php">Reset</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Major</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>

        <?php if (count($students) > 0): ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student["id"]) ?></td>
                    <td><?= htmlspecialchars($student["name"]) ?></td>
                    <td><?= htmlspecialchars($student["gender"]) ?></td>
                    <td><?= htmlspecialchars($student["email"]) ?></td>
                    <td><?= htmlspecialchars($student["phone"]) ?></td>
                    <td><?= htmlspecialchars($student["major"]) ?></td>
                    <td><?= htmlspecialchars($student["created_at"]) ?></td>
                    <td>
                        <a class="btn btn-edit" href="index.php?edit=<?= $student["id"] ?>">Edit</a>
                        <a class="btn btn-delete" href="index.php?delete=<?= $student["id"] ?>"
                           onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No student found.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
