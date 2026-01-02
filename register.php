<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Kisah</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0088cc 0%, #34b7f1 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .telegram-blue {
            background-color: #0088cc;
        }
        .telegram-light-blue {
            background-color: #34b7f1;
        }
        .text-telegram-blue {
            color: #0088cc;
        }
        .border-telegram-blue {
            border-color: #0088cc;
        }
        .focus-ring:focus {
            ring-color: #0088cc;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-md w-full mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-white">My Kisah</h1>
            <p class="text-white opacity-90 mt-2">Buat akun baru untuk mulai menulis kisah Anda</p>
        </div>

        <!-- Register Form -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <?php
                require 'db.php';
                session_start();

                // Pesan sukses jika ada
                if (isset($_SESSION['register_success'])) {
                    echo '<div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">';
                    echo '<div class="flex items-center">';
                    echo '<i class="fas fa-check-circle mr-3"></i>';
                    echo '<span>' . $_SESSION['register_success'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                    unset($_SESSION['register_success']);
                }

                if (isset($_POST['register'])) {
                    // Validasi input
                    $errors = [];
                    
                    if (empty($_POST['name'])) {
                        $errors[] = "Nama harus diisi";
                    }
                    
                    if (empty($_POST['username'])) {
                        $errors[] = "Username harus diisi";
                    } else {
                        // Cek apakah username sudah ada
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$_POST['username']]);
                        if ($stmt->fetch()) {
                            $errors[] = "Username sudah digunakan";
                        }
                    }
                    
                    if (empty($_POST['password'])) {
                        $errors[] = "Password harus diisi";
                    } elseif (strlen($_POST['password']) < 6) {
                        $errors[] = "Password minimal 6 karakter";
                    }
                    
                    if (empty($errors)) {
                        // Insert data ke database
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, username, password, created_at)
                            VALUES (?,?,?,NOW())
                        ");
                        
                        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        
                        if ($stmt->execute([
                            $_POST['name'],
                            $_POST['username'],
                            $hashedPassword
                        ])) {
                            $_SESSION['register_success'] = "Pendaftaran berhasil! Silakan login.";
                            header("Location: login.php");
                            exit;
                        } else {
                            $errors[] = "Terjadi kesalahan saat mendaftar";
                        }
                    }
                    
                    // Tampilkan error jika ada
                    if (!empty($errors)) {
                        echo '<div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">';
                        echo '<div class="flex items-center">';
                        echo '<i class="fas fa-exclamation-circle mr-3"></i>';
                        echo '<div>';
                        echo '<p class="font-semibold">Terjadi kesalahan:</p>';
                        echo '<ul class="list-disc ml-5 mt-1">';
                        foreach ($errors as $error) {
                            echo '<li>' . htmlspecialchars($error) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>

                <form method="POST" class="space-y-6">
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user-circle mr-2 text-telegram-blue"></i>Nama Lengkap
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                id="name" 
                                name="name" 
                                type="text" 
                                required 
                                placeholder="Masukkan nama lengkap Anda"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Nama akan ditampilkan di profil Anda</p>
                    </div>

                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-at mr-2 text-telegram-blue"></i>Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tag text-gray-400"></i>
                            </div>
                            <input 
                                id="username" 
                                name="username" 
                                type="text" 
                                required 
                                placeholder="Masukkan username unik"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Username digunakan untuk login</p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-telegram-blue"></i>Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                placeholder="Buat password yang kuat"
                                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                      <br>
                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                       
                    </div>

                    <!-- Register Button -->
                    <button 
                        name="register" 
                        type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Daftar Sekarang
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-8 mb-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">Sudah punya akun?</span>
                    </div>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <a 
                        href="login.php" 
                        class="inline-flex items-center justify-center w-full py-3 px-4 border border-blue-500 rounded-xl shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login Sekarang
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-6 rounded-b-2xl">
                <div class="text-center text-sm text-gray-600">
                    <p>© 2023 My Kisah. All rights reserved.</p>
                    <p class="mt-1">Bergabunglah dengan komunitas penulis kisah terbesar di Indonesia</p>
                </div>
            </div>
        </div>

        <!-- Back to Home Link -->
        <div class="text-center mt-8">
            <a href="index.php" class="inline-flex items-center text-white hover:text-gray-200 transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Beranda
            </a>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            // Change color based on strength
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#ef4444'; // red
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#f59e0b'; // amber
            } else {
                strengthBar.style.backgroundColor = '#10b981'; // emerald
            }
        });
        
   
    </script>
</body>
</html>