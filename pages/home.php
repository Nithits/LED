<?php
include(__DIR__ . '/../db.php');

// ดึงข้อมูลการจองป้ายไวนิล โดยเพิ่ม b.sample_file และเงื่อนไข status = 'in_progress'
$vinyl_boards_today = $conn->query("
    SELECT sb.id, sb.code, sb.location, sb.image_url, b.title, b.start_date, b.end_date, b.sample_file
    FROM sign_boards sb
    LEFT JOIN bookings b ON sb.id = b.sign_board_id 
    AND DATE(b.start_date) <= CURDATE() AND DATE(b.end_date) >= CURDATE()
    AND b.status = 'in_progress'
    WHERE sb.type = 'Vinyl' AND sb.version = 'new'
    ORDER BY sb.id
");

// ดึงข้อมูลการจองป้าย LED โดยเพิ่ม b.sample_file และเงื่อนไข status = 'in_progress'
$led_boards_today = $conn->query("
    SELECT sb.id, sb.code, sb.location, sb.image_url, b.title, b.start_date, b.end_date, b.sample_file
    FROM sign_boards sb
    LEFT JOIN bookings b ON sb.id = b.sign_board_id 
    AND CURDATE() BETWEEN DATE(b.start_date) AND DATE(b.end_date)
    AND b.status = 'in_progress'
    WHERE sb.type = 'LED' AND sb.version = 'new'
    ORDER BY sb.id
");

$vinyl_list_today = [];
while ($row = $vinyl_boards_today->fetch_assoc()) {
    $vinyl_list_today[] = $row;
}

$led_list_today = [];
while ($row = $led_boards_today->fetch_assoc()) {
    $led_list_today[] = $row;
}

// ข้อกำหนดการใช้งาน
$requirement_dir = __DIR__ . '/../images/';
$requirement_url = 'images/';
$requirement_images = $led_images = $vinyl_images = [];

if (is_dir($requirement_dir)) {
    $requirement_images = array_diff(scandir($requirement_dir), array('.', '..'));

    $led_images = array_filter($requirement_images, function($img) {
        return str_starts_with($img, 'rules_led') && preg_match('/\.(jpg|jpeg|png)$/i', $img);
    });

    $vinyl_images = array_filter($requirement_images, function($img) {
        return str_starts_with($img, 'rules_vinyl') && preg_match('/\.(jpg|jpeg|png)$/i', $img);
    });
}
?>

<div class="container bg-white p-4 rounded shadow mt-4">
    <h2 class="fw-bold text-center text-dark mb-3">
        ยินดีต้อนรับเข้าสู่ระบบจองป้ายประชาสัมพันธ์
    </h2>
      <p class="text-center text-secondary fs-5">
        โดยกองประชาสัมพันธ์และกิจการต่างประเทศ มหาวิทยาลัยมหาสารคาม<br>
        กรุณา <a href="#guidelines">ศึกษาแนวปฏิบัติการขอใช้งานป้าย</a> ก่อนทำการจอง เพื่อความถูกต้องและเป็นระเบียบ
      </p>
</div>
<hr class="my-5">

<?php if (count($vinyl_list_today) > 0): ?>
    <h4 class="fw-bold mb-4 text-center fs-3">กิจกรรมประชาสัมพันธ์บนป้ายไวนิลที่มีการแสดงในวันนี้</h4>
    <div id="vinylCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-inner rounded-4 shadow-lg overflow-hidden">
            <?php foreach ($vinyl_list_today as $index => $row): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <?php
                        if (!empty($row['sample_file'])) {
                            $image_path = str_starts_with($row['sample_file'], 'uploads/')
                                ? htmlspecialchars($row['sample_file'])
                                : 'uploads/' . htmlspecialchars($row['sample_file']);
                        } else {
                            $image_path = "images/" . htmlspecialchars($row['image_url'] ?? 'placeholder.jpg');
                        }
                    ?>
                    <img src="<?= $image_path ?>" class="d-block w-100" style="height: 400px; object-fit: contain; background-color: #e0e0e0;">
                    <div class="carousel-caption d-none d-md-block rounded-3 p-3" style="background-color: rgba(0,0,0,0.9);">
                        <h3 class="text-white fw-bold"><?= htmlspecialchars($row['code']) ?></h3>
                        <?php if (!empty($row['title'])): ?>
                            <p class="text-warning fw-bold d-inline-block">
                                <i class="bi bi-pin-map"></i> <?= mb_strimwidth(htmlspecialchars($row['title']), 0, 30, '...') ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-calendar"></i> <?= htmlspecialchars($row['start_date']) ?> - <?= htmlspecialchars($row['end_date']) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-warning fw-bold d-inline-block">ไม่มีการจองกิจกรรมในวันนี้</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#vinylCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-light rounded-circle"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#vinylCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-light rounded-circle"></span>
        </button>
    </div>
<?php endif; ?>

<?php if (count($led_list_today) > 0): ?>
    <h4 class="fw-bold mb-4 text-center fs-3">กิจกรรมประชาสัมพันธ์ที่แสดงบนป้าย LED ในวันนี้</h4>
    <div id="ledCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-inner rounded-4 shadow-lg overflow-hidden">
            <?php foreach ($led_list_today as $index => $row): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <?php
                    $video_extensions = ['mp4', 'webm', 'mov', 'ogv'];
                    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (!empty($row['sample_file'])) {
                        $file_path = str_starts_with($row['sample_file'], 'uploads/')
                            ? htmlspecialchars($row['sample_file'])
                            : 'uploads/' . htmlspecialchars($row['sample_file']);
                        $file_extension = strtolower(pathinfo($row['sample_file'], PATHINFO_EXTENSION));

                        if (in_array($file_extension, $video_extensions)) {
                    ?>
                            <video class="d-block w-100" style="height: 400px; object-fit: contain; background-color: #e0e0e0;" autoplay muted loop playsinline>
                                <source src="<?= $file_path ?>" type="video/<?= $file_extension === 'ogv' ? 'ogg' : $file_extension ?>">
                                เบราว์เซอร์ของคุณไม่รองรับการแสดงวิดีโอ
                            </video>
                    <?php
                        } elseif (in_array($file_extension, $image_extensions)) {
                    ?>
                            <img src="<?= $file_path ?>" class="d-block w-100" style="height: 400px; object-fit: contain; background-color: #e0e0e0;">
                    <?php
                        }
                    } else {
                    ?>
                        <img src="images/<?= htmlspecialchars($row['image_url'] ?? 'placeholder.jpg') ?>" class="d-block w-100" style="height: 400px; object-fit: contain; background-color: #e0e0e0;">
                    <?php
                    }
                    ?>
                    <div class="carousel-caption d-none d-md-block rounded-3 p-3" style="background-color: rgba(0,0,0,0.9);">
                        <h3 class="text-white fw-bold"><?= htmlspecialchars($row['code']) ?></h3>
                        <?php if (!empty($row['title'])): ?>
                            <p class="text-warning fw-bold d-inline-block">
                                <i class="bi bi-pin-map"></i> <?= mb_strimwidth(htmlspecialchars($row['title']), 0, 30, '...') ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-calendar"></i> <?= htmlspecialchars($row['start_date']) ?> - <?= htmlspecialchars($row['end_date']) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-warning fw-bold d-inline-block">ไม่มีการจองกิจกรรมในวันนี้</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#ledCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-light rounded-circle"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#ledCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-light rounded-circle"></span>
        </button>
    </div>
<?php endif; ?>

<?php if (count($vinyl_list_today) === 0 && count($led_list_today) === 0): ?>
    <div class="alert alert-warning text-center mt-4">
        ไม่มีป้ายไวนิลหรือป้าย LED ที่แสดงในวันนี้
    </div>
<?php endif; ?>

<?php if (!empty($led_images) || !empty($vinyl_images)): ?>
<hr class="my-5">
<h4 id="guidelines" class="fw-bold mb-4 text-center mt-5 fs-3">แนวปฏิบัติการจองป้าย</h4>
<div class="card shadow mb-5">
    <div class="card-body">
        <?php if (!empty($led_images)): ?>
            <?php foreach ($led_images as $img): ?>
                <div class="mb-4 text-center">
                    <img src="<?= $requirement_url . htmlspecialchars($img) ?>" class="img-fluid rounded border">
                </div>
            <?php endforeach; ?>
            <hr>
        <?php endif; ?>
        <?php if (!empty($vinyl_images)): ?>
            <?php foreach ($vinyl_images as $img): ?>
                <div class="mb-4 text-center">
                    <img src="<?= $requirement_url . htmlspecialchars($img) ?>" class="img-fluid rounded border">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="autoModal" tabindex="-1" aria-labelledby="autoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="autoModalLabel">คำแนะนำการใช้งานระบบจองป้ายประชาสัมพันธ์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    ระบบจองป้ายประชาสัมพันธ์จัดทำขึ้นเพื่ออำนวยความสะดวกให้กับหน่วยงานภายในมหาวิทยาลัยมหาสารคาม 
                    ในการขอใช้พื้นที่สำหรับเผยแพร่ข้อมูลข่าวสารหรือกิจกรรมต่าง ๆ ผ่านสื่อประชาสัมพันธ์ประเภทป้ายไวนิลและป้าย LED 
                    โดยมีจุดมุ่งหมายเพื่อให้การสื่อสารภายในมหาวิทยาลัยเป็นไปอย่างทั่วถึงและเหมาะสม
                </p>
                <p>
                    ผู้ใช้งานสามารถเลือกประเภทของป้ายตามความเหมาะสมของกิจกรรม พร้อมกรอกข้อมูลและช่วงเวลาที่ต้องการใช้งาน 
                    ทั้งนี้ขอความร่วมมือให้ตรวจสอบข้อมูลก่อนทำการจอง และปฏิบัติตามขั้นตอนที่ระบบกำหนดไว้ 
                    เพื่อให้การใช้งานเป็นไปอย่างมีระเบียบและเกิดประโยชน์สูงสุดต่อส่วนรวม
                </p>
                <ul>
                    <li>เลือกประเภทป้ายให้เหมาะสม (LED / ไวนิล)</li>
                    <li>ตรวจสอบช่วงวันที่ยังว่างก่อนทำการจอง</li>
                    <li>ปฏิบัติตามแนวปฏิบัติที่แสดงไว้ด้านล่างของหน้า</li>
                </ul>
                <p class="mt-3">
                    หรือ <a href="documents/guidelines.pdf" target="_blank" class="text-decoration-underline">คลิกที่นี่เพื่อดูแนวปฏิบัติ (PDF)</a> อย่างละเอียด
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">เข้าใจแล้ว</button>
            </div>
        </div>
    </div>
</div>

<script>
// === โค้ดสำหรับหน้า home.php ===
window.addEventListener('load', function () {
    const pageSpecificKey = 'modalShownFor_' + window.location.pathname;
    if (!sessionStorage.getItem(pageSpecificKey)) {
        const modalElement = document.getElementById('autoModal'); 
        if (modalElement) {
            const bsModal = new bootstrap.Modal(modalElement);
            bsModal.show();
            sessionStorage.setItem(pageSpecificKey, 'true');
        }
    }
});
</script>