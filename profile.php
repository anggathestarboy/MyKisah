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
if (isset($_POST['save'])) {

    $name = trim($_POST['name']);
    $bio  = trim($_POST['bio']);

    if (!empty($_FILES['avatar']['name'])) {

        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $avatarName = time() . '.' . $ext;

        move_uploaded_file(
            $_FILES['avatar']['tmp_name'],
            'uploads/' . $avatarName
        );

        $pdo->prepare(
            "UPDATE users SET name=?, bio=?, avatar=? WHERE id=?"
        )->execute([$name, $bio, $avatarName, $me]);

    } else {

        $pdo->prepare(
            "UPDATE users SET name=?, bio=? WHERE id=?"
        )->execute([$name, $bio, $me]);
    }

    header("Location: profile.php");
    exit;
}

/* GET USER */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$me]);
$user = $stmt->fetch();
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-white flex items-center justify-center px-4">

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6">

    <!-- Header -->
    <div class="flex items-center mb-6">
        <!-- <a href="index.php" class="text-blue-600 font-semibold text-sm hover:underline">← Kembali</a> -->
        <h2 class="flex-1 text-center text-xl font-bold text-blue-700">Edit Profil</h2>
    </div>



    <!-- Form -->
<form method="POST" enctype="multipart/form-data" class="space-y-4">

    <!-- Avatar -->
    <div class="flex justify-center mb-6">
        <label for="avatar" class="relative group cursor-pointer">

            <?php if ($user['avatar']): ?>
                <img id="avatarPreview"
                     src="uploads/<?= htmlspecialchars($user['avatar']) ?>"
                     class="w-28 h-28 rounded-full object-cover border-4 border-blue-500">
            <?php else: ?>
                <div id="avatarPreview"
                     class="w-28 h-28 rounded-full bg-blue-600 text-white flex items-center justify-center text-4xl font-bold">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>

            <!-- Overlay -->
            <div class="absolute inset-0 rounded-full bg-black/40 text-white text-sm font-semibold
                        flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                Ubah Foto
            </div>

            <input type="file"
                   name="avatar"
                   id="avatar"
                   class="hidden"
                   accept="image/*"
                   onchange="previewAvatar(this)">
        </label>
    </div>

    <!-- Nama -->
    <div>
        <label class="text-sm font-semibold text-gray-700">Nama</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($user['name']) ?>"
               required
               class="w-full mt-1 px-4 py-2 rounded-lg border border-blue-100
                      focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Bio -->
    <div>
        <label class="text-sm font-semibold text-gray-700">Bio</label>
        <textarea name="bio" rows="4"
                  class="w-full mt-1 px-4 py-2 rounded-lg border border-blue-100 resize-none
                         focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
    </div>

    <button name="save"
            class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-500
                   text-white rounded-xl font-semibold
                   hover:shadow-lg hover:scale-[1.01] transition">
         Simpan Perubahan
    </button><br><br>
   <a href="index.php" style="color: red; margin-left:80px" > 
  
         
               << Kembali ke Home
                
</a>

</form>


</div>

<script>

               

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const avatar = document.getElementById('avatarPreview');

            if (avatar.tagName === 'IMG') {
                avatar.src = e.target.result;
            } else {
                avatar.innerHTML = '';
                avatar.style.backgroundImage = `url(${e.target.result})`;
                avatar.style.backgroundSize = 'cover';
                avatar.style.backgroundPosition = 'center';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>

