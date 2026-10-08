<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('theloai');
$canManageCategories = hasLibraryPermission('categories_manage');
?>
<div class="catx-page">
  <section class="catx-hero reveal-on-scroll">
    <div class="catx-hero-copy">
      <span class="catx-kicker"><i class="fa-solid fa-layer-group"></i> Category Management Experience</span>
      <h2>Quản lý thể loại</h2>
      <p>Theo dõi cơ cấu kho sách theo thể loại, tìm nhanh nhóm sách nổi bật và thao tác thêm, sửa, xóa thuận tiện hơn mà vẫn giữ nguyên dữ liệu cũ.</p>
      <div class="catx-hero-actions">
        <?php if ($canManageCategories): ?>
          <button class="btn btn-primary" id="categoryAddBtn" type="button"><i class="fa-solid fa-plus me-2"></i>Thêm thể loại</button>
        <?php endif; ?>
        <button class="btn btn-outline-primary" id="categoryExportBtn" type="button"><i class="fa-solid fa-file-export me-2"></i>Xuất dữ liệu</button>
        <button class="btn btn-light" id="categoryRefreshBtn" type="button"><i class="fa-solid fa-rotate-right me-2"></i>Làm mới</button>
      </div>
      <div class="catx-hero-status">
        <span><i class="fa-solid fa-circle-check"></i> Đồng bộ dữ liệu theo thời gian tải trang</span>
        <span id="categoryTodayText"><i class="fa-regular fa-calendar"></i> --</span>
      </div>
    </div>
    <div class="catx-hero-visual" aria-hidden="true">
      <div class="catx-hero-icon"><i class="fa-solid fa-books"></i></div>
      <div class="catx-hero-blob catx-blob-one"></div>
      <div class="catx-hero-blob catx-blob-two"></div>
      <div class="catx-hero-mini-card catx-mini-a">
        <strong id="heroTotalTitles">0</strong>
        <span>Đầu sách</span>
      </div>
      <div class="catx-hero-mini-card catx-mini-b">
        <strong id="heroTotalBooks">0</strong>
        <span>Tổng cuốn</span>
      </div>
      <div class="catx-hero-mini-card catx-mini-c">
        <strong id="heroTopCategory">--</strong>
        <span>Thể loại nổi bật</span>
      </div>
      <div class="catx-hero-stack">
        <div class="catx-stack-card stack-1"><span>Phân loại</span></div>
        <div class="catx-stack-card stack-2"><span>Khám phá</span></div>
        <div class="catx-stack-card stack-3"><span>Kho sách</span></div>
      </div>
    </div>
  </section>

  <section class="catx-metrics reveal-on-scroll">
    <article class="catx-metric metric-blue">
      <div class="catx-metric-copy"><span>Tổng thể loại</span><strong id="categoryCount">0</strong><small>Đang quản lý trong thư viện</small></div>
      <div class="catx-metric-icon"><i class="fa-solid fa-layer-group"></i></div>
      <div class="catx-sparkline"><span></span><span></span><span></span><span></span><span></span></div>
    </article>
    <article class="catx-metric metric-green">
      <div class="catx-metric-copy"><span>Tổng đầu sách</span><strong id="categoryTitleCount">0</strong><small>Tất cả đầu sách đã phân loại</small></div>
      <div class="catx-metric-icon"><i class="fa-solid fa-book"></i></div>
      <div class="catx-sparkline"><span></span><span></span><span></span><span></span><span></span></div>
    </article>
    <article class="catx-metric metric-orange">
      <div class="catx-metric-copy"><span>Tổng số lượng sách</span><strong id="categoryBookCount">0</strong><small>Tổng số cuốn hiện có trong kho</small></div>
      <div class="catx-metric-icon"><i class="fa-solid fa-books"></i></div>
      <div class="catx-sparkline"><span></span><span></span><span></span><span></span><span></span></div>
    </article>
  </section>

  <section class="catx-featured reveal-on-scroll">
    <div class="catx-section-head">
      <div>
        <span class="catx-section-kicker">Khám phá nhanh</span>
        <h3>Thể loại nổi bật</h3>
        <p>Bấm vào từng thẻ để lọc danh sách ngay.</p>
      </div>
      <div class="catx-scroll-hint"><i class="fa-solid fa-arrow-right-long"></i> Kéo ngang để xem thêm</div>
    </div>
    <div class="catx-featured-scroller" id="categoryFeaturedTrack"></div>
  </section>

  <section class="catx-toolbar-card reveal-on-scroll">
    <div class="catx-toolbar-top">
      <label class="catx-search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="categorySearch" type="text" placeholder="Tìm theo mã, tên thể loại hoặc mô tả...">
      </label>
      <div class="catx-toolbar-group">
        <select id="categorySort" class="form-select">
          <option value="newest">Mới thêm</option>
          <option value="many_titles">Nhiều đầu sách</option>
          <option value="few_titles">Ít đầu sách</option>
          <option value="many_books">Nhiều số lượng</option>
          <option value="az">A → Z</option>
        </select>
        <button class="btn btn-outline-primary" id="categoryAdvancedBtn" type="button"><i class="fa-solid fa-sliders me-2"></i>Lọc nâng cao</button>
        <button class="btn btn-light" id="categoryResetFilterBtn" type="button"><i class="fa-solid fa-filter-circle-xmark me-2"></i>Làm mới</button>
      </div>
    </div>
    <div class="catx-chip-row" id="categoryChipRow">
      <button type="button" class="catx-chip active" data-chip="all">Tất cả</button>
      <button type="button" class="catx-chip" data-chip="top_titles">Top đầu sách</button>
      <button type="button" class="catx-chip" data-chip="top_stock">Top số lượng</button>
      <button type="button" class="catx-chip" data-chip="low_books">Ít sách</button>
    </div>
    <div class="catx-subtools">
      <div class="catx-view-switch" role="tablist" aria-label="Chế độ hiển thị">
        <button type="button" class="active" data-view="table"><i class="fa-solid fa-table-list"></i> Bảng</button>
        <button type="button" data-view="card"><i class="fa-solid fa-grip"></i> Card</button>
      </div>
      <div class="catx-result-text" id="categoryResultText">Đang tải dữ liệu...</div>
    </div>
  </section>

  <section class="catx-insights reveal-on-scroll">
    <article class="catx-chart-card">
      <div class="catx-section-head compact">
        <div>
          <span class="catx-section-kicker">Biểu đồ</span>
          <h3>Phân bố kho sách theo thể loại</h3>
        </div>
      </div>
      <div class="catx-chart-wrap">
        <div class="catx-donut-box">
          <div class="catx-donut" id="categoryDonutChart"><div class="catx-donut-center"><strong id="categoryDonutTotal">0</strong><span>cuốn</span></div></div>
        </div>
        <div class="catx-donut-legend" id="categoryDonutLegend"></div>
      </div>
    </article>
    <article class="catx-insight-card">
      <div class="catx-section-head compact"><div><span class="catx-section-kicker">Tổng hợp</span><h3>Top 5 thể loại có nhiều sách nhất</h3></div></div>
      <div class="catx-bar-list" id="categoryTopList"></div>
    </article>
    <article class="catx-insight-card">
      <div class="catx-section-head compact"><div><span class="catx-section-kicker">Cân bằng kho</span><h3>Thể loại ít đầu sách nhất</h3></div></div>
      <div class="catx-mini-list" id="categoryLowList"></div>
    </article>
  </section>

  <section class="catx-main-card reveal-on-scroll">
    <div class="catx-table-wrap" id="categoryTableWrap">
      <table class="table catx-table align-middle">
        <thead>
          <tr>
            <th style="width:72px">STT</th>
            <th>Thể loại</th>
            <th>Mô tả</th>
            <th style="width:140px">Đầu sách</th>
            <th style="width:180px">Tổng số lượng</th>
            <?php if ($canManageCategories): ?><th style="width:190px">Thao tác</th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="categoryTableBody"></tbody>
      </table>
    </div>
    <div class="catx-card-grid d-none" id="categoryCardGrid"></div>
  </section>

  <aside class="catx-drawer" id="categoryDrawer">
    <div class="catx-drawer-backdrop" data-close-drawer></div>
    <div class="catx-drawer-panel">
      <button class="catx-drawer-close" type="button" data-close-drawer><i class="fa-solid fa-xmark"></i></button>
      <div class="catx-drawer-body" id="categoryDrawerBody"></div>
    </div>
  </aside>

  <?php if ($canManageCategories): ?>
  <div class="catx-modal" id="categoryModal">
    <div class="catx-modal-backdrop" data-close-modal></div>
    <div class="catx-modal-panel">
      <div class="catx-modal-head">
        <div>
          <span class="catx-section-kicker">Biểu mẫu</span>
          <h3 id="categoryModalTitle">Thêm thể loại</h3>
        </div>
        <button type="button" class="catx-modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="catx-modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Mã thể loại</label>
            <input id="categoryId" type="text" class="form-control" readonly placeholder="Tự động">
          </div>
          <div class="col-md-8">
            <label class="form-label">Tên thể loại *</label>
            <input id="categoryName" type="text" class="form-control" placeholder="Nhập tên thể loại">
          </div>
          <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea id="categoryDescription" class="form-control" rows="5" placeholder="Nhập mô tả ngắn cho thể loại"></textarea>
          </div>
        </div>
      </div>
      <div class="catx-modal-foot">
        <button class="btn btn-secondary" id="categoryResetBtn" type="button"><i class="fa-solid fa-rotate-right me-2"></i>Làm mới</button>
        <button class="btn btn-primary" id="categorySaveBtn" type="button"><i class="fa-solid fa-floppy-disk me-2"></i>Lưu</button>
        <button class="btn btn-warning text-white" id="categoryUpdateBtn" type="button"><i class="fa-solid fa-pen me-2"></i>Cập nhật</button>
        <button class="btn btn-danger" id="categoryDeleteBtn" type="button"><i class="fa-solid fa-trash me-2"></i>Xóa</button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
