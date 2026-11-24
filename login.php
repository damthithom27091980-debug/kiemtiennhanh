<?php require_once('config/config.php'); ?>
<?php 
if(isset($_SESSION['username'])) {
    echo '<meta http-equiv="refresh" content="0;url=/">';
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng Nhập - <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

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
            position: relative;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.08) 0%, transparent 40%);
        }

        /* Layout chia đôi 2 cột tương đồng trang đăng ký */
        .split-container {
            width: 100%;
            max-width: 1050px;
            min-height: 600px;
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

        /* Cột Trái: Banner & Giới thiệu */
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

        /* Cột Phải: Form Đăng Nhập */
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

        .badge-welcome {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-color);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 14px;
            border: 1px solid rgba(245, 158, 11, 0.25);
            width: fit-content;
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

        /* Input Controls */
        .input-group-custom {
            margin-bottom: 16px;
        }

        .input-group-custom label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control-custom {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border-glass);
            padding: 12px 14px 12px 16px;
            font-size: 0.9rem;
            color: #ffffff;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control-custom::placeholder {
            color: #4b5563;
        }

        .form-control-custom:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .toggle-password:hover {
            color: #fff;
        }

        .forgot-pass {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .forgot-pass a {
            font-size: 0.82rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-pass a:hover {
            color: var(--accent-color);
        }

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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .register-link a {
            color: var(--accent-color);
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .card-footer-info {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .card-footer-info .stars {
            color: var(--accent-color);
        }

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
if (isset($_POST['submit'])) {
    $account  = check_string($_POST['username']); 
    $password = check_string($_POST['password']);

    if ($account == "" || $password == "") { 
        echo '<script>swal("", "Vui Lòng Nhập Đầy Đủ Thông Tin", "warning"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
    } else {
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $check = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$account'")->fetch_array();
        } else {
            if (!preg_match("/^[a-zA-Z0-9_]*$/", $account)) { 
                echo '<script>swal("", "Tên Đăng Nhập Không Được Phép Chứa Ký Tự Đặc Biệt", "warning"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
                exit();
            }
            $check = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$account'")->fetch_array();
        }

        if (empty($check)) { 
            echo '<script>swal("", "Tài khoản hoặc Email không tồn tại", "error"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
        } else if ($check['password'] != md5($password)) { 
            echo '<script>swal("", "Mật Khẩu Không Chính Xác", "error"); setTimeout(function(){ location.href = "" }, 1200);</script>'; 
        } else {
            $username = $check['username'];
            $_SESSION['username'] = $username;

            sendTele(templateTele(' Người Dùng '. $username .' Vừa Đăng Nhập Thành Công Vào Hệ Thống Với Địa Chỉ IP Là '. $ipme));
            mysqli_query($ketnoi, "INSERT INTO `log` SET `noidung`= 'Người Dùng ".$username." Vừa Đăng Nhập Thành Công Vào Hệ Thống ', `time`= now(), `ip`= '$ipme', `status`= 'admin'");
            echo '<script>swal("", "Đăng Nhập Thành Công", "success"); setTimeout(function(){ location.href = "/" }, 1200);</script>'; 
        }
    }
}
?>

    <div class="split-container">
        <!-- CỘT TRÁI: BANNER & GIỚI THIỆU -->
        <div class="split-left">
            <div>
                <a href="/" class="left-brand"><i class="fas fa-bolt"></i> <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTien<span>Fast</span>'; ?></a>
            </div>
            <div class="left-content">
                <h2>Chào Mừng Trở Lại <span>Hệ Thống</span></h2>
                <p>Tiếp tục hành trình kiếm thu nhập trực tuyến của bạn cùng hàng ngàn thành viên khác.</p>
                <div class="left-features">
                    <div class="feature-item"><i class="fas fa-check"></i> Giao dịch rút tiền nhanh chóng 24/7</div>
                    <div class="feature-item"><i class="fas fa-check"></i> Bảo mật tài khoản tối tân tuyệt đối</div>
                    <div class="feature-item"><i class="fas fa-check"></i> Kiếm tiền mọi lúc mọi nơi đơn giản</div>
                </div>
            </div>
            <div class="left-footer">
                © 2026 <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?>. All rights reserved.
            </div>
        </div>

        <!-- CỘT PHẢI: FORM ĐĂNG NHẬP -->
        <div class="split-right">
            <div class="badge-welcome">✦ Đăng nhập hệ thống</div>
            <div class="form-header">
                <h1>Chào mừng trở lại</h1>
                <p>Đăng nhập nhanh qua mạng xã hội hoặc tài khoản của bạn</p>
            </div>

            <!-- Các nút mạng xã hội -->
            <div class="social-login">
                <a href="/login-google" class="btn-social google"><i class="fab fa-google"></i> Google</a>
                <a href="/login-facebook" class="btn-social facebook"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="/login-github" class="btn-social github"><i class="fab fa-github"></i> Github</a>
            </div>

            <div class="divider">hoặc tài khoản</div>

            <form action="" method="POST" autocomplete="off">
                <div class="input-group-custom">
                    <label>Tài khoản hoặc Email</label>
                    <div class="input-wrapper">
                        <input type="text" placeholder="Nhập tài khoản hoặc email" name="username" class="form-control-custom" required>
                    </div>
                </div>

                <div class="input-group-custom">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" class="form-control-custom" required>
                        <button type="button" class="toggle-password" onclick="togglePass()">
                            <i class="far fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-pass">
                    <a href="/reset-pass" target="_blank">Quên mật khẩu?</a>
                </div>

                <button type="submit" name="submit" class="btn-submit">Đăng nhập ngay <i class="fas fa-arrow-right"></i></button>

                <div class="register-link">
                    Chưa có tài khoản? <a href="/dang-ky">Đăng ký ngay</a>
                </div>
            </form>

            <div class="card-footer-info">
                <span class="stars">★★★★★</span> 
                <span>10,000+ người dùng đã tin tưởng</span>
            </div>
        </div>
    </div>

    <script>
        function togglePass() {
            var passInput = document.getElementById("password");
            var icon = document.getElementById("toggleIcon");
            if (passInput.type === "password") {
                passInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>