<?php require_once('config/config.php'); ?>
<?php 
if(isset($_SESSION['username']))
    echo '<meta http-equiv="refresh" content="0;url=/">';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng Ký Tài Khoản - <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        :root {
            --bg-primary: #07090e;
            --bg-card: rgba(17, 24, 39, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-hover: rgba(245, 158, 11, 0.4);
            --accent-color: #f59e0b;
            --accent-hover: #d97706;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --input-bg: rgba(31, 41, 55, 0.6);
        }

        body { 
            background-color: var(--bg-primary); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px;
            overflow-x: hidden;
        }

        /* Layout chia đôi 2 cột */
        .split-container {
            width: 100%;
            max-width: 1050px;
            min-height: 640px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8);
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* Cột Trái: Image / Banner & Hiệu ứng */
        .split-left {
            flex: 1;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(7, 9, 14, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1000&auto=format&fit=crop') center/cover no-repeat;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            color: #fff;
            border-right: 1px solid var(--border-glass);
        }

        .split-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(7, 9, 14, 0.75);
            z-index: 1;
        }

        .split-left > * {
            position: relative;
            z-index: 2;
        }

        .left-brand {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
        }

        .left-brand i { color: var(--accent-color); }
        .left-brand span { color: var(--accent-color); }

        .left-content h2 {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .left-content h2 span { color: var(--accent-color); }

        .left-content p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .left-features {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #d1d5db;
        }

        .feature-item i {
            color: var(--accent-color);
            background: rgba(245, 158, 11, 0.1);
            padding: 6px;
            border-radius: 50%;
        }

        .left-footer {
            font-size: 0.8rem;
            color: #6b7280;
        }

        /* Cột Phải: Form Đăng Ký */
        .split-right {
            flex: 1;
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(17, 24, 39, 0.5);
            max-height: 100vh;
            overflow-y: auto;
        }

        .form-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* Nút đăng nhập mạng xã hội */
        .social-login {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-social {
            background: var(--input-bg);
            border: 1px solid var(--border-glass);
            padding: 10px;
            border-radius: 12px;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-social:hover {
            background: rgba(55, 65, 81, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-social.google i { color: #ea4335; }
        .btn-social.facebook i { color: #1877f2; }
        .btn-social.github i { color: #e5e7eb; }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #4b5563;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-glass);
        }

        .divider::before { margin-right: 10px; }
        .divider::after { margin-left: 10px; }

        /* Input Styles */
        .input-group-custom { margin-bottom: 16px; }
        .input-group-custom label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px; }
        
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 14px; color: #6b7280; font-size: 0.9rem; }
        
        .form-control-custom { 
            width: 100%; 
            background: var(--input-bg); 
            border: 1px solid var(--border-glass); 
            padding: 12px 14px 12px 40px; 
            font-size: 0.9rem; 
            color: #ffffff; 
            border-radius: 12px; 
            outline: none; 
            transition: all 0.3s ease; 
        }
        
        .form-control-custom::placeholder { color: #4b5563; }
        .form-control-custom:focus { 
            border-color: var(--accent-color); 
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); 
        }

        .toggle-password { position: absolute; right: 14px; background: none; border: none; color: #6b7280; cursor: pointer; font-size: 0.9rem; }
        .toggle-password:hover { color: #fff; }

        /* Password strength */
        .strength-meter-container { margin-top: 6px; display: none; }
        .strength-bar { height: 3px; width: 100%; background: #374151; border-radius: 2px; display: flex; gap: 3px; overflow: hidden; }
        .strength-segment { flex: 1; height: 100%; background: transparent; transition: background 0.3s; }
        .strength-text { font-size: 0.7rem; color: var(--text-muted); margin-top: 3px; text-align: right; }

        /* Submit Button */
        .btn-submit { 
            width: 100%; 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            color: #07090e; 
            border: none; 
            padding: 13px; 
            font-size: 0.95rem; 
            font-weight: 800; 
            border-radius: 12px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            box-shadow: 0 8px 20px -4px rgba(245, 158, 11, 0.4); 
            margin-top: 5px; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { transform: translateY(-1px); filter: brightness(1.05); }

        .register-link { text-align: center; margin-top: 18px; font-size: 0.85rem; color: var(--text-muted); }
        .register-link a { color: var(--accent-color); font-weight: 700; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        /* Responsive Mobile */
        @media (max-width: 850px) {
            .split-container { flex-direction: column; max-width: 480px; }
            .split-left { display: none; }
            .split-right { padding: 35px 25px; }
        }
    </style>
</head>
<body>

<?php            
if(isset($_POST['submit'])) {
    $username = check_string($_POST['username']);
    $password = check_string($_POST['password']);
    $email = check_string($_POST['email']);
    $ref_by = isset($_GET['ref']) ? check_string($_GET['ref']) : '';

    $check_acc = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username' ");
    $check_mail = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$email' ");

    if($username == "" || $password == "" || $email == "") { 
        echo '<script>swal("", "Vui Lòng Nhập Đầy Đủ Thông Tin", "warning"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
    } else if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) { 
        echo '<script>swal("", "Tên Đăng Nhập Không Được Phép Chứa Ký Tự Đặc Biệt", "warning"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
    } else if ($check_acc->num_rows != 0) { 
        echo '<script>swal("", "Tên Đăng Nhập Đã Tồn Tại", "error"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
    } else if ($check_mail->num_rows != 0) { 
        echo '<script>swal("", "Email Đã Tồn Tại", "error"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];  
        $pass = md5($password);
        
        $luuacc = $ketnoi->query("INSERT INTO `users` SET  
            `password` = '$pass',
            `username` = '$username',
            `email` = '$email',
            `level` = '0',
            `tong_nap` = '0',
            `money` = '0',
            `ref_money` = '0',
            `ref_by` = '$ref_by',
            `bannd` = '0',
            `ip` = '".$ip."',
            `time` = '$time' ");
            
        if ($luuacc) { 
            $_SESSION['username'] = $username;
            echo '<script>swal("", "Đăng Ký Thành Công", "success"); setTimeout(function(){ location.href = "/" }, 1200);</script>';    
        } else { 
            echo '<script>swal("", "Lỗi Đăng Ký Hệ Thống", "error"); setTimeout(function(){ location.href = "" }, 1200);</script>';            
        }
    }
}
?>

    <div class="split-container">
        <!-- CỘT TRÁI: BANNER & IMAGE MÔ TẢ -->
        <div class="split-left">
            <div>
                <a href="/" class="left-brand"><i class="fas fa-bolt"></i> <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTien<span>Fast</span>'; ?></a>
            </div>
            <div class="left-content">
                <h2>Hệ Thống Kiếm Tiền <span>Tự Động</span> Hàng Đầu</h2>
                <p>Tham gia ngay cộng đồng kiếm tiền online uy tín, rút tiền siêu tốc chỉ trong vài nốt nhạc.</p>
                <div class="left-features">
                    <div class="feature-item"><i class="fas fa-check"></i> Nhiệm vụ đa dạng, cập nhật liên tục</div>
                    <div class="feature-item"><i class="fas fa-check"></i> Thanh toán tự động 24/7 (Momo, Ngân hàng, Thẻ cào)</div>
                    <div class="feature-item"><i class="fas fa-check"></i> Bảo mật thông tin tuyệt đối 100%</div>
                </div>
            </div>
            <div class="left-footer">
                © 2026 <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?>. All rights reserved.
            </div>
        </div>

        <!-- CỘT PHẢI: FORM ĐĂNG KÝ & MẠNG XÃ HỘI -->
        <div class="split-right">
            <div class="form-header">
                <h1>Tạo tài khoản</h1>
                <p>Đăng ký nhanh qua mạng xã hội hoặc điền thông tin bên dưới</p>
            </div>

            <!-- Các phương thức đăng nhập mạng xã hội -->
            <div class="social-login">
                <a href="/login-google" class="btn-social google"><i class="fab fa-google"></i> Google</a>
                <a href="/login-facebook" class="btn-social facebook"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="/login-github" class="btn-social github"><i class="fab fa-github"></i> Github</a>
            </div>

            <div class="divider">hoặc bằng email</div>

            <form action="" method="POST" autocomplete="off">
                <div class="input-group-custom">
                    <label>Tên tài khoản</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" placeholder="Nhập tên tài khoản" name="username" class="form-control-custom" required>
                    </div>
                </div>
                <div class="input-group-custom">
                    <label>Địa chỉ Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" placeholder="name@example.com" name="email" class="form-control-custom" required>
                    </div>
                </div>
                <div class="input-group-custom">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" class="form-control-custom" required oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePass()"><i class="far fa-eye" id="toggleIcon"></i></button>
                    </div>
                    <div class="strength-meter-container" id="strengthContainer">
                        <div class="strength-bar">
                            <div class="strength-segment" id="seg1"></div>
                            <div class="strength-segment" id="seg2"></div>
                            <div class="strength-segment" id="seg3"></div>
                            <div class="strength-segment" id="seg4"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Độ mạnh mật khẩu</div>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn-submit">Đăng ký ngay <i class="fas fa-arrow-right"></i></button>
                <div class="register-link">Đã có tài khoản? <a href="/dang-nhap">Đăng nhập</a></div>
            </form>
        </div>
    </div>

    <script>
        function togglePass() {
            var passInput = document.getElementById("password");
            var icon = document.getElementById("toggleIcon");
            if (passInput.type === "password") { 
                passInput.type = "text"; 
                icon.className = "far fa-eye-slash"; 
            } else { 
                passInput.type = "password"; 
                icon.className = "far fa-eye"; 
            }
        }

        function checkPasswordStrength(password) {
            var container = document.getElementById("strengthContainer");
            var text = document.getElementById("strengthText");
            if (password.length > 0) {
                container.style.display = "block";
            } else {
                container.style.display = "none";
                return;
            }

            var score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score++;

            var segs = [
                document.getElementById("seg1"),
                document.getElementById("seg2"),
                document.getElementById("seg3"),
                document.getElementById("seg4")
            ];

            segs.forEach(s => s.style.background = "#374151");
            var colors = ["#ef4444", "#f59e0b", "#3b82f6", "#10b981"];
            var labels = ["Rất yếu", "Yếu", "Khá mạnh", "Rất mạnh"];

            for (var i = 0; i < score; i++) {
                segs[i].style.background = colors[score - 1];
            }
            text.textContent = labels[score - 1] || "Yếu";
            text.style.color = colors[score - 1] || "#ef4444";
        }
    </script>
</body>
</html>