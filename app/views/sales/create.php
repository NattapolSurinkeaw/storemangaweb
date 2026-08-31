<div class="page-title">
    <i class="bi bi-cash-coin"></i>
    <h3>บันทึกการขาย</h3>
</div>

<div class="card p-4 mb-4">
    <form method="POST" action="<?= BASE_URL ?>/index.php?url=sales/create" id="saleForm">
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

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ช่องทางขาย</label>
                <select id="channel" name="channel" class="form-select">
                    <option value="shopee" data-fee="<?= $shopeeFee ?>">Shopee (Fee <?= $shopeeFee ?>%)</option>
                    <option value="facebook" data-fee="<?= $facebookFee ?>">Facebook</option>
                </select>
            </div>
            <div class="col-md-2" id="feePercentBox">
                <label class="form-label">Fee %</label>
                <input type="number" step="0.01" id="feePercent" name="fee_percent" class="form-control" value="<?= $shopeeFee ?>">
            </div>
            <div class="col-md-2" id="shippingFeeBox" style="display:none;">
                <label class="form-label">ค่าส่ง (บาท)</label>
                <input type="number" step="0.01" id="shippingFee" name="fee_amount" class="form-control" value="0">
            </div>
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
        </div>

        <div class="row g-3 mb-3" id="resultBox" style="display:none;">
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ราคาขาย</div><div class="fw-bold font-mono" id="resSalePrice">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ค่าธรรมเนียม</div><div class="fw-bold font-mono text-danger" id="resFee">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">รายรับสุทธิ</div><div class="fw-bold font-mono" id="resNet">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">กำไรสุทธิ</div><div class="fw-bold font-mono text-success" id="resProfit">-</div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ราคาขายที่จะบันทึก (บาท)</label>
                <input type="number" step="0.01" name="sale_price" id="finalSalePrice" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">วันที่ขาย</label>
                <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกการขาย</button>
    </form>
</div>

<div class="card p-4">
    <div class="panel-title"><i class="bi bi-receipt"></i> รายการขายล่าสุด</div>
    <div class="table-responsive">
        <table class="table table-sm table-flex-mobile">
            <thead><tr><th>วันที่</th><th>รายการ</th><th>ช่องทาง</th><th>ราคาขาย</th><th>ค่าธรรมเนียม</th><th>ต้นทุน</th><th>กำไร</th></tr></thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['sale_date']) ?></td>
                    <td><?= htmlspecialchars($s['title_snapshot']) ?></td>
                    <td><?= strtoupper($s['channel']) ?></td>
                    <td><?= number_format($s['sale_price'], 2) ?></td>
                    <td><?= number_format($s['fee_amount'], 2) ?></td>
                    <td><?= number_format($s['cost_amount'], 2) ?></td>
                    <td class="<?= $s['profit_amount'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($s['profit_amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7" class="text-center text-muted">ยังไม่มีการขาย</td></tr>
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
const feePercentBox = document.getElementById('feePercentBox');
const shippingFeeBox = document.getElementById('shippingFeeBox');
const calcMode = document.getElementById('calcMode');
const calcValueLabel = document.getElementById('calcValueLabel');
const finalSalePrice = document.getElementById('finalSalePrice');

function applyChannelUI() {
    const isFb = channel.value === 'facebook';
    feePercentBox.style.display = isFb ? 'none' : '';
    shippingFeeBox.style.display = isFb ? '' : 'none';
    feePercent.readOnly = isFb;
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

shippingFee.addEventListener('input', recalcFbPercent);
finalSalePrice.addEventListener('input', recalcFbPercent);

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
    applyChannelUI();
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
        fee_amount: shippingFee.value,
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
            }
        });
});

populateOptions();
applyChannelUI();
</script>
