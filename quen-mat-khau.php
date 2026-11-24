<?php 
require_once('config/config.php'); 

if(isset($_SESSION['username'])) {
    echo '<meta http-equiv="refresh" content="0;url=/">';
    exit();
}

$step = isset($_GET['step']) ? $_GET['step'] : 'request';

if(isset($_POST['action'])) {
    $action = $_POST['action'];

    if($action == 'send_otp') {
        $email = check_string($_POST['email']);
        
        if(empty($email)) {
            echo json_encode(['status' => 'error', 'msg' => 'Vui lòng nhập địa chỉ Email!']);
            exit();
        }

        $check_mail = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$email'");
        if($check_mail->num_rows == 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Email này không tồn tại trong hệ thống!']);
            exit();
        }

        // Lấy cấu hình SMTP từ database
        $smtp_query = $ketnoi->query("SELECT * FROM `smtp` LIMIT 1");
        if($smtp_query->num_rows == 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Chưa cấu hình thông tin SMTP trong bảng smtp!']);
            exit();
        }
        $smtp_config = $smtp_query->fetch_assoc();

        $token = strtoupper(substr(md5(mt_rand()), 0, 6)); 
        $time_exp = date("Y-m-d H:i:s", strtotime("+15 minutes")); 
        
        $ketnoi->query("UPDATE `users` SET `token` = '$token', `token_time` = '$time_exp' WHERE `email` = '$email'");
        
        // Gọi thư viện PHPMailer để gửi mail thực tế
        try {
          // Gọi thư viện PHPMailer từ thư mục src của bạn
require_once('PHPMailer/src/Exception.php');
require_once('PHPMailer/src/PHPMailer.php');
require_once('PHPMailer/src/SMTP.php');

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Cấu hình server mail lấy từ bảng smtp trong CSDL
            $mail->CharSet = "utf-8";
            $mail->isSMTP();
            $mail->Host       = $smtp_config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_config['smtp_user'];
            $mail->Password   = $smtp_config['smtp_pass'];
            
            // Xử lý chuẩn cổng 465 (SSL) hoặc 587 (TLS)
            if($smtp_config['smtp_port'] == 465) {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port       = $smtp_config['smtp_port'];

            // Gửi từ ai và đến ai
            $mail->setFrom($smtp_config['smtp_user'], $smtp_config['smtp_name']);
            $mail->addAddress($email);

            // Nội dung email gửi đi
            $mail->isHTML(true);
            $mail->Subject = 'Mã xác thực khôi phục mật khẩu - ' . (isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast');
            $mail->Body    = '<div style="font-family:Arial,sans-serif;padding:20px;background:#f4f4f4;">'
                           . '<div style="max-width:500px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;">'
                           . '<h2>Khôi phục mật khẩu tài khoản</h2>'
                           . '<p>Xin chào bạn,</p>'
                           . '<p>Mã OTP xác thực khôi phục mật khẩu của bạn là:</p>'
                           . '<h1 style="color:#f59e0b;letter-spacing:2px;">' . $token . '</h1>'
                           . '<p>Mã này có hiệu lực trong vòng <b>15 phút</b>. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>'
                           . '</div></div>';

            $mail->send();
            
            echo json_encode([
                'status' => 'success', 
                'msg' => 'Đã gửi mã OTP thành công tới email của bạn!', 
                'email' => $email
            ]);
        } catch (Exception $e) {
            // Hiển thị chính xác lỗi SMTP trả về từ Google/Server để dễ fix nếu sai pass ứng dụng
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Gửi mail thất bại! Chi tiết lỗi: ' . $mail->ErrorInfo
            ]);
        }
        exit();
    }

    if($action == 'reset_password') {
        $email = check_string($_POST['email']);
        $otp = check_string($_POST['otp']);
        $new_password = check_string($_POST['new_password']);
        
        $check_user = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$email' AND `token` = '$otp'");
        
        if(empty($otp) || empty($new_password)) {
            echo json_encode(['status' => 'error', 'msg' => 'Vui lòng điền đầy đủ thông tin!']);
            exit();
        } else if($check_user->num_rows == 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Mã OTP không chính xác!']);
            exit();
        } else {
            $user_data = $check_user->fetch_array();
            if(strtotime($user_data['token_time']) < time()) {
                echo json_encode(['status' => 'error', 'msg' => 'Mã OTP đã hết hạn, vui lòng yêu cầu gửi lại!']);
                exit();
            } else {
                $pass_hash = md5($new_password);
                $ketnoi->query("UPDATE `users` SET `password` = '$pass_hash', `token` = NULL, `token_time` = NULL WHERE `email` = '$email'");
                echo json_encode(['status' => 'success', 'msg' => 'Đổi mật khẩu thành công!']);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Khôi Phục Mật Khẩu - <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        :root {
            --bg-primary: #07090e;
            --bg-card: rgba(17, 24, 39, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
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
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.08) 0%, transparent 40%);
        }
        .split-container {
            width: 100%; max-width: 950px; min-height: 520px;
            background: var(--bg-card); backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass); border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8); display: flex; overflow: hidden;
        }
        .split-left {
            flex: 1; background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(7, 9, 14, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1000&auto=format&fit=crop') center/cover no-repeat;
            padding: 45px; display: flex; flex-direction: column; justify-content: space-between;
            color: #fff; border-right: 1px solid var(--border-glass); position: relative;
        }
        .split-left::before { content: ''; position: absolute; inset: 0; background: rgba(7, 9, 14, 0.75); z-index: 1; }
        .split-left > * { position: relative; z-index: 2; }
        .left-brand { font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; }
        .left-brand i, .left-brand span { color: var(--accent-color); }
        .left-content h2 { font-size: 1.8rem; font-weight: 800; line-height: 1.3; margin-bottom: 12px; }
        .left-content h2 span { color: var(--accent-color); }
        .left-content p { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }
        .left-footer { font-size: 0.78rem; color: #6b7280; }

        .split-right {
            flex: 1; padding: 45px 40px; display: flex; flex-direction: column; justify-content: center; background: rgba(17, 24, 39, 0.5);
        }
        .badge-welcome {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(245, 158, 11, 0.12); color: var(--accent-color);
            font-size: 0.72rem; font-weight: 700; padding: 5px 12px; border-radius: 20px;
            text-transform: uppercase; margin-bottom: 14px; border: 1px solid rgba(245, 158, 11, 0.25); width: fit-content;
        }
        .form-header h1 { font-size: 1.7rem; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .form-header p { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5; }
        
        .input-group-custom { margin-bottom: 18px; }
        .input-group-custom label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; }
        
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 15px; color: #6b7280; font-size: 0.95rem; }

        .form-control-custom {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border-glass);
            padding: 13px 15px 13px 44px; font-size: 0.92rem; color: #ffffff; border-radius: 12px; outline: none; transition: all 0.3s ease;
        }
        .form-control-custom:focus { border-color: var(--accent-color); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15); background: rgba(31, 41, 55, 0.9); }
        .input-wrapper:focus-within .input-icon { color: var(--accent-color); }

        .btn-submit {
            width: 100%; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #07090e; border: none; padding: 14px; font-size: 0.95rem; font-weight: 800;
            border-radius: 12px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 20px -4px rgba(245, 158, 11, 0.4); 
            margin-top: 6px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { transform: translateY(-1px); filter: brightness(1.05); }
        
        .back-login { text-align: center; margin-top: 20px; font-size: 0.85rem; color: var(--text-muted); }
        .back-login a { color: var(--accent-color); font-weight: 700; text-decoration: none; }
        .back-login a:hover { text-decoration: underline; color: #fbbf24; }

        @media (max-width: 850px) {
            .split-container { flex-direction: column; max-width: 480px; }
            .split-left { display: none; }
            .split-right { padding: 35px 25px; }
        }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="split-left">
            <div>
                <a href="/" class="left-brand"><i class="fas fa-bolt"></i> <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTien<span>Fast</span>'; ?></a>
            </div>
            <div class="left-content">
                <h2>Khôi Phục <span>Tài Khoản</span> An Toàn</h2>
                <p>Hệ thống tự động gửi mã xác thực OTP qua email giúp bạn lấy lại quyền truy cập tài khoản nhanh chóng và bảo mật tuyệt đối.</p>
            </div>
            <div class="left-footer">
                © 2026 <?= isset($site['ten_web']) ? $site['ten_web'] : 'KiemTienFast'; ?>. All rights reserved.
            </div>
        </div>

        <div class="split-right">
            <?php if($step == 'request'): ?>
                <div class="badge-welcome"><i class="fas fa-shield-alt"></i> Hỗ trợ tài khoản</div>
                <div class="form-header">
                    <h1>Quên mật khẩu?</h1>
                    <p>Nhập email đã đăng ký để nhận mã OTP khôi phục bảo mật</p>
                </div>
                <form id="formSendOtp" autocomplete="off">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="input-group-custom">
                        <label>Địa chỉ Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" id="email" placeholder="name@example.com" class="form-control-custom" required>
                        </div>
                    </div>
                    <button type="submit" id="btnSend" class="btn-submit">Gửi mã xác thực <i class="fas fa-arrow-right"></i></button>
                </form>
            <?php elseif($step == 'verify'): ?>
                <div class="badge-welcome"><i class="fas fa-key"></i> Xác thực bảo mật</div>
                <div class="form-header">
                    <h1>Đặt lại mật khẩu</h1>
                    <p>Mã OTP đã được gửi đến email: <b style="color:#fff;"><?= htmlspecialchars($_GET['email'] ?? ''); ?></b></p>
                </div>
                <form id="formResetPass" autocomplete="off">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" id="verifyEmail" value="<?= htmlspecialchars($_GET['email'] ?? ''); ?>">
                    <div class="input-group-custom">
                        <label>Mã OTP (6 chữ số)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-certificate input-icon"></i>
                            <input type="text" name="otp" id="otp" placeholder="VD: A1B2C3" class="form-control-custom" required>
                        </div>
                    </div>
                    <div class="input-group-custom">
                        <label>Mật khẩu mới</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="new_password" id="new_password" placeholder="••••••••" class="form-control-custom" required>
                        </div>
                    </div>
                    <button type="submit" id="btnReset" class="btn-submit">Cập nhật mật khẩu <i class="fas fa-check"></i></button>
                </form>
            <?php endif; ?>

            <div class="back-login">
                Đã nhớ ra mật khẩu? <span style="cursor:pointer;" onclick="window.location.href='/dang-nhap'">Đăng nhập ngay</span>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $(document).on('submit', '#formSendOtp', function(e) {
                e.preventDefault();
                var email = $('#email').val();
                $('#btnSend').html('<i class="fas fa-spinner fa-spin"></i> Đang gửi mail...').prop('disabled', true);

                $.ajax({
                    url: 'quen-mat-khau.php',
                    type: 'POST',
                    data: { action: 'send_otp', email: email },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            swal("Thành công", res.msg, "success");
                            setTimeout(function() {
                                window.location.href = 'quen-mat-khau.php?step=verify&email=' + encodeURIComponent(res.email);
                            }, 2000);
                        } else {
                            swal("Lỗi gửi mail", res.msg, "error");
                            $('#btnSend').html('Gửi mã xác thực <i class="fas fa-arrow-right"></i>').prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        swal("Lỗi hệ thống", "Không thể kết nối tới máy chủ!", "error");
                        $('#btnSend').html('Gửi mã xác thực <i class="fas fa-arrow-right"></i>').prop('disabled', false);
                    }
                });
            });

            $(document).on('submit', '#formResetPass', function(e) {
                e.preventDefault();
                var formData =$(this).serialize();
                $('#btnReset').html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...').prop('disabled', true);

                $.ajax({
                    url: 'quen-mat-khau.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            swal("Thành công", res.msg, "success");
                            setTimeout(function() {
                                window.location.href = '/dang-nhap';
                            }, 1500);
                        } else {
                            swal("Lỗi", res.msg, "error");
                            $('#btnReset').html('Cập nhật mật khẩu <i class="fas fa-check"></i>').prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        swal("Lỗi hệ thống", "Không thể kết nối tới máy chủ!", "error");
                        $('#btnReset').html('Cập nhật mật khẩu <i class="fas fa-check"></i>').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>