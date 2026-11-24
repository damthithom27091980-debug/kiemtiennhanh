<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối CSDL
include_once('config/head.php');

// 1. Kiểm tra đăng nhập
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if (empty($username)) {
    echo '<script>
        alert("Vui lòng đăng nhập để nhận thưởng!");
        window.location="/dang-nhap";
    </script>';
    exit;
}

// Kiểm tra và khởi tạo giới hạn 3 lần nhập sai
if (!isset($_SESSION['captcha_fail_count'])) {
    $_SESSION['captcha_fail_count'] = 0;
}

if ($_SESSION['captcha_fail_count'] >= 3) {
    echo '<script>
        alert("Bạn đã nhập sai quá 3 lần! Vui lòng quay lại danh sách nhiệm vụ.");
        window.location="/nhiem-vu";
    </script>';
    exit;
}

// 2. Lấy mã code (secret_token) từ tham số `code` và lọc bỏ phần rác
$raw_code = isset($_GET['code']) ? $_GET['code'] : (isset($_POST['id_token']) ? $_POST['id_token'] : '');
$code_token = strtok($raw_code, '/?');
$code_token = preg_replace('/[^a-zA-Z0-9\-_]/', '', $code_token);

if (empty($code_token)) {
    echo '<script>
        alert("Mã nhận thưởng không hợp lệ!");
        window.location="/nhiem-vu";
    </script>';
    exit;
}

$code_token_esc = mysqli_real_escape_string($ketnoi, $code_token);
$username_esc = mysqli_real_escape_string($ketnoi, $username);

// 3. Tìm chiến dịch dựa trên `secret_token`
$query = mysqli_query($ketnoi, "
    SELECT campaigns.*, categories.reward, categories.approve_type
    FROM `campaigns`
    INNER JOIN `categories`
        ON campaigns.category_id = categories.id
    WHERE campaigns.secret_token = '$code_token_esc'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) == 0) {
    echo '<script>
        alert("Liên kết nhận thưởng không tồn tại hoặc đã bị xóa!");
        window.location="/nhiem-vu";
    </script>';
    exit;
}

$campaign = mysqli_fetch_assoc($query);
$campaign_id = intval($campaign['id']);
$cat_id = intval($campaign['category_id']);
$reward = isset($campaign['reward']) ? intval($campaign['reward']) : 500;
$approve_type = isset($campaign['approve_type']) ? intval($campaign['approve_type']) : 1;
$current_date = date('Y-m-d');

// 4. Kiểm tra xem hôm nay đã hoàn thành nhiệm vụ này chưa
$check_spam = mysqli_query($ketnoi, "
    SELECT id FROM `user_task_history`
    WHERE `username` = '$username_esc' 
      AND `campaign_id` = '$campaign_id' 
      AND DATE(`created_date`) = '$current_date'
    LIMIT 1
");

if ($check_spam && mysqli_num_rows($check_spam) > 0) {
    echo '<script>
        alert("Bạn đã hoàn thành nhiệm vụ này trong ngày hôm nay rồi, hãy quay lại vào ngày mai nhé!");
        window.location="/lam-nhiem-vu/' . $cat_id . '";
    </script>';
    exit;
}

// 5. XỬ LÝ ĐỔI PHÉP TOÁN KHI BẤM NÚT ĐỔI
if (isset($_GET['refresh_captcha'])) {
    unset($_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_op'], $_SESSION['captcha_result']);
    echo '<script>window.location="nhan-thuong.php?code=' . htmlspecialchars($code_token) . '";</script>';
    exit;
}

// Khởi tạo Captcha
if (!isset($_SESSION['captcha_result'])) {
    $num1 = rand(1, 15);
    $num2 = rand(1, 10);
    $op = (rand(0, 1) == 1) ? '+' : '-';

    if ($op == '+') {
        $_SESSION['captcha_result'] = $num1 + $num2;
    } else {
        if ($num1 < $num2) { $temp = $num1; $num1 = $num2; $num2 = $temp; }
        $_SESSION['captcha_result'] = $num1 - $num2;
    }
    $_SESSION['captcha_num1'] = $num1;
    $_SESSION['captcha_num2'] = $num2;
    $_SESSION['captcha_op'] = $op;
}

$question_str = $_SESSION['captcha_num1'] . ' ' . $_SESSION['captcha_op'] . ' ' . $_SESSION['captcha_num2'];

// 6. XỬ LÝ KHI USER NHẬP ĐÁP ÁN
$error_msg = '';
if (isset($_POST['verify_captcha'])) {
    $user_answer = intval($_POST['captcha_answer']);
    $session_answer = isset($_SESSION['captcha_result']) ? intval($_SESSION['captcha_result']) : -999;

    if ($user_answer === $session_answer) {
        // Nhập đúng -> Xóa session captcha và reset số lần đếm sai
        unset($_SESSION['captcha_result'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_op'], $_SESSION['captcha_fail_count']);

        $current_time = date('Y-m-d H:i:s');

        if ($approve_type == 1) {
            $update_money = mysqli_query($ketnoi, "
                UPDATE `users`
                SET `money` = `money` + $reward
                WHERE `username` = '$username_esc'
            ");

            if (!$update_money) {
                echo '<script>
                    alert("Có lỗi khi cộng tiền, vui lòng thử lại!");
                    window.location="/lam-nhiem-vu/' . $cat_id . '";
                </script>';
                exit;
            }

            // Cộng hoa hồng giới thiệu
            $get_user_info = mysqli_query($ketnoi, "SELECT `ref_by` FROM `users` WHERE `username` = '$username_esc' LIMIT 1");
            if ($get_user_info && mysqli_num_rows($get_user_info) > 0) {
                $user_info = mysqli_fetch_assoc($get_user_info);
                $nguoi_gioi_thieu = $user_info['ref_by'] ?? '';

                if (!empty($nguoi_gioi_thieu)) {
                    $hoa_hong = round($reward * 0.05); 
                    if ($hoa_hong > 0) {
                        $nguoi_gioi_thieu_esc = mysqli_real_escape_string($ketnoi, $nguoi_gioi_thieu);
                        mysqli_query($ketnoi, "
                            UPDATE `users`
                            SET `money` = `money` + $hoa_hong,
                                `ref_money` = `ref_money` + $hoa_hong
                            WHERE `username` = '$nguoi_gioi_thieu_esc'
                        ");
                    }
                }
            }

            mysqli_query($ketnoi, "
                INSERT INTO `user_task_history`
                (`username`, `campaign_id`, `status`, `created_date`, `created_at`)
                VALUES
                ('$username_esc', '$campaign_id', 1, '$current_date', '$current_time')
            ");

            echo '<script>
                alert("Chúc mừng bạn đã hoàn thành nhiệm vụ và nhận được +' . number_format($reward) . ' VNĐ!");
                window.location="/lam-nhiem-vu/' . $cat_id . '";
            </script>';
            exit;

        } else {
            mysqli_query($ketnoi, "
                INSERT INTO `user_task_history`
                (`username`, `campaign_id`, `status`, `created_date`, `created_at`)
                VALUES
                ('$username_esc', '$campaign_id', 0, '$current_date', '$current_time')
            ");

            echo '<script>
                alert("Gửi yêu cầu thành công! Nhiệm vụ của bạn đang chờ Admin duyệt.");
                window.location="/lam-nhiem-vu/' . $cat_id . '";
            </script>';
            exit;
        }

    } else {
        // Nhập sai -> Tăng số lần sai lên 1
        $_SESSION['captcha_fail_count']++;
        $remaining = 3 - $_SESSION['captcha_fail_count'];

        if ($remaining > 0) {
            $error_msg = 'Kết quả không chính xác! Bạn còn ' . $remaining . ' lần thử.';
        } else {
            $error_msg = 'Bạn đã nhập sai quá 3 lần và bị khóa xác thực nhiệm vụ này!';
        }

        // Hủy captcha cũ để tạo câu hỏi mới
        unset($_SESSION['captcha_result'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_op']);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Nhận Thưởng - <?=$site['ten_web'];?></title>
    <!-- Font Awesome & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-dark: #07090e;
            --card-bg: rgba(18, 22, 33, 0.75);
            --border-color: rgba(108, 92, 231, 0.25);
            --accent-primary: #6c5ce7;
            --accent-secondary: #a29bfe;
            --success-color: #00b894;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(108, 92, 231, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(0, 184, 148, 0.12) 0%, transparent 45%);
            color: var(--text-main);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .landing-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 20px;
            animation: fadeInScale 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: translateY(25px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .verify-box {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 35px 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7), 0 0 35px rgba(108, 92, 231, 0.12);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Viền sáng chạy dọc thân card */
        .verify-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-secondary), transparent);
            animation: borderGlow 4s linear infinite;
        }

        @keyframes borderGlow {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }

        .brand-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--accent-secondary);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            display: inline-block;
            padding: 6px 16px;
            background: rgba(108, 92, 231, 0.12);
            border-radius: 50px;
            border: 1px solid rgba(108, 92, 231, 0.25);
        }

        .verify-box h2 {
            color: #fff;
            margin: 0 0 10px 0;
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
        }

        .verify-box p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .reward-highlight {
            color: var(--success-color);
            font-weight: 700;
            background: rgba(0, 184, 148, 0.12);
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid rgba(0, 184, 148, 0.25);
        }

        .captcha-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .captcha-badge {
            background: linear-gradient(135deg, rgba(108, 92, 231, 0.18) 0%, rgba(162, 155, 254, 0.08) 100%);
            color: #fff;
            font-size: 1.7rem;
            font-weight: 700;
            padding: 14px 20px;
            border-radius: 16px;
            letter-spacing: 4px;
            border: 1px solid rgba(108, 92, 231, 0.35);
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.3);
            flex-grow: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
        }

        .btn-refresh {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            border-radius: 16px;
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            flex-shrink: 0;
        }

        .btn-refresh:hover {
            background: rgba(108, 92, 231, 0.25);
            color: #fff;
            border-color: var(--accent-primary);
            transform: rotate(180deg) scale(1.05);
        }

        .form-control-custom {
            background: rgba(7, 9, 14, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            border-radius: 16px !important;
            padding: 16px 20px !important;
            font-size: 1.15rem;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            margin-bottom: 20px;
            outline: none;
            transition: all 0.3s ease;
            letter-spacing: 1px;
        }

        .form-control-custom:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.25), 0 0 15px rgba(108, 92, 231, 0.2);
            background: rgba(7, 9, 14, 0.95) !important;
        }

        .btn-verify {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-weight: 700;
            color: #fff;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.45);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(108, 92, 231, 0.65);
            filter: brightness(1.05);
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .note-box {
            margin-top: 25px;
            background: rgba(243, 156, 18, 0.05);
            border: 1px solid rgba(243, 156, 18, 0.18);
            border-radius: 16px;
            padding: 16px;
            text-align: left;
        }

        .note-box h5 {
            color: #f39c12;
            font-size: 0.88rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .note-box ul {
            margin: 0;
            padding-left: 18px;
            color: var(--text-muted);
            font-size: 0.83rem;
            line-height: 1.6;
        }

        .note-box ul li {
            margin-bottom: 4px;
        }

        .note-box ul li:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 480px) {
            .verify-box {
                padding: 25px 20px;
            }
            .captcha-badge {
                font-size: 1.4rem;
                padding: 12px 14px;
            }
        }
    </style>
</head>
<body>

<div class="landing-wrapper">
    <div class="verify-box">
        <div class="brand-title"><i class="fa-solid fa-shield-halved"></i> <?=$site['ten_web'];?></div>
        <h2>Xác Thực Nhận Thưởng</h2>
        <p>Giải phép tính bên dưới để hoàn tất và nhận ngay <span class="reward-highlight">+<?=number_format($reward);?> VNĐ</span> vào tài khoản.</p>

        <?php if (!empty($error_msg)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thông báo',
                        text: '<?=$error_msg;?>',
                        confirmButtonColor: '#6c5ce7',
                        background: '#161b27',
                        color: '#f1f5f9'
                    }).then(() => {
                        <?php if ($_SESSION['captcha_fail_count'] >= 3): ?>
                            window.location = "/nhiem-vu";
                        <?php endif; ?>
                    });
                });
            </script>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="id_token" value="<?=htmlspecialchars($code_token);?>">
            
            <div class="captcha-container">
                <div class="captcha-badge"><?=$question_str;?> = ?</div>
                <a href="nhan-thuong.php?code=<?=htmlspecialchars($code_token);?>&refresh_captcha=1" class="btn-refresh" title="Đổi câu hỏi khác">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>

            <div>
                <input type="number" name="captcha_answer" class="form-control-custom" placeholder="Nhập kết quả..." required autocomplete="off" autofocus>
            </div>

            <button type="submit" name="verify_captcha" class="btn-verify">
                <span>XÁC NHẬN NHẬN THƯỞNG</span> <i class="fa-solid fa-gift"></i>
            </button>
        </form>

        <div class="note-box">
            <h5><i class="fa-solid fa-circle-exclamation"></i> Lưu ý quan trọng:</h5>
            <ul>
                <li>Nhập sai tối đa <b>3 lần</b> sẽ bị khóa xác thực nhiệm vụ.</li>
                <li>Hình thức duyệt: <b><?= ($approve_type == 1) ? 'Tự động cộng tiền ngay' : 'Chờ Admin duyệt thủ công'; ?></b>.</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>