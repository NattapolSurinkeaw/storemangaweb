<div class="page-title">
    <i class="bi bi-cash-coin"></i>
    <h3>
        <?php if (!empty($editing)): ?>
            แก้ไขการขาย
            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-pencil-square"></i> แก้ไข #<?= (int) $editing['id'] ?></span>
        <?php else: ?>
            บันทึกการขาย
            <?php if (!$__isAdmin): ?><span class="badge bg-secondary ms-2">ดูอย่างเดียว</span><?php endif; ?>
        <?php endif; ?>
    </h3>
</div>

<?php if (!empty($editing)): ?>
<div class="alert alert-warning d-flex align-items-start mb-3">
    <i class="bi bi-info-circle me-2 fs-5 mt-1"></i>
    <div class="small">
        <strong>โหมดแก้ไข:</strong> แก้ไขได้เฉพาะ <strong>ราคาขาย/ค่าธรรมเนียม/ช่องทาง/วันที่</strong>
        หากต้องการเปลี่ยนสินค้า ให้ลบรายการนี้แล้วสร้างใหม่
        <div class="mt-1"><strong class="text-dark">สินค้า:</strong> <?= htmlspecialchars($editing['title_snapshot']) ?> | ต้นทุน: <strong><?= number_format($editing['cost_amount'], 2) ?></strong> ฿</div>
    </div>
</div>
<?php endif; ?>

<?php if ($__isAdmin): ?>
<div class="card p-4 mb-4">
    <form method="POST"
          action="<?= !empty($editing) ? BASE_URL . '/index.php?url=sales/update/' . (int) $editing['id'] : BASE_URL . '/index.php?url=sales/create' ?>"
          id="saleForm">
        <?php if (!empty($editing)): ?>
            <input type="hidden" name="ref_type" value="<?= htmlspecialchars($editing['ref_type']) ?>">
            <input type="hidden" name="ref_id" value="<?= (int) $editing['ref_id'] ?>">
            <input type="hidden" id="editCost" value="<?= htmlspecialchars($editing['cost_amount']) ?>">
        <?php endif; ?>

        <?php if (empty($editing)): ?>
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ประเภทสินค้า</label>
                <select id="refType" name="ref_type" class="form-select" required>
                    <option value="book">เล่มเดี่ยว / เศษเล่ม</option>
                    <option value="bundle">ชุด (Bundle)</option>
                    <option value="bundle_group">กลุ่มขายรวม (Bundle Group)</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">เลือกสินค้า</label>
                <select id="refId" name="ref_id" class="form-select" required></select>
            </div>
            <div class="col-md-4">
                <label class="form-label">ต้นทุนรวมของสินค้าที่เลือก</label>
                <input type="text" id="costDisplay" class="form-control" value="0.00" readonly>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <label class="form-label">สินค้าที่ขาย</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($editing['title_snapshot']) ?>" readonly>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ช่องทางขาย</label>
                <select id="channel" name="channel" class="form-select">
                    <option value="shopee" data-fee="<?= $shopeeFee ?>" <?= (!empty($editing) && $editing['channel'] === 'shopee') ? 'selected' : '' ?>>Shopee (Fee <?= $shopeeFee ?>%)</option>
                    <option value="facebook" data-fee="<?= $facebookFee ?>" <?= (!empty($editing) && $editing['channel'] === 'facebook') ? 'selected' : '' ?>>Facebook</option>
                </select>
            </div>
            <div class="col-md-2" id="feePercentBox">
                <label class="form-label">Fee %</label>
                <input type="number" step="0.01" id="feePercent" name="fee_percent" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['fee_percent']) : $shopeeFee ?>">
            </div>
            <div class="col-md-2" id="shopeeFeeAmountBox">
                <label class="form-label">ค่าธรรมเนียมจริง (บาท)</label>
                <input type="number" step="0.01" id="shopeeFeeAmount" class="form-control" placeholder="ระบุค่าธรรมเนียม"
                       value="<?= !empty($editing) && $editing['channel'] === 'shopee' ? htmlspecialchars($editing['fee_amount']) : '' ?>">
                <div class="form-text small">ระบุยอดที่ Shopee หักจริง ระบบจะคำนวณ % ให้อัตโนมัติ</div>
            </div>
            <div class="col-md-2" id="shippingFeeBox" style="display:none;">
                <label class="form-label">ค่าส่ง (บาท)</label>
                <input type="number" step="0.01" id="shippingFee" name="fee_amount" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['fee_amount']) : '0' ?>">
            </div>
            <?php if (empty($editing)): ?>
            <div class="col-md-3">
                <label class="form-label">โหมดคำนวณ</label>
                <select id="calcMode" class="form-select">
                    <option value="by_target_price">ระบุราคาขาย -&gt; หากำไร</option>
                    <option value="by_target_profit">ระบุ % กำไรที่ต้องการ -&gt; หาราคาขาย</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" id="calcValueLabel">ราคาขาย (บาท)</label>
                <input type="number" step="0.01" id="calcValue" class="form-control" value="0">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="calcBtn" class="btn btn-outline-primary w-100"><i class="bi bi-calculator"></i> คำนวณ</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($editing)): ?>
        <div class="row g-3 mb-3" id="resultBox" style="display:none;">
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ราคาขาย</div><div class="fw-bold font-mono" id="resSalePrice">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ค่าธรรมเนียม</div><div class="fw-bold font-mono text-danger" id="resFee">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">รายรับสุทธิ</div><div class="fw-bold font-mono" id="resNet">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">กำไรสุทธิ</div><div class="fw-bold font-mono text-success" id="resProfit">-</div></div></div>
        </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ราคาขายที่จะบันทึก (บาท)</label>
                <input type="number" step="0.01" name="sale_price" id="finalSalePrice" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['sale_price']) : '' ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">วันที่ขาย</label>
                <input type="date" name="sale_date" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['sale_date']) : date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="submit-bar">
            <?php if (!empty($editing)): ?>
                <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle"></i> บันทึกการแก้ไข</button>
                <a href="<?= BASE_URL ?>/index.php?url=sales/create" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> ยกเลิก</a>
                <p class="submit-hint text-muted small mb-0"><i class="bi bi-info-circle"></i> กำลังแก้ไขรายการขาย <strong>#<?= (int) $editing['id'] ?></strong></p>
            <?php else: ?>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกการขาย</button>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php else: ?>
<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-info-circle me-2 fs-4"></i>
    <div>บัญชีของคุณอยู่ในสิทธิ์ <strong>ผู้ใช้ทั่วไป</strong> สามารถดูประวัติการขายได้อย่างเดียว ไม่สามารถบันทึกการขายใหม่ได้</div>
</div>
<?php endif; ?>

<div class="card p-4">
    <div class="panel-title"><i class="bi bi-receipt"></i> รายการขายล่าสุด</div>
    <div class="table-responsive">
        <table class="table table-sm table-flex-mobile">
            <thead><tr><th>วันที่</th><th>รายการ</th><th>ช่องทาง</th><th>ราคาขาย</th><th>ค่าธรรมเนียม</th><th>ต้นทุน</th><th>กำไร</th><?php if ($__isAdmin): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td data-label="วันที่"><?= htmlspecialchars($s['sale_date']) ?></td>
                    <td data-label="รายการ"><?= htmlspecialchars($s['title_snapshot']) ?></td>
                    <td data-label="ช่องทาง"><span class="badge bg-<?= $s['channel'] === 'shopee' ? 'warning text-dark' : 'primary' ?>"><?= strtoupper($s['channel']) ?></span></td>
                    <td data-label="ราคาขาย" class="font-mono"><?= number_format($s['sale_price'], 2) ?></td>
                    <td data-label="ค่าธรรมเนียม" class="font-mono text-danger"><?= number_format($s['fee_amount'], 2) ?></td>
                    <td data-label="ต้นทุน" class="font-mono"><?= number_format($s['cost_amount'], 2) ?></td>
                    <td data-label="กำไร" class="font-mono <?= $s['profit_amount'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($s['profit_amount'], 2) ?></td>
                    <?php if ($__isAdmin): ?>
                    <td data-label="" class="row-actions">
                        <a class="btn btn-sm btn-outline-warning"
                           href="<?= BASE_URL ?>/index.php?url=sales/create&edit=<?= (int)$s['id'] ?>">
                           <i class="bi bi-pencil-square"></i> แก้ไข
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-sale-btn"
                                data-id="<?= (int)$s['id'] ?>"
                                data-title="<?= htmlspecialchars($s['title_snapshot']) ?>">
                            <i class="bi bi-trash"></i> ลบ
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="<?= $__isAdmin ? 8 : 7 ?>" class="text-center text-muted">ยังไม่มีการขาย</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const availableBooks = <?= json_encode($availableBooks, JSON_UNESCAPED_UNICODE) ?>;
const availableBundles = <?= json_encode($availableBundles, JSON_UNESCAPED_UNICODE) ?>;
const availableBundleGroups = <?= json_encode($availableBundleGroups, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL = "<?= BASE_URL ?>";

const refType = document.getElementById('refType');
const refId = document.getElementById('refId');
const costDisplay = document.getElementById('costDisplay');
const channel = document.getElementById('channel');
const feePercent = document.getElementById('feePercent');
const shippingFee = document.getElementById('shippingFee');
const shopeeFeeAmount = document.getElementById('shopeeFeeAmount');
const feePercentBox = document.getElementById('feePercentBox');
const shippingFeeBox = document.getElementById('shippingFeeBox');
const shopeeFeeAmountBox = document.getElementById('shopeeFeeAmountBox');
const calcMode = document.getElementById('calcMode');
const calcValueLabel = document.getElementById('calcValueLabel');
const finalSalePrice = document.getElementById('finalSalePrice');

let lastFeeEdited = 'percent'; // จำไว้ว่า user แก้ช่องไหนล่าสุด เพื่อ sync อีกช่อง

function applyChannelUI() {
    const isFb = channel.value === 'facebook';
    feePercentBox.style.display      = isFb ? 'none' : '';
    shippingFeeBox.style.display     = isFb ? ''     : 'none';
    shopeeFeeAmountBox.style.display = isFb ? 'none' : '';
    feePercent.readOnly = isFb;
    if (!isFb) recalcShopeeFeeAmount();
    recalcFbPercent();
}

function recalcFbPercent() {
    if (channel.value !== 'facebook') return;
    const price = parseFloat(finalSalePrice.value) || 0;
    const fee = parseFloat(shippingFee.value) || 0;
    if (price > 0) {
        feePercent.value = ((fee / price) * 100).toFixed(2);
    } else {
        feePercent.value = '0.00';
    }
}

// Shopee: sync fee_amount ↔ fee_percent สองทาง
function recalcShopeeFeeAmount() {
    if (channel.value !== 'shopee') return;
    const price = parseFloat(finalSalePrice.value) || 0;
    if (lastFeeEdited === 'amount') return; // ให้ sync จาก amount เป็นหลัก
    const pct = parseFloat(feePercent.value) || 0;
    const amt = price > 0 ? (price * pct / 100) : 0;
    shopeeFeeAmount.value = amt > 0 ? amt.toFixed(2) : '';
}

function recalcShopeePercentFromAmount() {
    if (channel.value !== 'shopee') return;
    const price = parseFloat(finalSalePrice.value) || 0;
    const amt   = parseFloat(shopeeFeeAmount.value) || 0;
    if (price > 0) {
        feePercent.value = ((amt / price) * 100).toFixed(2);
    } else {
        feePercent.value = '0.00';
    }
}

shippingFee.addEventListener('input', recalcFbPercent);
finalSalePrice.addEventListener('input', function () {
    recalcFbPercent();
    recalcShopeeFeeAmount();
});
feePercent.addEventListener('input', function () {
    lastFeeEdited = 'percent';
    recalcShopeeFeeAmount();
});
shopeeFeeAmount.addEventListener('input', function () {
    lastFeeEdited = 'amount';
    recalcShopeePercentFromAmount();
});

function populateOptions() {
    refId.innerHTML = '';
    let list;
    if (refType.value === 'book') list = availableBooks;
    else if (refType.value === 'bundle') list = availableBundles;
    else list = availableBundleGroups;

    list.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        if (refType.value === 'book') {
            opt.textContent = `${item.title} เล่ม ${item.volume} (ต้นทุน ${parseFloat(item.unit_cost).toFixed(2)} ฿)`;
            opt.dataset.cost = item.unit_cost;
        } else if (refType.value === 'bundle') {
            opt.textContent = `${item.title} ชุดเล่ม ${item.volume_start}-${item.volume_end} (ต้นทุน ${parseFloat(item.total_cost).toFixed(2)} ฿)`;
            opt.dataset.cost = item.total_cost;
        } else {
            const label = item.label || `กลุ่มขายรวม #${item.id}`;
            opt.textContent = `[กลุ่ม] ${label} (${item.bundle_count} เรื่อง, ${item.book_count} เล่ม, ต้นทุน ${parseFloat(item.total_cost).toFixed(2)} ฿)`;
            opt.dataset.cost = item.total_cost;
        }
        refId.appendChild(opt);
    });
    updateCost();
}

function updateCost() {
    const opt = refId.options[refId.selectedIndex];
    costDisplay.value = opt ? parseFloat(opt.dataset.cost).toFixed(2) : '0.00';
}

refType.addEventListener('change', populateOptions);
refId.addEventListener('change', updateCost);

channel.addEventListener('change', function () {
    const opt = channel.options[channel.selectedIndex];
    feePercent.value = opt.dataset.fee;
    lastFeeEdited = 'percent';
    applyChannelUI();
    if (channel.value === 'shopee') recalcShopeeFeeAmount();
});

calcMode.addEventListener('change', function () {
    calcValueLabel.textContent = calcMode.value === 'by_target_price' ? 'ราคาขาย (บาท)' : '% กำไรที่ต้องการ';
});

document.getElementById('calcBtn').addEventListener('click', function () {
    const params = new URLSearchParams({
        ref_type: refType.value,
        ref_id: refId.value,
        channel: channel.value,
        fee_percent: feePercent.value,
        fee_amount: channel.value === 'shopee' ? shopeeFeeAmount.value : shippingFee.value,
        mode: calcMode.value,
        value: document.getElementById('calcValue').value,
    });

    fetch(`${BASE_URL}/index.php?url=sales/calculate&` + params.toString())
        .then(r => r.json())
        .then(res => {
            if (!res.success) { alert(res.message); return; }
            const d = res.data;
            document.getElementById('resultBox').style.display = 'flex';
            document.getElementById('resSalePrice').textContent = d.sale_price.toFixed(2) + ' ฿';
            document.getElementById('resFee').textContent = d.fee_amount.toFixed(2) + ' ฿';
            document.getElementById('resNet').textContent = d.net_revenue.toFixed(2) + ' ฿';
            document.getElementById('resProfit').textContent = d.profit_amount.toFixed(2) + ' ฿ (' + d.profit_percent + '%)';
            document.getElementById('finalSalePrice').value = d.sale_price.toFixed(2);
            if (channel.value === 'facebook') {
                shippingFee.value = d.fee_amount.toFixed(2);
                recalcFbPercent();
            } else if (channel.value === 'shopee') {
                // sync ทั้งสองช่องจากผลลัพธ์
                feePercent.value = d.fee_percent.toFixed(2);
                shopeeFeeAmount.value = d.fee_amount.toFixed(2);
                lastFeeEdited = 'percent';
            }
        });
});

// ตอน submit: สำหรับ Shopee ให้ใช้ค่าจาก shopeeFeeAmount (ไม่ใช่ shippingFee)
document.getElementById('saleForm').addEventListener('submit', function (e) {
    if (channel.value === 'shopee') {
        shippingFee.value = parseFloat(shopeeFeeAmount.value) || 0;
    }
});

populateOptions();
applyChannelUI();

<?php if (!empty($editing)): ?>
// Edit mode: ตั้งค่า channel/fb UI ตามข้อมูลเดิม
applyChannelUI();
// scroll to form
setTimeout(() => {
    const f = document.getElementById('saleForm');
    if (f) f.scrollIntoView({ behavior: 'smooth', block: 'start' });
}, 100);
<?php endif; ?>

/* =================== Delete sale =================== */
document.querySelectorAll('.delete-sale-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id    = btn.dataset.id;
        const title = btn.dataset.title || ('#' + id);

        const ok = confirm(
            `ยืนยันลบรายการขาย #${id}?\n\n` +
            `สินค้า: ${title}\n\n` +
            `⚠️ ระบบจะคืนสถานะสินค้ากลับเป็น "พร้อมขาย" เพื่อให้สามารถบันทึกการขายใหม่ได้`
        );
        if (!ok) return;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังลบ...';

        try {
            const res = await fetch(`${BASE_URL}/index.php?url=sales/delete/${id}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json().catch(() => ({ success: false, message: 'ตอบกลับไม่ถูกต้อง' }));
            if (!json.success) throw new Error(json.message || 'ลบไม่สำเร็จ');

            alert(json.message || 'ลบรายการขายเรียบร้อย');
            window.location.reload();
        } catch (e) {
            alert('❌ ' + e.message);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
});
</script>
