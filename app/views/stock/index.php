<div class="page-title">
    <i class="bi bi-boxes"></i>
    <h3>สต็อกมังงะคงเหลือ</h3>
</div>

<div class="card p-4 mb-4">
    <div class="panel-title"><i class="bi bi-journal-bookmark"></i> สรุปตามเรื่อง</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-flex-mobile">
            <thead><tr><th>เรื่อง</th><th>เล่มที่มี (available)</th><th>จำนวน</th><th>ต้นทุนรวม</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stockSummary as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['title']) ?></td>
                    <td><?= $s['min_volume'] ?> - <?= $s['max_volume'] ?></td>
                    <td><?= $s['available_count'] ?></td>
                    <td><?= number_format($s['total_cost'], 2) ?> ฿</td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/index.php?url=stock&title=<?= urlencode($s['title']) ?>">
                            ดูรายเล่ม
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($stockSummary)): ?>
                <tr><td colspan="5" class="text-center text-muted">ยังไม่มีสต็อก</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($selectedTitle !== ''): ?>
<div class="card p-4 mb-4">
    <div class="panel-title"><i class="bi bi-list-columns"></i> รายเล่มของ "<?= htmlspecialchars($selectedTitle) ?>"</div>
    <div class="table-responsive">
        <table class="table table-sm table-flex-mobile">
            <thead><tr><th>เล่มที่</th><th>ต้นทุน/เล่ม</th><th>วันที่ซื้อ</th><th>สถานะ</th></tr></thead>
            <tbody>
            <?php foreach ($books as $b): ?>
                <tr>
                    <td><?= $b['volume'] ?></td>
                    <td><?= number_format($b['unit_cost'], 2) ?> ฿</td>
                    <td><?= htmlspecialchars($b['purchase_date']) ?></td>
                    <td><span class="badge badge-status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($books)): ?>
                <tr><td colspan="4" class="text-center text-muted">ไม่พบข้อมูล</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4">
    <div class="panel-title"><i class="bi bi-image"></i> ที่มาของสต็อก — บิลซื้อที่เกี่ยวข้องกับ "<?= htmlspecialchars($selectedTitle) ?>"</div>
    <?php if (empty($purchases)): ?>
        <div class="text-muted">ไม่พบข้อมูลบิลซื้อ</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($purchases as $p): ?>
                <div class="col-md-6">
                    <div class="receipt-card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">
                                    <i class="bi bi-receipt"></i>
                                    <?= htmlspecialchars($p['purchase_code']) ?>
                                </div>
                                <div class="small text-muted">
                                    วันที่ซื้อ: <?= htmlspecialchars($p['purchase_date']) ?>
                                    | เล่มของเรื่องนี้: <strong><?= (int)$p['book_count_for_title'] ?></strong> เล่ม
                                    | ค่าส่ง: <?= number_format($p['total_shipping_cost'], 2) ?> ฿
                                </div>
                                <?php if (!empty($p['note'])): ?>
                                    <div class="small text-muted">หมายเหตุ: <?= htmlspecialchars($p['note']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($p['images'])): ?>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php foreach ($p['images'] as $img): ?>
                                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($img['image_path']) ?>" target="_blank" title="คลิกเพื่อดูภาพเต็ม">
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($img['image_path']) ?>"
                                             style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted fst-italic mt-2">ไม่มีรูปแนบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>