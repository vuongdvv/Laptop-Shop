<?php
session_start();
require_once dirname(__DIR__, 1) . "/../config/config.php";
require_once dirname(__DIR__, 1) . "/../config/database.php";
require_once dirname(__DIR__, 1) . "/../includes/functions.php";


requireAdminAccess($conn);

$currentPath = $_SERVER['REQUEST_URI'];


$totalCustomers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

$newCustomers = $conn->query("
    SELECT COUNT(*) as total 
    FROM users 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
")->fetch_assoc()['total'];

$activeUsers = $conn->query("
    SELECT COUNT(*) as total 
    FROM users 
    WHERE status = '1'
")->fetch_assoc()['total'];


$sql = "
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    u.status,
    u.role,
    COUNT(o.id) as total_orders,
    COALESCE(SUM(o.total_price),0) as total_spent
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
GROUP BY u.id
ORDER BY u.id DESC
LIMIT 10
";

$customers = $conn->query($sql);

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: customer.php?success=Xóa thành công");
        exit;
    } else {
        echo "Lỗi xóa!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý khách hàng</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/customer.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">



</head>

<body>
    <div class="admin-wraper">
        <!-- SIDEBAR -->
        <?php include dirname(__DIR__) . "/sidebar.php"; ?>

        <!-- MAIN -->
        <div class="admin-container">

            <div class="page-header">
                <div>
                    <h2>Quản lý khách hàng</h2>
                    <p>Quản lý và theo dõi thông tin khách hàng</p>
                </div>

            </div>

            <!-- CARDS -->
            <div class="stats-grid">
                <div class="card">
                    <p>Tổng khách hàng</p>
                    <h3><?= $totalCustomers ?></h3>

                </div>

                <div class="card">
                    <p>Khách mới tháng này</p>
                    <h3><?= $newCustomers ?></h3>

                </div>

                <div class="card">
                    <p>Người dùng hoạt động</p>
                    <h3><?= $activeUsers ?></h3>

                </div>
            </div>

            <!-- TABLE -->
            <div class="table-wrapper">

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Đơn hàng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Trạng thái</th>
                            <th>Vai trò</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $customers->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['full_name'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= $row['phone'] ?></td>
                                <td><?= $row['total_orders'] ?></td>
                                <td><?= number_format($row['total_spent'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <?php if ($row['status'] == '1'): ?>
                                        <span class="status active-status">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status inactive-status">Ngừng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="user-role"><?= $row['role'] ?></td>

                                <td>
                                    <button class="btn btn-edit" onclick="openEditUserModal(<?= $row['id'] ?>)">
                                        Sửa
                                    </button>

                                    <a href="?delete=<?= $row['id'] ?>"
                                        onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')"
                                        class="btn-delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
            <?php if (isset($_GET['success'])): ?> <div id="toast" class="toast-success">
                    ✔ Xóa tài khoản thành công </div>
            <?php endif; ?>
        </div>

    </div>



    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditUserModal()">&times;</span>

            <h2>Sửa thông tin người dùng</h2>

            <form id="editUserForm">
                <input type="hidden" name="id" id="edit_user_id">

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" id="edit_user_status">
                        <option value="1">Hoạt động</option>
                        <option value="0">Ngừng</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_user_role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary-save">Cập nhật</button>
            </form>
        </div>
    </div>
</body>
<script>
    // TOAST NOTIFICATION
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 40px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            min-width: 280px;
            max-width: 360px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            animation: slideDown 0.35s ease-out, fadeOut 0.3s ease-in 2.5s forwards;
        `;

        if (type === 'success') {
            toast.style.background = '#22c55e';
            toast.style.color = '#fff';
        } else if (type === 'error') {
            toast.style.background = '#ef4444';
            toast.style.color = '#fff';
        } else {
            toast.style.background = '#3b82f6';
            toast.style.color = '#fff';
        }

        toast.textContent = message;
        document.body.appendChild(toast);

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-40px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-10px);
                }
            }
        `;
        document.head.appendChild(style);

        setTimeout(() => toast.remove(), 3000);
    }

    setTimeout(() => {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.remove();
        }

        // Xoá ?success=1 khỏi URL
        if (window.location.search.includes('success')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

    }, 3500);


    function openEditUserModal(id) {
        let row = document.querySelectorAll("tbody tr")[id - 1];

        document.getElementById("editUserModal").style.display = "block";
        document.getElementById("edit_user_id").value = id;

        let statusText = row.querySelector(".status").innerText;
        let role = row.querySelector(".user-role").innerText;

        document.getElementById("edit_user_status").value =
            statusText === "Hoạt động" ? "1" : "0";

        document.getElementById("edit_user_role").value = role;
    }

    function closeEditUserModal() {
        document.getElementById("editUserModal").style.display = "none";
    }

    document.getElementById("editUserForm").addEventListener("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch("update_user.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast("Cập nhật thành công!", "success");
                    closeEditUserModal();

                    // Nếu logout=true thì redirect về login page
                    if (data.logout) {
                        setTimeout(() => {
                            window.location.href = "<?= BASE_URL ?>/admin/admin_auth/login.php?message=role_changed";
                        }, 1500);
                    } else {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showToast("Lỗi cập nhật!", "error");
                }
            })
            .catch(err => {
                console.error("FETCH ERROR:", err);
                showToast("Không gửi được request!", "error");
            });
    });

    // Click ngoài modal
    window.onclick = function(e) {
        let modal = document.getElementById("editUserModal");
        if (e.target === modal) modal.style.display = "none";
    };
</script>

</html>