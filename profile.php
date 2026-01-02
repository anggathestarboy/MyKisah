<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = $_SESSION['user_id'];

/* UPDATE LAST SEEN */
$pdo->prepare("UPDATE users SET last_seen=NOW() WHERE id=?")->execute([$me]);

/* UPDATE PROFILE */
/* UPDATE PROFILE */
if (isset($_POST['save'])) {

    $bio = $_POST['bio'];
    $avatarName = null;

    if (!empty($_FILES['avatar']['name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $avatarName = time().'.'.$ext;

        // simpan file ke folder uploads
        move_uploaded_file(
            $_FILES['avatar']['tmp_name'],
            'uploads/'.$avatarName
        );

        // SIMPAN HANYA NAMA FILE
        $pdo->prepare("UPDATE users SET bio=?, avatar=? WHERE id=?")
            ->execute([$bio, $avatarName, $me]);
    } else {
        $pdo->prepare("UPDATE users SET bio=? WHERE id=?")
            ->execute([$bio, $me]);
    }

    header("Location: profile.php");
    exit;
}

/* GET USER */
$user = $pdo->query("SELECT * FROM users WHERE id=$me")->fetch();
?>

<h2>Edit Profile</h2>

<form method="POST" enctype="multipart/form-data">
    <p>Nama: <b><?= $user['name'] ?></b></p>

    <p>Bio:</p>
    <textarea name="bio" rows="4" cols="30"><?= $user['bio'] ?></textarea>

    <p>Avatar:</p>
    <?php if ($user['avatar']): ?>
    <img src="uploads/<?= $user['avatar'] ?>" width="80"><br>
<?php endif; ?>

    <input type="file" name="avatar">

    <br><br>
    <button name="save">Simpan</button>
</form>

<br>
<a href="index.php">← Kembali ke Chat</a>
