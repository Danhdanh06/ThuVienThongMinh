<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('xemtatca');
$type = strtolower(trim((string)($_GET['type'] ?? 'popular')));
$allowedTypes = ['popular', 'new', 'recent'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'popular';
}
if (currentLibraryRoleKey() === 'guest' && $type === 'recent') {
    $type = 'popular';
}
?>
<div class="dashboard-full-list" id="dashboardFullList" data-list-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
    <div class="dashboard-list-toolbar">
        <button type="button" class="dashboard-back-btn" id="dashboardBackBtn"><i class="fa-solid fa-arrow-left"></i> Trang chủ</button>
        <div class="dashboard-list-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="dashboardListSearch" placeholder="Tìm trong danh sách..."></div>
    </div>
    <div class="dashboard-table dashboard-full-table">
        <h2 id="dashboardListHeading">Đang tải...</h2>
        <div class="dashboard-full-table-scroll">
            <table>
                <thead id="dashboardListHead"></thead>
                <tbody id="dashboardListBody"><tr><td>Đang tải dữ liệu...</td></tr></tbody>
            </table>
        </div>
        <div class="dashboard-list-count" id="dashboardListCount"></div>
    </div>
</div>
