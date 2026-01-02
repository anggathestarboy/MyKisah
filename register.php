<?php
require 'db.php';
session_start();

if (isset($_POST['register'])) {
    $pdo->prepare("
        INSERT INTO users (name, username, password, created_at)
        VALUES (?,?,?,NOW())
    ")->execute([
        $_POST['name'],
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT)
    ]);

    header("Location: login.php");
    exit;
}
?>

<h3>Register</h3>
<form method="POST">
    <input name="name" placeholder="Nama" required><br>
    <input name="username" placeholder="Username" required><br>
    <input name="password" type="password" required><br>
    <button name="register">Register</button>
</form>

<a href="login.php">Login</a>
