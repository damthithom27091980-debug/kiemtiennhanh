<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('config/head.php'); 

$result = mysqli_query($ketnoi, "SELECT * FROM `setting` LIMIT 1");
$minhchien = mysqli_fetch_assoc($result);

// --- XỬ LÝ LẤY LỊCH SỬ NHIỆM VỤ DỰA TRÊN USERNAME VÀ BỘ LỌC TRẠNG THÁI ---
$username = mysqli_real_escape_string($ketnoi, $user['username'] ?? '');

// Lấy giá trị lọc trạng thái từ form GET (nếu có)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Xây dựng điều kiện lọc SQL dựa theo giá trị status trong DB (0: Chờ duyệt, 1: Thành công, 2: Hủy)
$where_status = "";
if ($status_filter !== 'all' && is_numeric($status_filter)) {
    $status_val = mysqli_real_escape_string($ketnoi, $status_filter);
    $where_status = "AND status = '$status_val'";
}

// Truy vấn giới hạn 11 nhiệm vụ mới nhất có kèm điều kiện lọc
$sql_history = "SELECT * FROM user_task_history WHERE username = '$username' $where_status ORDER BY id DESC LIMIT 11";
$query_history = mysqli_query($ketnoi, $sql_history);

// --- XỬ LÝ LẤY DANH SÁCH THÔNG BÁO PHỤ / SỰ KIỆN (BÀI ĐĂNG) ---
$sql_posts = "SELECT * FROM posts ORDER BY id DESC LIMIT 5";
$query_posts = mysqli_query($ketnoi, $sql_posts);

// --- XỬ LÝ TRẠNG THÁI TÀI KHOẢN ---
$role_name = "Thành viên";
$badge_color = "text-info";
$badge_icon = "mdi-account-check";

if (isset($user['role']) && $user['role'] == 'admin') {
    $role_name = "Quản Trị Viên";
    $badge_color = "text-danger";
    $badge_icon = "mdi-shield-account";
} elseif (isset($user['tong_nap']) && $user['tong_nap'] >= 500000) {
    $role_name = "Thành Viên VIP";
    $badge_color = "text-warning";
    $badge_icon = "mdi-crown";
}
?>
<title><?=$minhchien['ten_web'];?> - <?=$minhchien['mo_ta'];?></title>
<?php require_once('config/nav.php'); ?>
            
	<div class="main-panel">
        <div class="content-wrapper">

            <!-- Ô THÔNG BÁO HỆ THỐNG CHÍNH -->
            <div class="row">
                <div class="col-12 grid-margin">
                    <div class="announcement-banner shadow-lg">
                        <div class="d-flex align-items-center mb-2">
                            <div class="announcement-icon mr-3">
                                <i class="mdi mdi-bullhorn-outline"></i>
                            </div>
                            <h4 class="text-white font-weight-bold mb-0" style="font-size: 1.15rem; letter-spacing: 0.3px;">Thông báo mới nhất từ hệ thống</h4>
                        </div>
                        <div class="announcement-content pl-4">
                            <?php 
                                if(isset($minhchien['thongbao']) && !empty($minhchien['thongbao'])) {
                                    echo html_entity_decode($minhchien['thongbao']);
                                } else {
                                    echo 'Hiện tại chưa có thông báo mới nào từ hệ thống.';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hàng 3 Thẻ Thống Kê (Dashboard Stats) -->
            <div class="row">
                <!-- Thẻ 1: Số dư -->
                <div class="col-xl-4 col-sm-6 grid-margin stretch-card">
                  <div class="card stat-card shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Số dư tài khoản</h6>
                            <h3 class="text-white font-weight-bold mb-0" style="font-size: 1.6rem;"><?=tien($user['money'] ?? 0);?> <span style="font-size: 1rem; color: #00f2fe;">đ</span></h3>
                        </div>
                        <div class="stat-icon-box text-info">
                            <i class="mdi mdi-wallet"></i>
                        </div>
                    </div>
                  </div>
                </div>

                <!-- Thẻ 2: Trạng thái tài khoản -->
                <div class="col-xl-4 col-sm-6 grid-margin stretch-card">
                  <div class="card stat-card shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Cấp độ tài khoản</h6>
                            <h3 class="<?=$badge_color;?> font-weight-bold mb-0" style="font-size: 1.4rem;"><?=$role_name;?></h3>
                        </div>
                        <div class="stat-icon-box <?=$badge_color;?>">
                            <i class="mdi <?=$badge_icon;?>"></i>
                        </div>
                    </div>
                  </div>
                </div>

                <!-- Thẻ 3: Đồng hồ thời gian thực -->
                <div class="col-xl-4 col-sm-6 grid-margin stretch-card">
                  <div class="card stat-card shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;" id="current-date">--/--/----</h6>
                            <h3 class="text-white font-weight-bold mb-0" style="font-size: 1.5rem; font-family: monospace;" id="live-clock">00:00:00</h3>
                        </div>
                        <div class="stat-icon-box text-warning">
                            <i class="mdi mdi-clock-outline"></i>
                        </div>
                    </div>
                  </div>
                </div>
            </div>

            <!-- Khối 3 Chức Năng Nhanh (Action Cards) -->
            <div class="row">
              <div class="col-md-4 grid-margin stretch-card">
                <div class="action-card-custom">
                    <div>
                        <div class="card-top-info">
                            <div class="card-badge-icon bg-info-soft"><i class="mdi mdi-check-circle-outline"></i></div>
                            <span class="card-subtitle-custom">Trung tâm nhiệm vụ</span>
                        </div>
                        <div class="card-title-custom">Làm task nhận Thưởng</div>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">Hoàn thành các chiến dịch đơn giản để tích lũy thu nhập mỗi ngày.</p>
                    </div>
                    <a href="/nhiem-vu" class="btn btn-action-custom btn-task">Làm Ngay <i class="mdi mdi-arrow-right ml-1"></i></a>
                </div>
              </div>

              <div class="col-md-4 grid-margin stretch-card">
                <div class="action-card-custom">
                    <div>
                        <div class="card-top-info">
                            <div class="card-badge-icon bg-warning-soft"><i class="mdi mdi-wallet"></i></div>
                            <span class="card-subtitle-custom">Ví & thanh toán</span>
                        </div>
                        <div class="card-title-custom">Tạo lệnh rút tiền</div>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">Quy đổi số dư và rút tiền về tài khoản ngân hàng hoặc ví điện tử nhanh chóng.</p>
                    </div>
                    <a href="/rut-tien" class="btn btn-action-custom btn-wallet">Rút Ngay <i class="mdi mdi-arrow-right ml-1"></i></a>
                </div>
              </div>

              <div class="col-md-4 grid-margin stretch-card">
                <div class="action-card-custom">
                    <div>
                        <div class="card-top-info">
                            <div class="card-badge-icon bg-success-soft"><i class="mdi mdi-account-multiple-plus"></i></div>
                            <span class="card-subtitle-custom">Hệ thống hoa hồng</span>
                        </div>
                        <div class="card-title-custom">Giới thiệu bạn bè</div>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">Mời bạn bè tham gia qua link giới thiệu để nhận hoa hồng thụ động hấp dẫn.</p>
                    </div>
                    <a href="/gioi-thieu" class="btn btn-action-custom btn-invite">Xem Ngay <i class="mdi mdi-arrow-right ml-1"></i></a>
                </div>
              </div>
            </div>
			
            <!-- Khối Bảng Tin & Sự Kiện -->
            <div class="row">
                <div class="col-12 grid-margin">
                  <div class="income-stats-card">
                    <div class="income-header-wrapper">
                        <div class="income-title-group">
                            <div class="income-icon-box">
                                <i class="mdi mdi-newspaper"></i>
                            </div>
                            <div>
                                <h4 class="income-main-title">Bảng Tin & Sự Kiện Hệ Thống</h4>
                                <div class="income-total-sub">Cập nhật các bài đăng, thông báo và sự kiện mới nhất</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table text-white custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Bài viết</th>
                                    <th style="width: 50%;">Tiêu đề sự kiện / Thông báo</th>
                                    <th style="width: 25%;">Thời gian đăng</th>
                                    <th style="width: 15%; text-align: center;">Xem chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($query_posts && mysqli_num_rows($query_posts) > 0) {
                                    $stt = 1;
                                    while($post = mysqli_fetch_assoc($query_posts)) {
                                        $postId = $post['id'];
                                ?>
                                <tr class="table-row-item">
                                    <td class="font-weight-bold text-warning">
                                        <span class="badge-index">#<?= $stt++; ?></span>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-white"><?= htmlspecialchars($post['title']); ?></span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        <i class="mdi mdi-clock-outline mr-1"></i><?= $post['created_at']; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-custom-detail" id="btn-toggle-<?= $postId; ?>" onclick="togglePostContent(<?= $postId; ?>)">
                                            Chi tiết <i class="mdi mdi-chevron-down ml-1" id="icon-<?= $postId; ?>" style="transition: transform 0.3s ease;"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Dòng ẩn hiển thị nội dung chi tiết -->
                                <tr id="post-detail-<?= $postId; ?>" style="display: none;" class="table-detail-row">
                                    <td colspan="4" style="padding: 24px;">
                                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                                            <h6 class="text-warning font-weight-bold mb-0">
                                                <i class="mdi mdi-information-outline mr-1"></i> <?= htmlspecialchars($post['title']); ?>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-secondary text-white" style="border-radius: 6px; padding: 3px 10px;" onclick="togglePostContent(<?= $postId; ?>)">
                                                <i class="mdi mdi-close"></i> Đóng
                                            </button>
                                        </div>
                                        <div style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.8; white-space: pre-line;">
                                            <?= html_entity_decode($post['content']); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Chưa có bài đăng sự kiện nào.</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                  </div>
                </div>
            </div>

            <!-- Khối Lịch Sử Nhiệm Vụ -->
            <div class="row">
                <div class="col-12 grid-margin">
                  <div class="income-stats-card">
                    
                    <div class="income-header-wrapper" style="margin-bottom: 20px;">
                        <div class="income-title-group">
                            <div class="income-icon-box" style="background: rgba(0, 242, 254, 0.1); border-color: rgba(0, 242, 254, 0.3); color: #00f2fe;">
                                <i class="mdi mdi-history"></i>
                            </div>
                            <div>
                                <h4 class="income-main-title">Lịch sử làm nhiệm vụ gần đây</h4>
                                <div class="income-total-sub">Hiển thị tối đa 11 nhiệm vụ mới nhất của bạn</div>
                            </div>
                        </div>

                        <form method="GET" action="" class="d-flex align-items-center">
                            <select name="status" class="form-control text-white filter-select" onchange="this.form.submit()">
                                <option value="all" <?= ($status_filter == 'all') ? 'selected' : ''; ?>>🔍 Tất cả trạng thái</option>
                                <option value="0" <?= ($status_filter === '0') ? 'selected' : ''; ?>>⏳ Chờ duyệt</option>
                                <option value="1" <?= ($status_filter == '1') ? 'selected' : ''; ?>>✅ Thành công</option>
                                <option value="2" <?= ($status_filter == '2') ? 'selected' : ''; ?>>❌ Từ chối / Hủy</option>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table text-white custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">#ID</th>
                                    <th style="width: 40%;">Mã Chiến Dịch (Campaign ID)</th>
                                    <th style="width: 30%;">Thời gian làm</th>
                                    <th style="width: 15%; text-align: center;">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($query_history && mysqli_num_rows($query_history) > 0) {
                                    while($row = mysqli_fetch_assoc($query_history)) {
                                        if ($row['status'] == 1) {
                                            $status_badge = '<span class="badge-status badge-success-custom">Thành công</span>';
                                        } elseif ($row['status'] == 2) {
                                            $status_badge = '<span class="badge-status badge-danger-custom">Từ chối</span>';
                                        } else {
                                            $status_badge = '<span class="badge-status badge-warning-custom">Chờ duyệt</span>';
                                        }
                                ?>
                                <tr class="table-row-item">
                                    <td class="font-weight-bold text-muted">#<?= $row['id']; ?></td>
                                    <td class="font-weight-bold text-info">
                                        <i class="mdi mdi-link-variant mr-1"></i> Nhiệm vụ #<?= $row['campaign_id']; ?>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        <i class="mdi mdi-clock-outline mr-1"></i> <?= $row['created_at']; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?= $status_badge; ?>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    $sql_error = !$query_history ? mysqli_error($ketnoi) : '';
                                    $msg = $sql_error ? "Lỗi SQL: " . $sql_error : "Chưa có lịch sử nhiệm vụ nào được ghi nhận.";
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4"><?= $msg ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                  </div>
                </div>
            </div>

        </div>
	</div>

<!-- CSS GIAO DIỆN CAO CẤP & MƯỢT MÀ -->
<style>
    /* Banner Thông Báo */
    .announcement-banner {
        background: linear-gradient(135deg, #131b2e 0%, #1a243f 100%) !important;
        border: 1px solid #1e293b !important;
        border-radius: 16px !important;
        padding: 22px !important;
        position: relative;
        overflow: hidden;
    }
    .announcement-banner::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: #f59e0b;
    }
    .announcement-icon {
        font-size: 1.6rem;
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.1);
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    .announcement-content {
        color: #cbd5e1;
        font-size: 0.95rem;
        line-height: 1.6;
        border-left: 2px solid rgba(245, 158, 11, 0.3);
        margin-left: 6px;
        padding-left: 14px;
    }

    /* Thẻ Thống Kê (Stat Cards) */
    .stat-card {
        background: #121824 !important;
        border: 1px solid #1e293b !important;
        border-radius: 16px !important;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        border-color: rgba(0, 242, 254, 0.4) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
    }
    .stat-icon-box {
        font-size: 2.2rem;
        width: 55px;
        height: 55px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Thẻ 3 Chức Năng (Action Cards) */
    .action-card-custom {
        background: linear-gradient(145deg, #121824 0%, #172138 100%) !important;
        border: 1px solid #1e293b !important;
        border-radius: 16px !important;
        padding: 25px !important;
        width: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .action-card-custom:hover {
        transform: translateY(-5px);
        border-color: #00f2fe !important;
        box-shadow: 0 10px 30px rgba(0, 242, 254, 0.15) !important;
    }
    .card-top-info {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 14px !important;
    }
    .card-badge-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .bg-info-soft { background: rgba(0, 242, 254, 0.1); color: #00f2fe; }
    .bg-warning-soft { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .bg-success-soft { background: rgba(16, 185, 129, 0.1); color: #10b981; }

    .card-subtitle-custom {
        font-size: 13px !important;
        color: #94a3b8 !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-title-custom {
        font-size: 1.2rem !important;
        font-weight: 700 !important;
        margin-bottom: 10px !important;
        color: #ffffff !important;
    }
    
    /* Nút bấm chức năng hiện đại */
    .btn-action-custom {
        width: 100% !important;
        padding: 12px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-align: center !important;
        border: none !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .btn-task { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); color: #fff !important; }
    .btn-wallet { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #111 !important; }
    .btn-invite { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #fff !important; }
    
    .btn-action-custom:hover {
        opacity: 0.92;
        transform: scale(1.02);
        color: #fff !important;
        text-decoration: none !important;
    }
    .btn-wallet:hover { color: #111 !important; }

    /* Khối Thẻ Bảng Tin & Lịch Sử */
    .income-stats-card {
        background: linear-gradient(145deg, #101728 0%, #162037 100%);
        border: 1px solid #1e293b;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        color: #fff;
    }
    .income-header-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
    }
    .income-title-group {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .income-icon-box {
        width: 50px;
        height: 50px;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #f59e0b;
    }
    .income-main-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 3px;
    }
    .income-total-sub {
        font-size: 0.85rem;
        color: #94a3b8;
    }

    /* Bảng dữ liệu tùy chỉnh */
    .custom-table {
        border-collapse: separate !important;
        border-spacing: 0 10px !important;
    }
    .custom-table thead th {
        border-bottom: none !important;
        color: #64748b !important;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 600;
        padding-top: 0;
    }
    .table-row-item {
        background: #131b2e !important;
        transition: background 0.2s ease;
    }
    .table-row-item:hover {
        background: #18223c !important;
    }
    .table-row-item td {
        border-top: 1px solid #1e293b !important;
        border-bottom: 1px solid #1e293b !important;
        padding: 16px !important;
        vertical-align: middle !important;
    }
    .table-row-item td:first-child {
        border-left: 1px solid #1e293b !important;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .table-row-item td:last-child {
        border-right: 1px solid #1e293b !important;
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    /* Dòng chi tiết xổ xuống */
    .table-detail-row td {
        background: #0f172a !important;
        border: 1px solid #334155 !important;
        border-radius: 0 0 12px 12px;
        box-shadow: inset 0 4px 10px rgba(0,0,0,0.3);
    }

    /* Huy hiệu trạng thái tùy chỉnh */
    .badge-status {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-success-custom { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-danger-custom { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-warning-custom { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

    .badge-index {
        background: rgba(245, 158, 11, 0.1);
        padding: 4px 8px;
        border-radius: 6px;
        color: #f59e0b;
        font-size: 0.85rem;
    }

    .btn-custom-detail {
        border-radius: 8px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 0.85rem;
        border-color: #f59e0b;
        color: #f59e0b;
    }
    .btn-custom-detail:hover {
        background: #f59e0b;
        color: #111;
    }

    .filter-select {
        background: #131b2e !important;
        border: 1px solid #334155 !important;
        border-radius: 10px !important;
        height: 40px !important;
        font-size: 0.9rem;
        padding-left: 14px;
        padding-right: 30px;
    }
    .filter-select:focus {
        border-color: #00f2fe !important;
        box-shadow: 0 0 0 2px rgba(0, 242, 254, 0.2);
    }
</style>

<!-- JS ĐỒNG HỒ & HIỆU ỨNG TƯƠNG TÁC -->
<script>
    function updateClock() {
        var now = new Date();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var seconds = String(now.getSeconds()).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var year = now.getFullYear();
        document.getElementById('live-clock').innerText = hours + ':' + minutes + ':' + seconds;
        document.getElementById('current-date').innerText = day + '/' + month + '/' + year;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Hàm ẩn/hiện nội dung bảng chi tiết bài đăng mượt mà
    function togglePostContent(id) {
        var row = document.getElementById('post-detail-' + id);
        var icon = document.getElementById('icon-' + id);
        
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
            icon.style.transform = 'rotate(180deg)';
        } else {
            row.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>

<?php require_once('config/foot.php'); ?>