<div class="page-title">
    <i class="bi bi-speedometer2"></i>
    <h3>Dashboard สรุปภาพรวม</h3>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purchase">
            <div class="stat-label">ยอดซื้อเข้าทั้งหมด (รวมค่าส่ง)</div>
            <div class="stat-value"><?= number_format($totalPurchase, 2) ?> ฿</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sales">
            <div class="stat-label">ยอดขายรวม</div>
            <div class="stat-value"><?= number_format($salesTotals['total_sales'], 2) ?> ฿</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-fee">
            <div class="stat-label">ค่าธรรมเนียม Shopee ที่เสีย</div>
            <div class="stat-value"><?= number_format($salesTotals['total_fee'], 2) ?> ฿</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-profit">
            <div class="stat-label">กำไรสุทธิรวม</div>
            <div class="stat-value"><?= number_format($salesTotals['total_profit'], 2) ?> ฿</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="panel-title"><i class="bi bi-boxes"></i> สต็อกเล่มเดี่ยว</div>
            <div class="d-flex justify-content-between align-items-center mb-1">ว่าง (available) <span class="badge bg-success"><?= $availableBooks ?></span></div>
            <div class="d-flex justify-content-between align-items-center mb-1">อยู่ในชุด (in_bundle) <span class="badge bg-warning text-dark"><?= $inBundleBooks ?></span></div>
            <div class="d-flex justify-content-between align-items-center">ขายแล้ว (sold) <span class="badge bg-secondary"><?= $soldBooks ?></span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="panel-title"><i class="bi bi-collection"></i> ชุด (Bundle)</div>
            <div class="d-flex justify-content-between align-items-center mb-1">พร้อมขาย <span class="badge bg-success"><?= $availableBundles ?></span></div>
            <div class="d-flex justify-content-between align-items-center">ขายแล้ว <span class="badge bg-secondary"><?= $soldBundles ?></span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="panel-title"><i class="bi bi-graph-up"></i> แยกตามช่องทางขาย</div>
            <?php foreach ($channelTotals as $c): ?>
                <div class="d-flex justify-content-between">
                    <span><?= strtoupper($c['channel']) ?> (<?= $c['tx_count'] ?> รายการ)</span>
                    <span>กำไร <?= number_format($c['total_profit'], 2) ?> ฿</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($channelTotals)): ?><div class="text-muted small">ยังไม่มีข้อมูลการขาย</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <div class="panel-title"><i class="bi bi-journal-bookmark"></i> สต็อกคงเหลือแยกตามเรื่อง</div>
            <div class="table-responsive">
                <table class="table table-sm table-flex-mobile">
                    <thead><tr><th>เรื่อง</th><th>เล่มที่มี</th><th>จำนวน</th><th>ต้นทุนรวม</th></tr></thead>
                    <tbody>
                    <?php foreach ($stockSummary as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['title']) ?></td>
                            <td><?= $s['min_volume'] ?>-<?= $s['max_volume'] ?></td>
                            <td><?= $s['available_count'] ?></td>
                            <td><?= number_format($s['total_cost'], 2) ?> ฿</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stockSummary)): ?>
                        <tr><td colspan="4" class="text-center text-muted">ไม่มีสต็อกคงเหลือ</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <div class="panel-title"><i class="bi bi-receipt-cutoff"></i> การขายล่าสุด</div>
            <div class="table-responsive">
                <table class="table table-sm table-flex-mobile">
                    <thead><tr><th>วันที่</th><th>รายการ</th><th>ช่องทาง</th><th>ราคาขาย</th><th>กำไร</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentSales as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['sale_date']) ?></td>
                            <td><?= htmlspecialchars($s['title_snapshot']) ?></td>
                            <td><?= strtoupper($s['channel']) ?></td>
                            <td><?= number_format($s['sale_price'], 2) ?></td>
                            <td class="<?= $s['profit_amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($s['profit_amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentSales)): ?>
                        <tr><td colspan="5" class="text-center text-muted">ยังไม่มีการขาย</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
