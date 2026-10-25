<div class="page-title d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-speedometer2"></i>
        <h3 class="mb-0">Dashboard สรุปภาพรวม</h3>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshDashboard" title="โหลดข้อมูลใหม่">
            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
        </button>
        <a href="<?= url('report') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-bar-chart-line me-1"></i> ดูรายงานซื้อ-ขายพร้อมกราฟ
        </a>
    </div>
</div>

<!-- สรุปตัวเลขสถิติ 4 ช่อง -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purchase">
            <div class="stat-label">ยอดซื้อเข้าทั้งหมด (รวมค่าส่ง)</div>
            <div class="stat-value" id="valTotalPurchase">
                <span class="skeleton-pulse px-3">000,000.00 ฿</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sales">
            <div class="stat-label">ยอดขายรวม</div>
            <div class="stat-value" id="valTotalSales">
                <span class="skeleton-pulse px-3">000,000.00 ฿</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-fee">
            <div class="stat-label">ค่าธรรมเนียม Shopee ที่เสีย</div>
            <div class="stat-value" id="valTotalFee">
                <span class="skeleton-pulse px-3">00,000.00 ฿</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-profit">
            <div class="stat-label">กำไรสุทธิรวม</div>
            <div class="stat-value" id="valTotalProfit">
                <span class="skeleton-pulse px-3">000,000.00 ฿</span>
            </div>
        </div>
    </div>
</div>

<!-- สถานะสต็อกและช่องทางขาย -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <div class="panel-title"><i class="bi bi-boxes"></i> สต็อกเล่มเดี่ยว</div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>ว่าง (available)</span>
                <span class="badge bg-success" id="badgeAvailableBooks"><span class="spinner-border spinner-border-sm" style="width:0.8rem;height:0.8rem;"></span></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>อยู่ในชุด (in_bundle)</span>
                <span class="badge bg-warning text-dark" id="badgeInBundleBooks"><span class="spinner-border spinner-border-sm" style="width:0.8rem;height:0.8rem;"></span></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span>ขายแล้ว (sold)</span>
                <span class="badge bg-secondary" id="badgeSoldBooks"><span class="spinner-border spinner-border-sm" style="width:0.8rem;height:0.8rem;"></span></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <div class="panel-title"><i class="bi bi-collection"></i> ชุด (Bundle)</div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>พร้อมขาย</span>
                <span class="badge bg-success" id="badgeAvailableBundles"><span class="spinner-border spinner-border-sm" style="width:0.8rem;height:0.8rem;"></span></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span>ขายแล้ว</span>
                <span class="badge bg-secondary" id="badgeSoldBundles"><span class="spinner-border spinner-border-sm" style="width:0.8rem;height:0.8rem;"></span></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <div class="panel-title"><i class="bi bi-graph-up"></i> แยกตามช่องทางขาย</div>
            <div id="channelTotalsContainer">
                <div class="text-center text-muted py-3">
                    <span class="spinner-border spinner-border-sm text-primary"></span> กำลังโหลด...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ตารางสต็อกคงเหลือ & รายการขายล่าสุด -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <div class="panel-title d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-bookmark"></i> สต็อกคงเหลือแยกตามเรื่อง</span>
                <a href="<?= url('stock') ?>" class="small text-decoration-none">ดูทั้งหมด &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-flex-mobile align-middle mb-0">
                    <thead><tr><th>เรื่อง</th><th>เล่มที่มี</th><th>จำนวน</th><th>ต้นทุนรวม</th></tr></thead>
                    <tbody id="stockSummaryTableBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span> กำลังโหลดสต็อกคงเหลือ...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <div class="panel-title d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt-cutoff"></i> การขายล่าสุด</span>
                <a href="<?= url('sales') ?>" class="small text-decoration-none">ดูทั้งหมด &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-flex-mobile align-middle mb-0">
                    <thead><tr><th>วันที่</th><th>รายการ</th><th>ช่องทาง</th><th>ราคาขาย</th><th>กำไร</th></tr></thead>
                    <tbody id="recentSalesTableBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span> กำลังโหลดรายการขายล่าสุด...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadDashboardData();

    $('#btnRefreshDashboard').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        loadDashboardData(function() {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> รีเฟรช');
        });
    });

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(str) {
        return escapeHtml(str);
    }

    function formatNumber(num, decimals = 2) {
        const n = parseFloat(num) || 0;
        return n.toLocaleString('th-TH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    function loadDashboardData(callback) {
        $.ajax({
            url: BASE_URL + '/dashboard/data',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    console.error('Failed to load dashboard:', res);
                    return;
                }

                // 1. Stat cards
                $('#valTotalPurchase').text(formatNumber(res.totalPurchase) + ' ฿');
                $('#valTotalSales').text(formatNumber(res.salesTotals.total_sales) + ' ฿');
                $('#valTotalFee').text(formatNumber(res.salesTotals.total_fee) + ' ฿');
                $('#valTotalProfit').text(formatNumber(res.salesTotals.total_profit) + ' ฿');

                // 2. Badges
                $('#badgeAvailableBooks').text(res.availableBooks);
                $('#badgeInBundleBooks').text(res.inBundleBooks);
                $('#badgeSoldBooks').text(res.soldBooks);
                $('#badgeAvailableBundles').text(res.availableBundles);
                $('#badgeSoldBundles').text(res.soldBundles);

                // 3. Channel breakdown
                const $channels = $('#channelTotalsContainer').empty();
                if (res.channelTotals && res.channelTotals.length > 0) {
                    res.channelTotals.forEach(function(c) {
                        const channelName = escapeHtml(String(c.channel || '').toUpperCase());
                        const txCount = parseInt(c.tx_count) || 0;
                        const profit = formatNumber(c.total_profit);
                        $channels.append(`
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>${channelName} (${txCount} รายการ)</span>
                                <span class="fw-semibold">กำไร ${profit} ฿</span>
                            </div>
                        `);
                    });
                } else {
                    $channels.html('<div class="text-muted small py-2">ยังไม่มีข้อมูลการขาย</div>');
                }

                // 4. Stock Summary Table
                const $stockBody = $('#stockSummaryTableBody').empty();
                if (res.stockSummary && res.stockSummary.length > 0) {
                    res.stockSummary.forEach(function(s) {
                        const count = s.total_count !== undefined ? s.total_count : s.available_count;
                        $stockBody.append(`
                            <tr>
                                <td data-label="เรื่อง" class="fw-medium">${escapeHtml(s.title)}</td>
                                <td data-label="เล่มที่มี">${escapeHtml(s.min_volume)}-${escapeHtml(s.max_volume)}</td>
                                <td data-label="จำนวน">${parseInt(count) || 0}</td>
                                <td data-label="ต้นทุนรวม">${formatNumber(s.total_cost)} ฿</td>
                            </tr>
                        `);
                    });
                } else {
                    $stockBody.html('<tr><td colspan="4" class="text-center text-muted py-3">ไม่มีสต็อกคงเหลือ</td></tr>');
                }

                // 5. Recent Sales Table
                const $salesBody = $('#recentSalesTableBody').empty();
                if (res.recentSales && res.recentSales.length > 0) {
                    res.recentSales.forEach(function(s) {
                        const profit = parseFloat(s.profit_amount) || 0;
                        const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
                        const channel = escapeHtml(String(s.channel || '').toUpperCase());
                        $salesBody.append(`
                            <tr>
                                <td data-label="วันที่">${escapeHtml(s.sale_date)}</td>
                                <td data-label="รายการ" class="fw-medium">${escapeHtml(s.title_snapshot)}</td>
                                <td data-label="ช่องทาง"><span class="badge bg-secondary">${channel}</span></td>
                                <td data-label="ราคาขาย" class="font-mono">${formatNumber(s.sale_price)}</td>
                                <td data-label="กำไร" class="font-mono ${profitClass}">${formatNumber(profit)}</td>
                            </tr>
                        `);
                    });
                } else {
                    $salesBody.html('<tr><td colspan="5" class="text-center text-muted py-3">ยังไม่มีการขาย</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#stockSummaryTableBody').html('<tr><td colspan="4" class="text-center text-danger py-3">โหลดข้อมูลไม่สำเร็จ</td></tr>');
                $('#recentSalesTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">โหลดข้อมูลไม่สำเร็จ</td></tr>');
            },
            complete: function() {
                if (typeof callback === 'function') callback();
            }
        });
    }
});
</script>
