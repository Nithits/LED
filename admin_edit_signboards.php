<?php
session_name('admin_session');
session_start();
include("db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// 1. ดึงข้อมูลทั้งหมดจาก DB
$signs_result = $conn->query("SELECT * FROM sign_boards ORDER BY id DESC");

// 2. เตรียม Array สำหรับเก็บป้ายแต่ละประเภท
$led_signs = [];
$vinyl_signs = [];

if ($signs_result && $signs_result->num_rows > 0) {
    while ($row = $signs_result->fetch_assoc()) {
        if ($row['type'] === 'LED') {
            $led_signs[] = $row;
        } else {
            $vinyl_signs[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการป้ายประชาสัมพันธ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
  <style>
    body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
    .sidebar-container { background-color: #5a4e8c; color: white; min-height: 100vh; position: sticky; top: 0; }
    .sidebar-container a { color: white; text-decoration: none; }
    .sidebar-container .nav-link:hover, .sidebar-container .nav-link.active { background-color: #7c6db3; border-radius: 5px; }
    .main-content { padding: 2rem; }
    .sign-card { background-color: #ffffff; border: none; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; display: flex; flex-direction: column; }
    .sign-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); }
    .card-img-container { position: relative; cursor: pointer; }
    .card-img-top { height: 200px; object-fit: cover; border-radius: 0.75rem 0.75rem 0 0; }
    .card-img-overlay-edit { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); color: white; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; border-radius: 0.75rem 0.75rem 0 0; font-size: 1.2rem; }
    .card-img-container:hover .card-img-overlay-edit { opacity: 1; }
    .card-body { flex-grow: 1; display: flex; flex-direction: column; }
    .card-title { font-weight: 600; margin-bottom: 0.5rem; }
    .card-details { list-style: none; padding: 0; font-size: 0.9rem; color: #6c757d; flex-grow: 1; }
    .card-details li { margin-bottom: 0.5rem; }
    .card-details .fa-solid { width: 20px; text-align: center; margin-right: 8px; color: #5a4e8c; }
    .card-footer { background-color: #ffffff; border-top: 1px solid #f0f2f5; border-radius: 0 0 0.75rem 0.75rem; padding: 0.75rem 1.25rem; }
    .modal-preview-img { max-width: 100%; height: auto; max-height: 250px; object-fit: contain; border-radius: 0.5rem; border: 1px solid #dee2e6; margin-bottom: 1.5rem; }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <?php include("admin_sidebar.php"); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
      
      <?php
      if (isset($_GET['success']) && $_GET['success'] === '1') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fa fa-check-circle me-2"></i>บันทึกข้อมูลสำเร็จ<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
      } elseif (isset($_GET['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fa fa-times-circle me-2"></i>เกิดข้อผิดพลาด: ' . htmlspecialchars($_GET['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
      }
      ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fa-solid fa-pen-to-square me-2"></i>จัดการป้ายประชาสัมพันธ์</h1>
        <div class="btn-toolbar mb-2 mb-md-0 d-flex align-items-center gap-2">
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาด้วยรหัสหรือชื่อป้าย...">
          </div>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSignboardModal">
            <i class="fa fa-plus me-2"></i>เพิ่มป้ายใหม่
          </button>
        </div>
      </div>

      <h3 class="mb-4"><i class="fa-solid fa-lightbulb me-3 text-warning"></i>รายการป้าย LED</h3>
      <div class="row" id="led-signs-container">
<?php if (empty($led_signs)): ?>
    <div class="col-12"><p class="text-muted">ยังไม่มีข้อมูลป้าย LED</p></div>
<?php else: ?>
    <?php foreach ($led_signs as $row):
        $imagePath = 'images/default.jpg';
        if (!empty($row['image_url']) && file_exists('images/' . $row['image_url'])) { $imagePath = 'images/' . $row['image_url']; }
    ?>
    <div class="col-xl-4 col-lg-6 col-md-12 mb-4 sign-card-col">
        <div class="sign-card h-100">
            <div class="card-img-container" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= htmlspecialchars($row['name']) ?>">
                <div class="card-img-overlay-edit"><i class="fa-solid fa-pencil"></i> คลิกเพื่อแก้ไข</div>
            </div>
            <div class="card-body">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title text-primary"><?= htmlspecialchars($row['name']) ?></h5>
                        <span class="badge text-bg-warning ms-2"><?= htmlspecialchars($row['type']) ?></span>
                    </div>
                    <p class="card-subtitle mb-3 text-muted" style="font-weight: 500;"><?= htmlspecialchars($row['code']) ?></p>
                </div>
                <ul class="card-details">
                    <li><i class="fa-solid fa-ruler-combined"></i><?= htmlspecialchars($row['size']) ?></li>
                    <li><i class="fa-solid fa-map-marker-alt"></i><?= htmlspecialchars($row['location']) ?></li>
                </ul>
                <small class="text-muted mt-2">แก้ไขล่าสุด: <?= date("d/m/Y H:i", strtotime($row['updated_at'])) ?></small>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="fa fa-pencil-alt"></i> แก้ไข</button>
                <a href="delete_signboard.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบป้ายนี้?')"><i class="fa fa-trash"></i> ลบ</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
        <div id="no-led-results" class="col-12 text-center text-muted d-none"><p>ไม่พบผลการค้นหา</p></div>
      </div>

      <h3 class="mt-5 mb-4"><i class="fa-solid fa-image me-3 text-info"></i>รายการป้ายไวนิล</h3>
      <div class="row" id="vinyl-signs-container">
        <?php if (empty($vinyl_signs)): ?>
    <div class="col-12"><p class="text-muted">ยังไม่มีข้อมูลป้ายไวนิล</p></div>
<?php else: ?>
    <?php foreach ($vinyl_signs as $row):
        $imagePath = 'images/default.jpg';
        if (!empty($row['image_url']) && file_exists('images/' . $row['image_url'])) { $imagePath = 'images/' . $row['image_url']; }
    ?>
    <div class="col-xl-4 col-lg-6 col-md-12 mb-4 sign-card-col">
       <div class="sign-card h-100">
            <div class="card-img-container" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= htmlspecialchars($row['name']) ?>">
                <div class="card-img-overlay-edit"><i class="fa-solid fa-pencil"></i> คลิกเพื่อแก้ไข</div>
            </div>
            <div class="card-body">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title text-primary"><?= htmlspecialchars($row['name']) ?></h5>
                        <span class="badge text-bg-info ms-2"><?= htmlspecialchars($row['type']) ?></span>
                    </div>
                    <p class="card-subtitle mb-3 text-muted" style="font-weight: 500;"><?= htmlspecialchars($row['code']) ?></p>
                </div>
                <ul class="card-details">
                    <li><i class="fa-solid fa-ruler-combined"></i><?= htmlspecialchars($row['size']) ?></li>
                    <li><i class="fa-solid fa-map-marker-alt"></i><?= htmlspecialchars($row['location']) ?></li>
                </ul>
                <small class="text-muted mt-2">แก้ไขล่าสุด: <?= date("d/m/Y H:i", strtotime($row['updated_at'])) ?></small>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="fa fa-pencil-alt"></i> แก้ไข</button>
                <a href="delete_signboard.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบป้ายนี้?')"><i class="fa fa-trash"></i> ลบ</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
        <div id="no-vinyl-results" class="col-12 text-center text-muted d-none"><p>ไม่พบผลการค้นหา</p></div>
      </div>
    </main>
  </div>
</div>

<div class="modal fade" id="addSignboardModal" tabindex="-1" aria-labelledby="addSignboardLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="insert_signboard.php" method="post" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="addSignboardLabel"><i class="fa fa-plus me-2"></i>เพิ่มป้ายประชาสัมพันธ์</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">รหัสป้าย</label><input type="text" name="code" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">ชื่อป้าย</label><input type="text" name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">ประเภท</label><select name="type" class="form-select" required><option value="Vinyl">Vinyl</option><option value="LED">LED</option></select></div>
            <div class="col-md-6"><label class="form-label">ขนาด</label><input type="text" name="size" class="form-control"></div>
            <div class="col-12"><label class="form-label">ตำแหน่ง</label><input type="text" name="location" class="form-control"></div>
            <div class="col-12"><label class="form-label">อัปโหลดรูปภาพ</label><input type="file" name="image" id="addSignImageInput" class="form-control" accept="image/*"></div>
            <div class="col-12 mt-3 text-center d-none" id="addSignImagePreviewContainer"><img id="addSignImagePreview" src="#" alt="ตัวอย่างรูปภาพ" class="modal-preview-img"/></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary">บันทึก</button></div>
      </div>
    </form>
  </div>
</div>

        <?php
        if ($signs_result && $signs_result->num_rows > 0) {
            $signs_result->data_seek(0);
            while ($row = $signs_result->fetch_assoc()):
                $imagePath = 'images/default.jpg';
                if (!empty($row['image_url']) && file_exists('images/' . $row['image_url'])) { $imagePath = 'images/' . $row['image_url']; }
        ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editLabel<?= $row['id'] ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" action="update_signboard.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $row['id'] ?>">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="editLabel<?= $row['id'] ?>"><i class="fa fa-pencil-alt me-2"></i>แก้ไขป้าย: <?= htmlspecialchars($row['code']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
          <div class="text-center"><img src="<?= $imagePath ?>" class="modal-preview-img edit-image-preview" alt="ภาพตัวอย่าง"></div>
          <div class="row g-3 mt-3">
            <div class="col-md-6"><label class="form-label">รหัสป้าย</label><input type="text" name="code" class="form-control" value="<?= htmlspecialchars($row['code']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">ชื่อป้าย</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">ประเภท</label><select name="type" class="form-select"><option value="Vinyl" <?= $row['type'] === 'Vinyl' ? 'selected' : '' ?>>Vinyl</option><option value="LED" <?= $row['type'] === 'LED' ? 'selected' : '' ?>>LED</option></select></div>
            <div class="col-md-6"><label class="form-label">ขนาด</label><input type="text" name="size" class="form-control" value="<?= htmlspecialchars($row['size']) ?>"></div>
            <div class="col-12"><label class="form-label">ตำแหน่ง</label><input type="text" name="location" class="form-control" value="<?= htmlspecialchars($row['location']) ?>"></div>
            <div class="col-12"><label class="form-label">อัปโหลดรูปใหม่ (หากต้องการเปลี่ยน)</label><input type="file" name="image" class="form-control edit-image-input" accept="image/*"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button></div>
      </div>
    </form>
  </div>
</div>
<?php 
    endwhile; 
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addImageInput = document.getElementById('addSignImageInput');
    const addPreviewContainer = document.getElementById('addSignImagePreviewContainer');
    const addImagePreview = document.getElementById('addSignImagePreview');
    if (addImageInput) {
        addImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    addImagePreview.src = e.target.result;
                    addPreviewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                addPreviewContainer.classList.add('d-none');
            }
        });
    }

    const addModal = document.getElementById('addSignboardModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function () {
            addModal.querySelector('form').reset();
            if (addPreviewContainer) {
                addPreviewContainer.classList.add('d-none');
            }
            if (addImagePreview) {
                addImagePreview.src = '#';
            }
        });
    }

    const editImageInputs = document.querySelectorAll('.edit-image-input');
    editImageInputs.forEach(function(input) {
        input.addEventListener('change', function(event) {
            const modalBody = input.closest('.modal-body');
            const previewImage = modalBody.querySelector('.edit-image-preview');
            const file = event.target.files[0];
            if (file && previewImage) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });

    const searchInput = document.getElementById('searchInput');
    const ledContainer = document.getElementById('led-signs-container');
    const vinylContainer = document.getElementById('vinyl-signs-container');
    const noLedResults = document.getElementById('no-led-results');
    const noVinylResults = document.getElementById('no-vinyl-results');

    searchInput.addEventListener('input', function() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        const filterCards = (container, noResultsMessage) => {
            let visibleCount = 0;
            const cards = container.querySelectorAll('.sign-card-col');
            if (cards.length === 0 && !noResultsMessage.classList.contains('d-none')) {
                // Do nothing if the container was empty to begin with
            } else if (cards.length === 0) {
                 return;
            }

            cards.forEach(function(card) {
                const signName = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const signCode = card.querySelector('.card-subtitle')?.textContent.toLowerCase() || '';
                
                if (signName.includes(searchTerm) || signCode.includes(searchTerm)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            noResultsMessage.classList.toggle('d-none', visibleCount > 0 || cards.length === 0);
        };

        filterCards(ledContainer, noLedResults);
        filterCards(vinylContainer, noVinylResults);
    });
});
</script>
</body>
</html>