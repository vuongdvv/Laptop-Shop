<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// =======================
// HANDLE ADD BRAND
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['type'] === 'brand') {
        $name = trim($_POST['brand_name']);

        if ($name != "") {
            $stmt = $conn->prepare("INSERT INTO brands(name) VALUES(?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
    }

    if ($_POST['type'] === 'category') {
        $name = trim($_POST['category_name']);
        $slug = trim($_POST['slug']);

        if ($name != "") {
            $stmt = $conn->prepare("INSERT INTO categories(name, slug) VALUES(?, ?)");
            $stmt->bind_param("ss", $name, $slug);
            $stmt->execute();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// =======================
// COUNT
// =======================
$totalBrands = $conn->query("SELECT COUNT(*) as total FROM brands")->fetch_assoc()['total'];
$totalCategories = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];

// =======================
// LIST
// =======================
$brands = $conn->query("SELECT * FROM brands ORDER BY id ASC");
$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Thương hiệu & Danh mục</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/brand_category.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">



</head>

<body>

    <!-- TOAST CONTAINER -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="admin-wrapper"> <!-- FIX LỖI Ở ĐÂY -->

        <?php include dirname(__DIR__) . "/sidebar.php"; ?>

        <div class="admin-container">
            <div class="admin-layout">

                <!-- HEADER -->
                <div class="page-header">
                    <div>
                        <h1>Quản lý Thương hiệu & Danh mục</h1>
                        <p>Quản lý thương hiệu và danh mục sản phẩm</p>
                    </div>

                    <div class="header-actions">
                        <button class="btn-primary-add" onclick="openCategoryModal()">
                            + Thêm danh mục
                        </button>

                        <button class="btn-primary-add" onclick="openBrandModal()">
                            + Thêm thương hiệu
                        </button>
                    </div>
                </div>

                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fa fa-box"></i>
                        <div>
                            <div>Tổng Thương hiệu</div>
                            <div class="stat-number"><?= $totalBrands ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <i class="fa fa-folder"></i>
                        <div>
                            <div>Tổng Danh mục</div>
                            <div class="stat-number"><?= $totalCategories ?></div>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-wraper">

                    <!-- BRAND -->
                    <div class="left-table">
                        <h2>Danh sách Thương hiệu</h2>

                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Logo</th>
                                    <th>Thương hiệu</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $stt = 1; ?>
                                <?php while ($brand = $brands->fetch_assoc()): ?>
                                    <tr data-id="brand-<?= $brand['id'] ?>">
                                        <td><?= $stt++ ?></td>
                                        <td>
                                            <?php
                                            $logoPath = !empty($brand['logo_image'])
                                                ? BASE_URL . 'assets/images/brands/' . $brand['logo_image']
                                                : BASE_URL . 'assets/images/brands/no-image.png';
                                            ?>
                                            <img src="<?= $logoPath ?>" alt="<?= htmlspecialchars($brand['name']) ?>" style="width:50px;">
                                        <td class="brand-name"><?= htmlspecialchars($brand['name']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-edit"
                                                onclick="openEditBrandModal(<?= $brand['id'] ?>)">
                                                Sửa
                                            </button>

                                            <a href="delete_brand_category.php?id=<?= $brand['id'] ?>&type=brand"
                                                class="btn btn-delete"
                                                onclick="return confirm('Xóa thương hiệu này?')">
                                                Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- CATEGORY -->
                    <div class="right-table">
                        <h2>Danh sách Danh mục</h2>

                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Danh mục</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $stt = 1; ?>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr data-id="category-<?= $category['id'] ?>">
                                        <td><?= $stt++ ?></td>
                                        <td class="category-name"><?= htmlspecialchars($category['name']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-edit"
                                                onclick="openEditModal(<?= $category['id'] ?>)">
                                                Sửa
                                            </button>

                                            <a href="delete_brand_category.php?id=<?= $category['id'] ?>&type=category"
                                                class="btn btn-delete"
                                                onclick="return confirm('Xóa danh mục này?')">
                                                Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div id="brandModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeBrandModal()">&times;</span>

                        <h2>Thêm thương hiệu</h2>

                        <form id="addBrandForm" enctype="multipart/form-data">
                            <input type="hidden" name="type" value="brand">

                            <div class="form-group">
                                <label>Tên thương hiệu</label>
                                <input type="text" name="name" required>
                            </div>

                            <div class="form-group">
                                <label>Chọn logo</label>
                                <input type="file" name="logo" id="add_brand_logo" accept="image/*">
                            </div>

                            <div class="form-group preview-group">
                                <label>Preview</label>
                                <div class="preview-container">
                                    <img id="add_brand_preview" src="" style="display:none;">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-save">Thêm</button>
                        </form>
                    </div>
                </div>


                <div id="categoryModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeCategoryModal()">&times;</span>

                        <h2>Thêm danh mục</h2>

                        <form id="addCategoryForm">
                            <input type="hidden" name="type" value="category">

                            <label>Tên danh mục</label>
                            <input type="text" id="category_name" name="category_name" autocomplete="off" required>

                            <label>Slug</label>
                            <input type="text" id="slug" name="slug" readonly>

                            <button type="submit" class="btn-primary-save">Thêm</button>
                        </form>
                    </div>
                </div>

                <!-- EDIT BRAND -->
                <div id="editBrandModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeEditBrandModal()">&times;</span>

                        <h2>Sửa thương hiệu</h2>

                        <form id="editBrandForm" enctype="multipart/form-data">
                            <input type="hidden" name="type" value="brand">
                            <input type="hidden" name="id" id="edit_brand_id">

                            <div class="form-group">
                                <label>Tên thương hiệu</label>
                                <input type="text" name="name" id="edit_brand_name" required>
                            </div>

                            <div class="form-group preview-group">
                                <label>Logo hiện tại</label>
                                <div class="preview-container">
                                    <img id="edit_brand_preview" src="">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Chọn ảnh mới</label>
                                <input type="file" name="logo" id="edit_brand_logo" accept="image/*">
                            </div>

                            <button type="submit" class="btn-primary-save">Cập nhật</button>
                        </form>
                    </div>
                </div>

                <!-- EDIT CATEGORY -->
                <div id="editModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeEditModal()">&times;</span>

                        <h2>Sửa danh mục</h2>

                        <form id="editForm">
                            <input type="hidden" name="type" value="category">
                            <input type="hidden" name="id" id="edit_id">

                            <label>Tên danh mục</label>
                            <input type="text" name="name" id="edit_name" required>

                            <label>Slug</label>
                            <input type="text" id="edit_slug" name="slug" readonly>

                            <button type="submit" class="btn-primary-save">Cập nhật</button>
                        </form>
                    </div>
                </div>

                <script>
                    // TOAST NOTIFICATION
                    function showToast(message, type = 'info') {
                        const container = document.getElementById('toastContainer');
                        const toast = document.createElement('div');
                        toast.className = `toast ${type}`;

                        let icon = 'fa-info-circle';
                        if (type === 'success') icon = 'fa-check-circle';
                        if (type === 'error') icon = 'fa-exclamation-circle';

                        toast.innerHTML = `
                            <i class="fas ${icon}"></i>
                            <span>${message}</span>
                        `;

                        container.appendChild(toast);

                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                    }

                    function createSlug(str) {
                        return str
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/đ/g, 'd')
                            .replace(/[^a-z0-9\s-]/g, '')
                            .trim()
                            .replace(/\s+/g, '-');
                    }


                    function openCategoryModal() {
                        document.getElementById("categoryModal").style.display = "block";
                    }

                    function closeCategoryModal() {
                        document.getElementById("categoryModal").style.display = "none";
                        document.getElementById("addCategoryForm").reset();
                        document.getElementById("slug").value = "";
                    }

                    function openBrandModal() {
                        document.getElementById("brandModal").style.display = "block";
                    }

                    function closeBrandModal() {
                        document.getElementById("brandModal").style.display = "none";
                        document.getElementById("addBrandForm").reset();
                        document.getElementById("add_brand_preview").style.display = "none";
                    }

                    document.getElementById("add_brand_logo").addEventListener("change", function(e) {
                        const file = e.target.files[0];
                        const preview = document.getElementById("add_brand_preview");
                        if (file) {
                            preview.src = URL.createObjectURL(file);
                            preview.style.display = "block";
                        } else {
                            preview.style.display = "none";
                        }
                    });

                    const nameInput = document.getElementById('category_name');
                    const slugInput = document.getElementById('slug');

                    if (nameInput) {
                        nameInput.addEventListener('input', function() {
                            slugInput.value = createSlug(this.value);
                        });
                    }
                    // ===== CATEGORY =====
                    function openEditModal(id) {
                        let row = document.querySelector(`[data-id='category-${id}']`);

                        if (!row) {
                            showToast("Không tìm thấy category!", "error");
                            return;
                        }

                        let name = row.querySelector(".category-name").innerText;

                        document.getElementById("editModal").style.display = "block";
                        document.getElementById("edit_id").value = id;
                        document.getElementById("edit_name").value = name;
                        document.getElementById("edit_slug").value = createSlug(name);

                        document.getElementById("edit_name").oninput = function() {
                            document.getElementById("edit_slug").value = createSlug(this.value);
                        };
                    }

                    function closeEditModal() {
                        document.getElementById("editModal").style.display = "none";
                    }

                    // update category
                    document.getElementById("editForm").addEventListener("submit", function(e) {
                        e.preventDefault();

                        let formData = new FormData(this);

                        fetch("update_brand_category.php", {
                                method: "POST",
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    let id = formData.get("id");
                                    let newName = formData.get("name");

                                    let row = document.querySelector(`[data-id='category-${id}']`);
                                    row.querySelector(".category-name").innerText = newName;

                                    closeEditModal();
                                    showToast("Cập nhật danh mục thành công!", "success");
                                } else {
                                    showToast("Lỗi!", "error");
                                }
                            });
                    });


                    // ===== ADD BRAND =====
                    document.getElementById("addBrandForm").addEventListener("submit", function(e) {
                        e.preventDefault();

                        let formData = new FormData(this);

                        fetch("add_brand.php", {
                                method: "POST",
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    closeBrandModal();
                                    showToast("Thêm thương hiệu thành công!", "success");
                                    location.reload();
                                } else {
                                    showToast(data.message || "Lỗi thêm thương hiệu!", "error");
                                }
                            })
                            .catch(err => {
                                console.error("FETCH ERROR:", err);
                                showToast("Không gửi được request!", "error");
                            });
                    });


                    // ===== ADD CATEGORY =====
                    document.getElementById("addCategoryForm").addEventListener("submit", function(e) {
                        e.preventDefault();

                        let formData = new FormData(this);

                        fetch("<?= $_SERVER['PHP_SELF'] ?>", {
                                method: "POST",
                                body: formData
                            })
                            .then(res => {
                                if (res.ok) {
                                    let categoryName = formData.get("category_name");
                                    closeCategoryModal();
                                    showToast("Thêm danh mục thành công!", "success");
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    showToast("Lỗi thêm danh mục!", "error");
                                }
                            })
                            .catch(err => {
                                console.error("FETCH ERROR:", err);
                                showToast("Không gửi được request!", "error");
                            });
                    });


                    // ===== EDIT BRAND ======
                    function openEditBrandModal(id) {
                        let row = document.querySelector(`[data-id='brand-${id}']`);

                        if (!row) {
                            showToast("Không tìm thấy brand!", "error");
                            return;
                        }

                        let name = row.querySelector(".brand-name").innerText;

                        // lấy ảnh (thêm class vào img nếu chưa có)
                        let img = row.querySelector("img")?.getAttribute("src") || "";

                        document.getElementById("editBrandModal").style.display = "block";
                        document.getElementById("edit_brand_id").value = id;
                        document.getElementById("edit_brand_name").value = name;

                        document.getElementById("edit_brand_preview").src = img;
                    }
                    document.getElementById("edit_brand_logo").addEventListener("change", function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            document.getElementById("edit_brand_preview").src = URL.createObjectURL(file);
                        }
                    });

                    function closeEditBrandModal() {
                        document.getElementById("editBrandModal").style.display = "none";
                    }

                    // update brand
                    document.getElementById("editBrandForm").addEventListener("submit", function(e) {
                        e.preventDefault();

                        let formData = new FormData(this);
                        let id = formData.get("id");

                        fetch("update_brand_category.php", {
                                method: "POST",
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    let newName = formData.get("name");
                                    let row = document.querySelector(`[data-id='brand-${id}']`);

                                    // Cập nhật tên
                                    row.querySelector(".brand-name").innerText = newName;

                                    // Cập nhật ảnh nếu có upload ảnh mới
                                    if (data.logo) {
                                        let imgTag = row.querySelector("img");
                                        imgTag.src = "<?= BASE_URL ?>assets/images/brands/" + data.logo + "?t=" + Date.now();
                                    }

                                    closeEditBrandModal();
                                    showToast("Cập nhật  thương hiệu thành công!", "success");
                                } else {
                                    showToast(data.message || "Lỗi cập nhật!", "error");
                                }
                            })
                            .catch(err => {
                                console.error("FETCH ERROR:", err);
                                showToast("Không gửi được request!", "error");
                            });
                    });



                    // click ngoài modal
                    window.onclick = function(e) {
                        ["editModal", "editBrandModal"].forEach(id => {
                            let modal = document.getElementById(id);
                            if (e.target === modal) modal.style.display = "none";
                        });
                    };
                </script>

</body>

</html>