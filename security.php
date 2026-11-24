<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối CSDL nếu chưa có biến $ketnoi
if (!isset($ketnoi)) {
    require_once('config/config.php');
}

// Lấy cấu hình bảo mật từ bảng security_settings
$sec_query = mysqli_query($ketnoi, "SELECT * FROM `security_settings` WHERE `id` = 1");
$sec = mysqli_fetch_assoc($sec_query);

if ($sec) {
    // 1. Chống DDoS / Rate Limiting (Giới hạn số request trên phút)
    if (isset($sec['anti_ddos_rate']) && $sec['anti_ddos_rate'] == 1) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $time_window = 60; // 60 giây
        $max_req = intval($sec['max_requests_per_min'] ?? 30);
        
        if (!isset($_SESSION['req_count'])) {
            $_SESSION['req_count'] = 1;
            $_SESSION['req_time'] = time();
        } else {
            if (time() - $_SESSION['req_time'] < $time_window) {
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $max_req) {
                    header("HTTP/1.1 429 Too Many Requests");
                    die("<div style='font-family:Arial;text-align:center;padding:50px;'>
                         <h1 style='color:red;'>429 - Quá Tải Yêu Cầu (Rate Limit Exceeded)</h1>
                         <p>Hệ thống phát hiện IP của bạn ($client_ip) gửi quá nhiều yêu cầu trong thời gian ngắn. Vui lòng chậm lại!</p>
                         </div>");
                }
            } else {
                $_SESSION['req_count'] = 1;
                $_SESSION['req_time'] = time();
            }
        }
    }
}
?>
<!-- Script bảo vệ phía Client tùy chỉnh theo cấu hình Admin -->
<?php if ($sec): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (isset($sec['anti_inspect']) && $sec['anti_inspect'] == 1): ?>
        // Chống bấm F12, Ctrl+Shift+I, Ctrl+U
        document.addEventListener('keydown', function(e) {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || (e.ctrlKey && e.keyCode == 85)) {
                e.preventDefault();
                alert("Tính năng này đã bị vô hiệu hóa bởi quản trị viên!");
                return false;
            }
        });
        // Chống click chuột phải
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        <?php endif; ?>

        <?php if (isset($sec['block_dev_tools']) && $sec['block_dev_tools'] == 1): ?>
        // Phát hiện mở bảng điều khiển nhà phát triển
        setInterval(function() {
            const widthThreshold = window.outerWidth - window.innerWidth > 160;
            const heightThreshold = window.outerHeight - window.innerHeight > 160;
            if (widthThreshold || heightThreshold) {
                document.body.innerHTML = "<h2 style='text-align:center;color:red;margin-top:20vh;'>Cảnh báo: Phát hiện công cụ Developer Tools đang mở! Vui lòng đóng lại để tiếp tục truy cập.</h2>";
            }
        }, 1000);
        <?php endif; ?>
    });
</script>
<?php endif; ?>