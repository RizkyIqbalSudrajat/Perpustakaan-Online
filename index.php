<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpus Online - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* CSS SAMA PERSIS (TEMA COKLAT) */
        :root {
            --bg-color: #efebe9;
            --card-bg: #ffffff;
            --primary-color: #5d4037;
            --primary-hover: #3e2723;
            --text-color: #4e342e;
            --muted-text: #8d6e63;
            --input-bg: #f5f5f5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; color: var(--text-color); }
        .container { background-color: var(--card-bg); width: 100%; max-width: 400px; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(93, 64, 55, 0.15); }
        .title { font-family: 'Playfair Display', serif; font-size: 32px; text-align: center; color: var(--primary-color); margin-bottom: 10px; }
        .subtitle { text-align: center; font-size: 14px; color: var(--muted-text); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; background-color: var(--input-bg); outline: none; transition: 0.3s; }
        .form-group input:focus { border-color: var(--primary-color); }
        .btn { width: 100%; padding: 12px; background-color: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn:hover { background-color: var(--primary-hover); }
        .switch-text { text-align: center; margin-top: 20px; font-size: 14px; }
        .switch-text a { color: var(--primary-color); font-weight: 600; cursor: pointer; text-decoration: none; }
        .hidden { display: none; }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="title">Perpus Online</h1>
        <p class="subtitle" id="page-subtitle">Gerbang Pengetahuan Anda</p>

        <?php if(isset($_GET['error'])): ?>
            <script>
                // Tampilkan Pop Up
                alert("Email atau Password Salah!");
                
                // (Opsional) Membersihkan URL agar jika direfresh, error tidak muncul lagi
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            </script>
        <?php endif; ?>

        <div id="login-form">
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email / Username</label>
                    <input type="text" id="email" name="email" placeholder="Masukkan email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn">Masuk</button>
            </form>
            <div class="switch-text">
                Belum punya akun? <a onclick="toggleForm()">Daftar disini</a>
            </div>
        </div>

        <div id="signup-form" class="hidden">
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="fullname" placeholder="Nama lengkap Anda" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@contoh.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Buat password baru" required>
                </div>
                <button type="submit" class="btn">Daftar Sekarang</button>
            </form>
            <div class="switch-text">
                Sudah punya akun? <a onclick="toggleForm()">Login disini</a>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const subtitle = document.getElementById('page-subtitle');

            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                subtitle.innerText = "Gerbang Pengetahuan Anda";
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                subtitle.innerText = "Bergabung menjadi Anggota Baru";
            }
        }
    </script>
</body>
</html>