<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telegram Style</title>
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
            <p class="text-white opacity-90 mt-2">Silakan masuk untuk melanjutkan</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <?php
                require 'db.php';
                session_start();

                if (isset($_POST['login'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
                    $stmt->execute([$_POST['username']]);
                    $u = $stmt->fetch();

                    if ($u && password_verify($_POST['password'], $u['password'])) {
                        $_SESSION['user_id'] = $u['id'];
                        header("Location: index.php");
                        exit;
                    } else {
                        echo '<div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">';
                        echo '<div class="flex items-center">';
                        echo '<i class="fas fa-exclamation-circle mr-3"></i>';
                        echo '<span>Login gagal. Periksa kembali username dan password Anda.</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>

                <form method="POST" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-telegram-blue"></i>Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                id="username" 
                                name="username" 
                                type="text" 
                                required 
                                placeholder="Masukkan username Anda"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-telegram-blue"></i>Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                placeholder="Masukkan password Anda"
                                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

            

                    <!-- Login Button -->
                    <button 
                        name="login" 
                        type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Masuk
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-8 mb-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">Belum punya akun?</span>
                    </div>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <a 
                        href="register.php" 
                        class="inline-flex items-center justify-center w-full py-3 px-4 border border-blue-500 rounded-xl shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Register Now
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-6 rounded-b-2xl">
                <div class="text-center text-sm text-gray-600">
                    <p>© 2023 My Kisah. All rights reserved.</p>
                    <p class="mt-1">Dengan masuk, Anda menyetujui <a href="#" class="text-blue-600 hover:text-blue-500 font-medium">Syarat & Ketentuan</a> kami.</p>
                </div>
            </div>
        </div>

        
    </div>

    <!-- JavaScript for toggle password visibility -->
    <script>
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
    </script>
</body>
</html>