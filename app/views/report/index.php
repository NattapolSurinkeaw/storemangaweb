<?php
/**
 * View: report/index.php
 * หน้ารายงานการซื้อเข้าและขายออก พร้อมกราฟแท่ง
 */
?>
<!-- โหลด Chart.js สำหรับการแสดงผลกราฟแท่ง -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<div class="report-page">
    <!-- Page Header -->
    <div class="page-title d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart-line-fill"></i>
                <h3 class="mb-0">รายงานการซื้อเข้าและขายออก</h3>
            </div>
            <div class="subtitle mt-1 text-muted small">
                เปรียบเทียบยอดซื้อ ยอดขาย กำไรสุทธิ และจำนวนเล่ม พร้อมกราฟแท่งสรุปผล
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" onclick="window.print()">
                <i class="bi bi-printer"></i>
                <span>พิมพ์รายงาน</span>
            </button>
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i>
                    <span>ส่งออก CSV</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item py-2" href="<?= BASE_URL ?>/index.php?url=report/export&type=summary&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&group_by=<?= urlencode($groupBy) ?>&channel=<?= urlencode($channel) ?>">
                            <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>สรุปเปรียบเทียบตามช่วงเวลา
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= BASE_URL ?>/index.php?url=report/export&type=purchases&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                            <i class="bi bi-bag-check me-2 text-info"></i>รายการซื้อเข้า
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= BASE_URL ?>/index.php?url=report/export&type=sales&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&channel=<?= urlencode($channel) ?>">
                            <i class="bi bi-cash-coin me-2 text-success"></i>รายการขายออก
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card filter-card mb-4 print-hide">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= BASE_URL ?>/index.php" id="reportFilterForm">
                <input type="hidden" name="url" value="report">
                <input type="hidden" name="preset" id="inputPreset" value="<?= htmlspecialchars($preset) ?>">

                <!-- Preset Quick Buttons -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-semibold"><i class="bi bi-calendar3 me-1"></i>ช่วงเวลา:</span>
                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                            <button type="button" class="btn btn-preset <?= $preset === 'all' ? 'active' : '' ?>" onclick="applyPreset('all')">ทั้งหมด</button>
                            <button type="button" class="btn btn-preset <?= $preset === 'this_year' ? 'active' : '' ?>" onclick="applyPreset('this_year')">ปีนี้ (<?= date('Y') ?>)</button>
                            <button type="button" class="btn btn-preset <?= $preset === 'this_month' ? 'active' : '' ?>" onclick="applyPreset('this_month')">เดือนนี้</button>
                            <button type="button" class="btn btn-preset <?= $preset === 'last_30_days' ? 'active' : '' ?>" onclick="applyPreset('last_30_days')">30 วันล่าสุด</button>
                            <button type="button" class="btn btn-preset <?= $preset === 'custom' ? 'active' : '' ?>" onclick="applyPreset('custom')">กำหนดเอง</button>
                        </div>
                    </div>

                    <?php if (!empty($startDate) || !empty($endDate) || $channel !== 'all' || $groupBy !== 'month'): ?>
                        <a href="<?= BASE_URL ?>/index.php?url=report" class="btn btn-link btn-sm text-decoration-none text-muted p-0">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>ล้างตัวกรอง
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Inputs row -->
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">จากวันที่</label>
                        <input type="date" name="start_date" id="inputStartDate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($startDate) ?>" onchange="onDateChanged()">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">ถึงวันที่</label>
                        <input type="date" name="end_date" id="inputEndDate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($endDate) ?>" onchange="onDateChanged()">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted mb-1">จัดกลุ่มตาม</label>
                        <select name="group_by" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="month" <?= $groupBy === 'month' ? 'selected' : '' ?>>รายเดือน</option>
                            <option value="day"   <?= $groupBy === 'day' ? 'selected' : '' ?>>รายวัน</option>
                            <option value="year"  <?= $groupBy === 'year' ? 'selected' : '' ?>>รายปี</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted mb-1">ช่องทางขาย</label>
                        <select name="channel" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all"      <?= $channel === 'all' ? 'selected' : '' ?>>ทุกช่องทาง</option>
                            <option value="shopee"   <?= $channel === 'shopee' ? 'selected' : '' ?>>Shopee</option>
                            <option value="facebook" <?= $channel === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel-fill me-1"></i>กรองข้อมูล
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filter Badge Alert (Print & Web) -->
    <div class="report-header-print d-none d-print-block mb-3">
        <h4 class="fw-bold mb-1"><?= htmlspecialchars(APP_NAME) ?> — รายงานสรุปการซื้อเข้าและขายออก</h4>
        <div class="text-muted small">
            ช่วงเวลา: <?= !empty($startDate) ? htmlspecialchars($startDate) : 'ตั้งแต่เริ่มต้น' ?> ถึง <?= !empty($endDate) ? htmlspecialchars($endDate) : 'ปัจจุบัน' ?>
            | พิมพ์เมื่อ: <?= date('d/m/Y H:i') ?>
        </div>
        <hr class="my-2">
    </div>

    <!-- KPI Summary Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- ยอดซื้อเข้า -->
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-purchase shadow-sm h-100">
                <div class="stat-label d-flex justify-content-between align-items-center">
                    <span>ยอดซื้อเข้าทั้งหมด</span>
                    <i class="bi bi-bag-arrow-down" style="font-size:1.1rem; opacity:0.8;"></i>
                </div>
                <div class="stat-value"><?= number_format($summary['purchases']['total_amount'], 2) ?> <small style="font-size:0.9rem;">฿</small></div>
                <div class="stat-sub text-white-50 mt-1" style="font-size:0.75rem;">
                    <?= $summary['purchases']['bill_count'] ?> บิล &bull; รวม <?= number_format($summary['purchases']['books_count']) ?> เล่ม
                    (ค่าส่ง <?= number_format($summary['purchases']['shipping'], 2) ?> ฿)
                </div>
            </div>
        </div>

        <!-- ยอดขายออก -->
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-sales shadow-sm h-100">
                <div class="stat-label d-flex justify-content-between align-items-center">
                    <span>ยอดขายออกรวม</span>
                    <i class="bi bi-cash-coin" style="font-size:1.1rem; opacity:0.8;"></i>
                </div>
                <div class="stat-value"><?= number_format($summary['sales']['total_amount'], 2) ?> <small style="font-size:0.9rem;">฿</small></div>
                <div class="stat-sub text-white-50 mt-1" style="font-size:0.75rem;">
                    <?= $summary['sales']['sales_count'] ?> รายการ &bull; ขายได้ <?= number_format($summary['sales']['books_sold']) ?> เล่ม
                    (สุทธิ <?= number_format($summary['sales']['net_revenue'], 2) ?> ฿)
                </div>
            </div>
        </div>

        <!-- กำไรสุทธิ -->
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-profit shadow-sm h-100">
                <div class="stat-label d-flex justify-content-between align-items-center">
                    <span>กำไรสุทธิรวม</span>
                    <i class="bi bi-graph-up-arrow" style="font-size:1.1rem; opacity:0.8;"></i>
                </div>
                <div class="stat-value"><?= number_format($summary['sales']['profit'], 2) ?> <small style="font-size:0.9rem;">฿</small></div>
                <div class="stat-sub text-white-50 mt-1" style="font-size:0.75rem;">
                    อัตรากำไร <?= number_format($summary['sales']['margin'], 1) ?>% &bull; ROI <?= number_format($summary['sales']['roi'], 1) ?>%
                </div>
            </div>
        </div>

        <!-- กระแสเงินสด หรือ ค่าธรรมเนียม -->
        <div class="col-6 col-xl-3">
            <div class="stat-card <?= $summary['cash_flow'] >= 0 ? 'stat-cashflow-pos' : 'stat-fee' ?> shadow-sm h-100">
                <div class="stat-label d-flex justify-content-between align-items-center">
                    <span>ส่วนต่างเงินสด (ขาย - ซื้อ)</span>
                    <i class="bi bi-arrow-left-right" style="font-size:1.1rem; opacity:0.8;"></i>
                </div>
                <div class="stat-value">
                    <?= ($summary['cash_flow'] >= 0 ? '+' : '') . number_format($summary['cash_flow'], 2) ?> <small style="font-size:0.9rem;">฿</small>
                </div>
                <div class="stat-sub text-white-50 mt-1" style="font-size:0.75rem;">
                    ค่าธรรมเนียมแพลตฟอร์มรวม: <?= number_format($summary['sales']['fee_amount'], 2) ?> ฿
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Chart Card (กราฟแท่งเปรียบเทียบ) -->
    <div class="card shadow-sm mb-4 chart-card">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="chart-icon-box">
                    <i class="bi bi-bar-chart-fill text-primary"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold" id="chartCardTitle">กราฟแท่งเปรียบเทียบการซื้อเข้า vs ขายออก</h5>
                    <small class="text-muted">
                        แสดงข้อมูลตามช่วงเวลา (<?= $groupBy === 'month' ? 'รายเดือน' : ($groupBy === 'day' ? 'รายวัน' : 'รายปี') ?>)
                    </small>
                </div>
            </div>

            <!-- Mode Selector for Chart -->
            <div class="chart-modes btn-group btn-group-sm print-hide" role="group">
                <button type="button" class="btn btn-outline-primary active" id="btnModeMoney" onclick="setChartMode('money')">
                    <i class="bi bi-currency-dollar me-1"></i>ยอดเงิน (฿)
                </button>
                <button type="button" class="btn btn-outline-primary" id="btnModeQty" onclick="setChartMode('quantity')">
                    <i class="bi bi-journal-text me-1"></i>จำนวนเล่ม (เล่ม)
                </button>
                <button type="button" class="btn btn-outline-primary" id="btnModeProfit" onclick="setChartMode('profit')">
                    <i class="bi bi-trophy me-1"></i>กำไร & กระแสเงินสด (฿)
                </button>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <?php if (empty($timeline)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bar-chart fs-1 opacity-50 d-block mb-2"></i>
                    <p class="mb-0">ไม่มีข้อมูลในช่วงเวลาที่เลือก</p>
                </div>
            <?php else: ?>
                <div class="chart-container" style="position: relative; height: 350px; width: 100%;">
                    <canvas id="reportBarChart"></canvas>
                </div>
                <!-- Custom Legend / Quick Metrics Bar -->
                <div class="d-flex flex-wrap justify-content-center gap-4 mt-3 pt-3 border-top small text-muted" id="chartLegendSummary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot" style="background-color: #3B82F6;"></span>
                        <span>ซื้อเข้า: <strong><?= number_format($summary['purchases']['total_amount'], 2) ?> ฿</strong> (<?= number_format($summary['purchases']['books_count']) ?> เล่ม)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot" style="background-color: #10B981;"></span>
                        <span>ขายออก: <strong><?= number_format($summary['sales']['total_amount'], 2) ?> ฿</strong> (<?= number_format($summary['sales']['books_sold']) ?> เล่ม)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot" style="background-color: #F59E0B;"></span>
                        <span>กำไรสุทธิ: <strong><?= number_format($summary['sales']['profit'], 2) ?> ฿</strong></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Channel Breakdown Banner (If sales exist) -->
    <?php if (!empty($summary['sales']['by_channel'])): ?>
    <div class="row g-3 mb-4">
        <?php foreach ($summary['sales']['by_channel'] as $ch): 
            $isShopee = ($ch['channel'] === 'shopee');
            $pct = $summary['sales']['total_amount'] > 0 ? round(($ch['channel_sales'] / $summary['sales']['total_amount']) * 100, 1) : 0;
        ?>
        <div class="col-md-6">
            <div class="card p-3 border-0 bg-white shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $isShopee ? 'badge-channel-shopee' : 'badge-channel-fb' ?> px-2 py-1">
                            <?= $isShopee ? 'SHOPEE' : 'FACEBOOK' ?>
                        </span>
                        <span class="fw-bold"><?= $ch['tx_count'] ?> รายการขาย</span>
                    </div>
                    <span class="text-muted small"><?= $pct ?>% ของยอดขายรวม</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar <?= $isShopee ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>ยอดขาย: <strong class="text-dark"><?= number_format($ch['channel_sales'], 2) ?> ฿</strong></span>
                    <span>ค่าธรรมเนียม: <strong class="text-dark"><?= number_format($ch['channel_fee'], 2) ?> ฿</strong></span>
                    <span>กำไร: <strong class="text-success"><?= number_format($ch['channel_profit'], 2) ?> ฿</strong></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Detailed Tabs Section -->
    <div class="card shadow-sm mb-4 detail-tabs-card">
        <div class="card-header bg-transparent p-2 border-bottom print-hide">
            <ul class="nav nav-pills card-header-pills" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-3 py-2 fw-semibold" id="tab-summary-btn" data-bs-toggle="tab" data-bs-target="#tab-summary" type="button" role="tab">
                        <i class="bi bi-calendar3-range me-1"></i> สรุปเปรียบเทียบตามช่วงเวลา
                        <span class="badge bg-primary-subtle text-primary rounded-pill ms-1"><?= count($timeline) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 py-2 fw-semibold" id="tab-purchases-btn" data-bs-toggle="tab" data-bs-target="#tab-purchases" type="button" role="tab">
                        <i class="bi bi-bag-check me-1"></i> รายการซื้อเข้า
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1"><?= count($purchases) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 py-2 fw-semibold" id="tab-sales-btn" data-bs-toggle="tab" data-bs-target="#tab-sales" type="button" role="tab">
                        <i class="bi bi-cash-stack me-1"></i> รายการขายออก
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1"><?= count($sales) ?></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="reportTabsContent">
                <!-- TAB 1: สรุปเปรียบเทียบตามช่วงเวลา -->
                <div class="tab-pane fade show active p-3 p-md-4" id="tab-summary" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-table me-1 text-primary"></i>ตารางเปรียบเทียบราย<?= $groupBy === 'month' ? 'เดือน' : ($groupBy === 'day' ? 'วัน' : 'ปี') ?>
                        </h6>
                        <span class="text-muted small">รวมทั้งหมด <?= count($timeline) ?> ช่วงเวลา</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-report">
                            <thead class="table-light">
                                <tr>
                                    <th>ช่วงเวลา</th>
                                    <th class="text-end">ยอดซื้อเข้า</th>
                                    <th class="text-center">ซื้อ (เล่ม)</th>
                                    <th class="text-end">ยอดขายออก</th>
                                    <th class="text-center">ขาย (เล่ม)</th>
                                    <th class="text-end">ค่าธรรมเนียม</th>
                                    <th class="text-end">ต้นทุนขาย</th>
                                    <th class="text-end">กำไรสุทธิ</th>
                                    <th class="text-end">% กำไร</th>
                                    <th class="text-end">กระแสเงินสดสุทธิ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($timeline)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">ไม่มีข้อมูลในช่วงเวลาที่เลือก</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($timeline as $t): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($t['label']) ?></td>
                                            <td class="text-end font-mono">
                                                <?= $t['purchase_amount'] > 0 ? number_format($t['purchase_amount'], 2) . ' ฿' : '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $t['books_purchased'] > 0 ? number_format($t['books_purchased']) : '-' ?>
                                            </td>
                                            <td class="text-end font-mono fw-semibold text-primary">
                                                <?= $t['sales_amount'] > 0 ? number_format($t['sales_amount'], 2) . ' ฿' : '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $t['books_sold'] > 0 ? number_format($t['books_sold']) : '-' ?>
                                            </td>
                                            <td class="text-end font-mono text-danger">
                                                <?= $t['fee_amount'] > 0 ? number_format($t['fee_amount'], 2) . ' ฿' : '-' ?>
                                            </td>
                                            <td class="text-end font-mono text-muted">
                                                <?= $t['cost_amount'] > 0 ? number_format($t['cost_amount'], 2) . ' ฿' : '-' ?>
                                            </td>
                                            <td class="text-end font-mono fw-bold <?= $t['profit_amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $t['sales_amount'] > 0 ? number_format($t['profit_amount'], 2) . ' ฿' : '-' ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($t['sales_amount'] > 0): ?>
                                                    <span class="badge <?= $t['profit_percent'] >= 20 ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $t['profit_percent'] ?>%
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end font-mono fw-semibold <?= $t['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= ($t['net_cash_flow'] >= 0 ? '+' : '') . number_format($t['net_cash_flow'], 2) ?> ฿
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($timeline)): ?>
                            <tfoot class="table-group-divider fw-bold bg-light">
                                <tr>
                                    <td>รวมทั้งหมด</td>
                                    <td class="text-end font-mono"><?= number_format($summary['purchases']['total_amount'], 2) ?> ฿</td>
                                    <td class="text-center"><?= number_format($summary['purchases']['books_count']) ?></td>
                                    <td class="text-end font-mono text-primary"><?= number_format($summary['sales']['total_amount'], 2) ?> ฿</td>
                                    <td class="text-center"><?= number_format($summary['sales']['books_sold']) ?></td>
                                    <td class="text-end font-mono text-danger"><?= number_format($summary['sales']['fee_amount'], 2) ?> ฿</td>
                                    <td class="text-end font-mono text-muted"><?= number_format($summary['sales']['cost_amount'], 2) ?> ฿</td>
                                    <td class="text-end font-mono text-success"><?= number_format($summary['sales']['profit'], 2) ?> ฿</td>
                                    <td class="text-end"><?= number_format($summary['sales']['margin'], 1) ?>%</td>
                                    <td class="text-end font-mono <?= $summary['cash_flow'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($summary['cash_flow'] >= 0 ? '+' : '') . number_format($summary['cash_flow'], 2) ?> ฿
                                    </td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: รายการซื้อเข้า -->
                <div class="tab-pane fade p-3 p-md-4" id="tab-purchases" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-bag-check me-1 text-info"></i>รายการบิลซื้อเข้า
                        </h6>
                        <span class="text-muted small">ทั้งหมด <?= count($purchases) ?> บิล</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-report">
                            <thead class="table-light">
                                <tr>
                                    <th>วันที่</th>
                                    <th>รหัสบิล</th>
                                    <th>เรื่อง / รายการ</th>
                                    <th class="text-center">จำนวนเล่ม</th>
                                    <th class="text-end">ค่าส่ง</th>
                                    <th class="text-end">ยอดรวมบิล</th>
                                    <th>หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($purchases)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">ไม่มีรายการซื้อเข้าในช่วงเวลานี้</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($purchases as $p): ?>
                                        <tr>
                                            <td class="text-nowrap"><?= htmlspecialchars($p['purchase_date']) ?></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm view-purchase-btn font-mono d-inline-flex align-items-center gap-1"
                                                        data-id="<?= (int) $p['id'] ?>"
                                                        data-code="<?= htmlspecialchars($p['purchase_code']) ?>"
                                                        title="คลิกดูคำสั่งซื้อ <?= htmlspecialchars($p['purchase_code']) ?>">
                                                    <i class="bi bi-receipt text-primary"></i>
                                                    <span><?= htmlspecialchars($p['purchase_code']) ?></span>
                                                </button>
                                            </td>
                                            <td style="max-width: 280px;">
                                                <div class="text-truncate fw-semibold text-dark" title="<?= htmlspecialchars($p['item_titles'] ?? '') ?>">
                                                    <?= htmlspecialchars($p['item_titles'] ?? '-') ?>
                                                </div>
                                            </td>
                                            <td class="text-center fw-semibold"><?= (int) $p['total_book_count'] ?> เล่ม</td>
                                            <td class="text-end font-mono text-muted"><?= number_format((float) $p['total_shipping_cost'], 2) ?> ฿</td>
                                            <td class="text-end font-mono fw-bold text-dark"><?= number_format((float) $p['grand_total'], 2) ?> ฿</td>
                                            <td class="text-muted small"><?= htmlspecialchars($p['note'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: รายการขายออก -->
                <div class="tab-pane fade p-3 p-md-4" id="tab-sales" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cash-coin me-1 text-success"></i>รายการขายออก
                        </h6>
                        <span class="text-muted small">ทั้งหมด <?= count($sales) ?> รายการ</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-report">
                            <thead class="table-light">
                                <tr>
                                    <th>วันที่</th>
                                    <th>รายการสินค้า</th>
                                    <th class="text-center">ช่องทาง</th>
                                    <th class="text-center">จำนวนเล่ม</th>
                                    <th class="text-end">ราคาขาย</th>
                                    <th class="text-end">ค่าธรรมเนียม</th>
                                    <th class="text-end">ต้นทุน</th>
                                    <th class="text-end">กำไรสุทธิ</th>
                                    <th class="text-end">% กำไร</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">ไม่มีรายการขายออกในช่วงเวลานี้</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td class="text-nowrap"><?= htmlspecialchars($s['sale_date']) ?></td>
                                            <td class="fw-semibold text-dark" style="max-width: 250px;">
                                                <div><?= htmlspecialchars($s['title_snapshot']) ?></div>
                                                <?php
                                                $sBills = [];
                                                if (!empty($s['purchase_bills'])) {
                                                    foreach (explode('||', $s['purchase_bills']) as $billStr) {
                                                        $parts = explode(':', $billStr, 2);
                                                        if (count($parts) === 2) {
                                                            $sBills[] = ['id' => (int) $parts[0], 'code' => $parts[1]];
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($sBills)): ?>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <?php foreach ($sBills as $bill): ?>
                                                            <button type="button" class="btn btn-sm view-purchase-btn py-0 px-1 font-mono"
                                                                    data-id="<?= $bill['id'] ?>"
                                                                    data-code="<?= htmlspecialchars($bill['code']) ?>"
                                                                    title="คลิกดูคำสั่งซื้อ <?= htmlspecialchars($bill['code']) ?>"
                                                                    style="font-size: 0.72rem;">
                                                                <i class="bi bi-receipt text-primary me-1"></i><?= htmlspecialchars($bill['code']) ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $s['channel'] === 'shopee' ? 'badge-channel-shopee' : 'badge-channel-fb' ?>">
                                                    <?= strtoupper($s['channel']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?= (int) $s['book_count'] ?> เล่ม</td>
                                            <td class="text-end font-mono fw-semibold"><?= number_format((float) $s['sale_price'], 2) ?> ฿</td>
                                            <td class="text-end font-mono text-danger"><?= number_format((float) $s['fee_amount'], 2) ?> ฿</td>
                                            <td class="text-end font-mono text-muted"><?= number_format((float) $s['cost_amount'], 2) ?> ฿</td>
                                            <td class="text-end font-mono fw-bold text-success"><?= number_format((float) $s['profit_amount'], 2) ?> ฿</td>
                                            <td class="text-end">
                                                <span class="badge <?= ($s['profit_margin_percent'] ?? 0) >= 20 ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $s['profit_margin_percent'] ?? 0 ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: รายละเอียดคำสั่งซื้อ / บิลซื้อ -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="purchaseModalLabel">รายละเอียดคำสั่งซื้อ</h5>
                        <div class="small text-white-50" id="purchaseModalSub">ข้อมูลรายการบิลและการคิดต้นทุน</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="purchaseDetailBody" style="background: #f8fafc; min-height: 250px;">
                <div class="text-center text-muted py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                    <span>กำลังโหลดข้อมูลคำสั่งซื้อ...</span>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between align-items-center">
                <div id="purchaseModalFooterActions"></div>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- สไตล์เฉพาะหน้ารายงาน -->
<style>
/* Purchase detail button & modal */
.view-purchase-btn {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #1e293b;
    font-size: 0.82rem;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.view-purchase-btn:hover {
    background-color: #e0e7ff;
    border-color: #6366f1;
    color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.15);
}
.slip-thumbnail:hover img {
    transform: scale(1.04);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
}
/* Preset buttons */
.btn-preset {
    border: 1px solid var(--color-border);
    background: #FFFFFF;
    color: var(--color-ink-light);
    font-size: 0.82rem;
    font-weight: 500;
    transition: all 0.15s ease;
}
.btn-preset:hover {
    background: var(--color-surface-subtle);
    border-color: var(--color-border-dark);
}
.btn-preset.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
    font-weight: 600;
}

/* Stat card cashflow */
.stat-cashflow-pos {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

/* Chart Card */
.chart-card {
    border-radius: var(--radius-lg);
    background: #FFFFFF;
    border: 1px solid var(--color-border);
}
.chart-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--color-primary-tint);
    display: flex;
    align-items: center;
    justify-content: center;
}
.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

/* Channel badges */
.badge-channel-shopee {
    background-color: #EE4D2D !important;
    color: #FFFFFF !important;
}
.badge-channel-fb {
    background-color: #1877F2 !important;
    color: #FFFFFF !important;
}

/* Table report */
.table-report th {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--color-ink-soft);
}
.table-report td {
    font-size: 0.9rem;
    padding: 0.65rem 0.75rem;
}

/* Tabs */
.detail-tabs-card .nav-pills .nav-link {
    color: var(--color-ink-light);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
}
.detail-tabs-card .nav-pills .nav-link.active {
    background: var(--color-primary);
    color: #FFFFFF;
}
.detail-tabs-card .nav-pills .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #FFFFFF !important;
}

/* Print CSS */
@media print {
    body {
        background: #FFFFFF !important;
        color: #000000 !important;
        font-size: 12px !important;
    }
    .sidebar, .topbar, .bottom-nav, .print-hide, .btn, .dropdown, .sidebar-foot {
        display: none !important;
    }
    .app-main, .content-area {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .card {
        border: 1px solid #CCCCCC !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    .stat-card {
        border: 1px solid #333333 !important;
        color: #000000 !important;
        background: #F8FAFC !important;
        box-shadow: none !important;
    }
    .stat-card .stat-value, .stat-card .stat-label {
        color: #000000 !important;
    }
    .stat-card .stat-sub {
        color: #555555 !important;
    }
    .table-report th, .table-report td {
        padding: 4px 6px !important;
        font-size: 11px !important;
    }
    .chart-container {
        height: 260px !important;
    }
}
</style>

<!-- Chart.js Logic Script -->
<script>
const BASE_URL = "<?= BASE_URL ?>";

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ข้อมูล Timeline จาก PHP
const timelineData = <?= json_encode($timeline, JSON_UNESCAPED_UNICODE) ?>;
let reportChartInstance = null;
let currentChartMode = 'money'; // 'money' | 'quantity' | 'profit'

function initReportChart() {
    const canvas = document.getElementById('reportBarChart');
    if (!canvas || !timelineData || timelineData.length === 0) return;

    const ctx = canvas.getContext('2d');
    const labels = timelineData.map(item => item.label);

    const chartConfig = {
        type: 'bar',
        data: getChartDatasets(currentChartMode, labels),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 16,
                        font: {
                            family: "'Noto Sans Thai', 'Plus Jakarta Sans', sans-serif",
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleFont: {
                        family: "'Noto Sans Thai', 'Plus Jakarta Sans', sans-serif",
                        size: 13,
                        weight: '700'
                    },
                    bodyFont: {
                        family: "'JetBrains Mono', monospace",
                        size: 12
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                if (currentChartMode === 'quantity') {
                                    label += new Intl.NumberFormat('th-TH').format(context.parsed.y) + ' เล่ม';
                                } else {
                                    label += new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y) + ' ฿';
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            family: "'Noto Sans Thai', 'Plus Jakarta Sans', sans-serif",
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.7)',
                    },
                    ticks: {
                        font: {
                            family: "'JetBrains Mono', monospace",
                            size: 11
                        },
                        callback: function(value) {
                            if (currentChartMode === 'quantity') {
                                return value + ' เล่ม';
                            }
                            if (Math.abs(value) >= 1000) {
                                return (value / 1000).toLocaleString('th-TH') + 'k';
                            }
                            return value.toLocaleString('th-TH');
                        }
                    }
                }
            }
        }
    };

    reportChartInstance = new Chart(ctx, chartConfig);
}

function getChartDatasets(mode, labels) {
    if (mode === 'quantity') {
        return {
            labels: labels,
            datasets: [
                {
                    label: 'เล่มที่ซื้อเข้า',
                    data: timelineData.map(it => it.books_purchased),
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    hoverBackgroundColor: '#2563EB',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'เล่มที่ขายออก',
                    data: timelineData.map(it => it.books_sold),
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    hoverBackgroundColor: '#059669',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        };
    } else if (mode === 'profit') {
        return {
            labels: labels,
            datasets: [
                {
                    label: 'กำไรสุทธิ (฿)',
                    data: timelineData.map(it => it.profit_amount),
                    backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    hoverBackgroundColor: '#D97706',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'กระแสเงินสดสุทธิ (ขาย - ซื้อ)',
                    data: timelineData.map(it => it.net_cash_flow),
                    backgroundColor: timelineData.map(it => it.net_cash_flow >= 0 ? 'rgba(16, 185, 129, 0.75)' : 'rgba(239, 68, 68, 0.75)'),
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        };
    }

    // Default: 'money'
    return {
        labels: labels,
        datasets: [
            {
                label: 'ยอดซื้อเข้า (฿)',
                data: timelineData.map(it => it.purchase_amount),
                backgroundColor: 'rgba(59, 130, 246, 0.85)',
                hoverBackgroundColor: '#2563EB',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'ยอดขายออก (฿)',
                data: timelineData.map(it => it.sales_amount),
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                hoverBackgroundColor: '#059669',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'กำไรสุทธิ (฿)',
                data: timelineData.map(it => it.profit_amount),
                backgroundColor: 'rgba(245, 158, 11, 0.85)',
                hoverBackgroundColor: '#D97706',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    };
}

function setChartMode(mode) {
    currentChartMode = mode;

    // update buttons state
    document.getElementById('btnModeMoney').classList.toggle('active', mode === 'money');
    document.getElementById('btnModeQty').classList.toggle('active', mode === 'quantity');
    document.getElementById('btnModeProfit').classList.toggle('active', mode === 'profit');

    const titleEl = document.getElementById('chartCardTitle');
    if (mode === 'quantity') {
        titleEl.textContent = 'กราฟแท่งเปรียบเทียบจำนวนเล่ม (ซื้อเข้า vs ขายออก)';
    } else if (mode === 'profit') {
        titleEl.textContent = 'กราฟแท่งเปรียบเทียบกำไรสุทธิ & กระแสเงินสด';
    } else {
        titleEl.textContent = 'กราฟแท่งเปรียบเทียบการซื้อเข้า vs ขายออก';
    }

    if (reportChartInstance) {
        const labels = timelineData.map(item => item.label);
        reportChartInstance.data = getChartDatasets(mode, labels);
        reportChartInstance.update();
    }
}

function applyPreset(preset) {
    document.getElementById('inputPreset').value = preset;
    const startInput = document.getElementById('inputStartDate');
    const endInput = document.getElementById('inputEndDate');

    const now = new Date();
    const curYear = now.getFullYear();

    if (preset === 'all') {
        startInput.value = '';
        endInput.value = '';
    } else if (preset === 'this_year') {
        startInput.value = `${curYear}-01-01`;
        endInput.value = `${curYear}-12-31`;
    } else if (preset === 'this_month') {
        const curMonth = String(now.getMonth() + 1).padStart(2, '0');
        const lastDay = new Date(curYear, now.getMonth() + 1, 0).getDate();
        startInput.value = `${curYear}-${curMonth}-01`;
        endInput.value = `${curYear}-${curMonth}-${String(lastDay).padStart(2, '0')}`;
    } else if (preset === 'last_30_days') {
        const past = new Date();
        past.setDate(past.getDate() - 30);
        startInput.value = past.toISOString().split('T')[0];
        endInput.value = now.toISOString().split('T')[0];
    }

    document.getElementById('reportFilterForm').submit();
}

function onDateChanged() {
    document.getElementById('inputPreset').value = 'custom';
}

// Purchase Detail Modal Logic
let purchaseModal = null;
const purchaseModalEl = document.getElementById('purchaseDetailModal');
const purchaseModalBody = document.getElementById('purchaseDetailBody');
const purchaseModalFooterActions = document.getElementById('purchaseModalFooterActions');

function ensurePurchaseModal() {
    if (!purchaseModal && typeof bootstrap !== 'undefined' && purchaseModalEl) {
        purchaseModal = new bootstrap.Modal(purchaseModalEl);
    }
    return purchaseModal;
}

async function openPurchaseDetail(idOrCode) {
    const modal = ensurePurchaseModal();
    if (!modal) {
        alert('ระบบยังโหลดไม่สมบูรณ์ กรุณาลองใหม่อีกครั้ง');
        return;
    }
    
    if (purchaseModalFooterActions) {
        purchaseModalFooterActions.innerHTML = '';
    }
    
    purchaseModalBody.innerHTML = `
        <div class="text-center text-muted py-5">
            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
            <span>กำลังโหลดข้อมูลคำสั่งซื้อ...</span>
        </div>
    `;
    modal.show();

    try {
        const res = await fetch(`${BASE_URL}/index.php?url=purchase/detail/${encodeURIComponent(idOrCode)}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
        
        renderPurchaseModalContent(json.data);
    } catch (err) {
        purchaseModalBody.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center mb-0">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <div>${escapeHtml(err.message)}</div>
            </div>
        `;
    }
}

function renderPurchaseModalContent(d) {
    const p = d.purchase;
    const items = p.items || [];
    const images = d.images || [];
    const totalBookCount = items.reduce((s, it) => s + (parseInt(it.book_count) || 0), 0);
    const itemsTotal = items.reduce((s, it) => s + (parseFloat(it.item_price) || 0), 0);
    const shipping = parseFloat(p.total_shipping_cost) || 0;
    const grandTotal = itemsTotal + shipping;
    const shipPerBook = totalBookCount > 0 ? (shipping / totalBookCount) : 0;

    const subEl = document.getElementById('purchaseModalSub');
    if (subEl) {
        subEl.textContent = `รหัสบิล ${p.purchase_code} • วันที่ซื้อ ${p.purchase_date}`;
    }

    const headerHtml = `
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px; overflow: hidden;">
            <div class="card-body p-3 p-md-4 bg-white">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary fs-6 font-mono px-3 py-2 shadow-xs">
                                <i class="bi bi-receipt me-1"></i>${escapeHtml(p.purchase_code)}
                            </span>
                            <span class="badge bg-light text-dark border">ID #${p.id}</span>
                        </div>
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-1">
                        <i class="bi bi-calendar3 text-primary"></i>
                        <span>วันที่ซื้อ: <strong>${escapeHtml(p.purchase_date)}</strong></span>
                    </div>
                </div>

                ${p.note ? `
                    <div class="p-2 px-3 mb-3 rounded bg-warning-subtle text-dark border border-warning-subtle small d-flex align-items-start gap-2">
                        <i class="bi bi-chat-left-text-fill text-warning mt-1"></i>
                        <div><strong>หมายเหตุ:</strong> ${escapeHtml(p.note)}</div>
                    </div>
                ` : ''}

                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-light border">
                            <div class="text-muted small">จำนวนเล่มรวม</div>
                            <div class="fs-5 fw-bold text-dark">${totalBookCount} <small class="fw-normal text-muted" style="font-size:0.75rem;">เล่ม</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-light border">
                            <div class="text-muted small">ยอดซื้อ (ไม่รวมส่ง)</div>
                            <div class="fs-5 fw-bold text-dark font-mono">${itemsTotal.toFixed(2)} <small class="fw-normal text-muted" style="font-size:0.75rem;">฿</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-light border">
                            <div class="text-muted small">ค่าส่งทั้งบิล</div>
                            <div class="fs-5 fw-bold text-muted font-mono">${shipping.toFixed(2)} <small class="fw-normal text-muted" style="font-size:0.75rem;">฿</small></div>
                            <div class="text-muted" style="font-size:0.7rem;">เฉลี่ย ${shipPerBook.toFixed(2)} ฿/เล่ม</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-primary-subtle border border-primary-subtle">
                            <div class="text-primary small fw-semibold">รวมทั้งบิล (สุทธิ)</div>
                            <div class="fs-5 fw-bold text-primary font-mono">${grandTotal.toFixed(2)} <small class="fw-normal" style="font-size:0.75rem;">฿</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    let itemsHtml = `
        <div class="mb-3">
            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-collection text-primary"></i> รายการหนังสือในบิล (${items.length} เรื่อง)
            </h6>
    `;

    if (items.length === 0) {
        itemsHtml += `<div class="alert alert-warning mb-0">ไม่มีรายการหนังสือในบิลนี้</div>`;
    } else {
        itemsHtml += items.map((it, idx) => {
            const volumes = it.volumes || [];
            const volChips = volumes.map(v => {
                const qty = parseInt(v.quantity) || 0;
                const start = parseInt(v.volume_start);
                const end = parseInt(v.volume_end);
                const label = start === end ? `เล่ม ${start}` : `เล่ม ${start}-${end}`;
                return `<span class="badge bg-light text-dark border me-1 my-1"><i class="bi bi-journal-bookmark me-1 text-primary"></i>${label} <span class="badge bg-primary-subtle text-primary rounded-pill ms-1">${qty} เล่ม</span></span>`;
            }).join('');

            const itemPrice = parseFloat(it.item_price) || 0;
            const itemQty = parseInt(it.book_count) || 0;
            const baseUnitCost = itemQty > 0 ? (itemPrice / itemQty) : 0;
            const netUnitCost = baseUnitCost + shipPerBook;

            return `
                <div class="card border-0 shadow-sm mb-2" style="border-radius: 10px;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <span class="badge bg-secondary-subtle text-secondary me-1">เรื่องที่ ${idx + 1}</span>
                                <strong class="fs-6 text-dark">${escapeHtml(it.title)}</strong>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">
                                รวม ${itemQty} เล่ม
                            </span>
                        </div>

                        <div class="mb-2">
                            ${volChips || '<span class="text-muted small">ไม่มีข้อมูลเล่ม</span>'}
                        </div>

                        <div class="p-2 rounded bg-light border small">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <span class="text-muted">ราคารวมเรื่องนี้:</span>
                                    <div class="fw-bold text-dark font-mono">${itemPrice.toFixed(2)} ฿</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted">ต้นทุน/เล่ม (ไม่รวมส่ง):</span>
                                    <div class="fw-semibold text-dark font-mono">${baseUnitCost.toFixed(2)} ฿</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted">ค่าส่งเฉลี่ย/เล่ม:</span>
                                    <div class="fw-semibold text-muted font-mono">+${shipPerBook.toFixed(2)} ฿</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted">ต้นทุนสุทธิ/เล่ม:</span>
                                    <div class="fw-bold text-primary font-mono">${netUnitCost.toFixed(2)} ฿</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    itemsHtml += `</div>`;

    let imagesHtml = '';
    if (images.length > 0) {
        const imgs = images.map(img => `
            <a href="${BASE_URL}/${escapeHtml(img.image_path)}" target="_blank" rel="noopener" class="d-inline-block position-relative slip-thumbnail" title="คลิกเพื่อดูภาพขนาดเต็ม">
                <img src="${BASE_URL}/${escapeHtml(img.image_path)}" 
                     alt="slip"
                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                <span class="position-absolute bottom-0 start-0 w-100 text-center bg-dark bg-opacity-50 text-white" style="font-size: 0.65rem; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                    <i class="bi bi-zoom-in"></i> ดูภาพ
                </span>
            </a>
        `).join('');

        imagesHtml = `
            <div class="card border-0 shadow-sm" style="border-radius: 10px;">
                <div class="card-body p-3">
                    <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                        <i class="bi bi-images text-primary"></i> รูปภาพแนบ / สลิป (${images.length} รูป)
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${imgs}
                    </div>
                </div>
            </div>
        `;
    }

    purchaseModalBody.innerHTML = headerHtml + itemsHtml + imagesHtml;

    if (purchaseModalFooterActions) {
        purchaseModalFooterActions.innerHTML = `
            <a href="${BASE_URL}/index.php?url=purchase/create&edit=${p.id}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-pencil-square"></i>
                <span>ไปที่หน้าแก้ไขบิลนี้</span>
            </a>
        `;
    }
}

// Global click event delegation for view-purchase-btn
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-purchase-btn');
    if (!btn) return;
    e.preventDefault();
    const id = btn.dataset.id || btn.dataset.code;
    if (id) {
        openPurchaseDetail(id);
    }
});

// Check URL params for direct bill opening
const urlParams = new URLSearchParams(window.location.search);
const billToOpen = urlParams.get('view_bill');
if (billToOpen) {
    openPurchaseDetail(billToOpen);
}

// Initialise when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initReportChart();
});
</script>
