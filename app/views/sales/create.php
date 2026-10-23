<style>
.searchable-select-wrap {
    position: relative;
}
.searchable-select-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 320px;
    overflow-y: auto;
    margin-top: 2px;
    display: none;
}
.searchable-group-header {
    padding: 0.35rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8f9fa;
    color: #6c757d;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 1;
}
.searchable-group-header:first-child {
    border-top: none;
}
.searchable-select-item {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    font-size: 0.92rem;
    border-bottom: 1px solid #f1f3f5;
    transition: background-color 0.15s ease-in-out;
}
.searchable-select-item:last-child {
    border-bottom: none;
}
.searchable-select-item:hover,
.searchable-select-item.active {
    background-color: #e9ecef;
}
.searchable-select-empty {
    padding: 0.75rem;
    color: #6c757d;
    text-align: center;
    font-size: 0.9rem;
}
</style>

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
        <input type="hidden" id="refType" name="ref_type" value="book">
        <input type="hidden" id="refId" name="ref_id" value="">
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-8">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label fw-semibold mb-0">เลือกสินค้า</label>
                    <div class="btn-group btn-group-sm" role="group" id="categoryFilterGroup">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">ทั้งหมด</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="เศษเล่ม">เศษเล่ม</button>
                        <button type="button" class="btn btn-outline-info text-dark" data-filter="ชุด">ชุด</button>
                        <button type="button" class="btn btn-outline-warning text-dark" data-filter="รวมกลุ่ม">รวมกลุ่ม</button>
                    </div>
                </div>
                <div class="searchable-select-wrap">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearchInput" class="form-control" placeholder="พิมพ์ค้นหาชื่อเรื่อง, เล่ม หรือชุดขาย..." autocomplete="off" required>
                        <button class="btn btn-outline-secondary" type="button" id="clearProductSearchBtn" style="display: none;" title="ล้างการค้นหา"><i class="bi bi-x-lg"></i></button>
                        <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" type="button" id="toggleProductMenuBtn" tabindex="-1" title="แสดงรายการทั้งหมด"></button>
                    </div>
                    <div class="searchable-select-menu shadow" id="productMenu"></div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
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
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">โหมดคำนวณ</label>
                <select id="calcMode" class="form-select">
                    <option value="by_target_price">ระบุราคาขาย -&gt; หากำไร</option>
                    <option value="by_target_profit">ระบุ % กำไรที่ต้องการ -&gt; หาราคาขาย</option>
                </select>
            </div>
            <div class="col-8 col-sm-6 col-md-2">
                <label class="form-label" id="calcValueLabel">ราคาขาย (บาท)</label>
                <input type="number" step="0.01" id="calcValue" class="form-control" placeholder="0.00" value="">
            </div>
            <div class="col-4 col-sm-12 col-md-2 d-flex align-items-end">
                <button type="button" id="calcBtn" class="btn btn-outline-primary w-100"><i class="bi bi-calculator"></i> คำนวณ</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($editing)): ?>
        <div class="row g-2 g-md-3 mb-3" id="resultBox" style="display:none;">
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ราคาขาย</div><div class="fw-bold font-mono" id="resSalePrice">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">ค่าธรรมเนียม</div><div class="fw-bold font-mono text-danger" id="resFee">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">รายรับสุทธิ</div><div class="fw-bold font-mono" id="resNet">-</div></div></div>
            <div class="col-6 col-md-3"><div class="card p-2 text-center"><div class="small text-muted">กำไรสุทธิ</div><div class="fw-bold font-mono text-success" id="resProfit">-</div></div></div>
        </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">ราคาขายที่จะบันทึก (บาท)</label>
                <input type="number" step="0.01" name="sale_price" id="finalSalePrice" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['sale_price']) : '' ?>" required>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
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
        <table class="table table-sm table-flex-mobile align-middle">
            <thead><tr><th>วันที่</th><th>รายการ</th><th>รหัสออเดอร์ซื้อเข้า</th><th>ช่องทาง</th><th>ราคาขาย</th><th>ค่าธรรมเนียม</th><th>ต้นทุน</th><th>กำไร</th><?php if ($__isAdmin): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <?php
                $sBills = [];
                if (!empty($s['purchase_bills'])) {
                    foreach (explode('||', $s['purchase_bills']) as $billStr) {
                        $parts = explode(':', $billStr, 2);
                        if (count($parts) === 2) {
                            $sBills[] = ['id' => (int)$parts[0], 'code' => $parts[1]];
                        }
                    }
                }
                ?>
                <tr>
                    <td data-label="วันที่"><?= htmlspecialchars($s['sale_date']) ?></td>
                    <td data-label="รายการ"><?= htmlspecialchars($s['title_snapshot']) ?></td>
                    <td data-label="รหัสออเดอร์ซื้อเข้า">
                        <?php if (!empty($sBills)): ?>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <?php foreach ($sBills as $bill): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 view-purchase-btn font-mono"
                                            data-id="<?= $bill['id'] ?>"
                                            title="คลิกดูคำสั่งซื้อ <?= htmlspecialchars($bill['code']) ?>"
                                            style="font-size:0.75rem;">
                                        <i class="bi bi-receipt me-1"></i><?= htmlspecialchars($bill['code']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
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
                <tr><td colspan="<?= $__isAdmin ? 9 : 8 ?>" class="text-center text-muted">ยังไม่มีการขาย</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: รายละเอียดคำสั่งซื้อเข้า -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-receipt text-primary"></i>
                    <span>รายละเอียดคำสั่งซื้อเข้า</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="purchaseDetailBody">
                <div class="text-center text-muted py-4">กำลังโหลด...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
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
}

function getCost() {
    const editCostEl = document.getElementById('editCost');
    if (editCostEl) return parseFloat(editCostEl.value) || 0;
    if (costDisplay) return parseFloat(costDisplay.value) || 0;
    return 0;
}

function autoCalculate(trigger) {
    const isFb = channel.value === 'facebook';
    const cost = getCost();

    // 1. ซิงค์ราคาขายและโหมดคำนวณ
    if (trigger === 'calcValue') {
        const valStr = calcValue ? calcValue.value.trim() : '';
        const val = parseFloat(valStr) || 0;
        if (calcMode && calcMode.value === 'by_target_profit') {
            // โหมด % กำไรที่ต้องการ -> คำนวณราคาขาย
            const targetNet = cost * (1 + (val / 100));
            let p = 0;
            if (isFb) {
                const sFee = parseFloat(shippingFee.value) || 0;
                p = targetNet + sFee;
            } else {
                const pct = parseFloat(feePercent.value) || 0;
                const ratio = pct / 100;
                p = ratio < 1 ? targetNet / (1 - ratio) : targetNet;
            }
            finalSalePrice.value = (valStr !== '' && p > 0) ? p.toFixed(2) : '';
        } else {
            // โหมดระบุราคาขาย -> ส่งต่อเข้าขายจริง
            finalSalePrice.value = valStr;
        }
    } else if (trigger === 'finalSalePrice') {
        const pStr = finalSalePrice.value.trim();
        const p = parseFloat(pStr) || 0;
        if (calcMode) {
            if (calcMode.value === 'by_target_price') {
                if (calcValue) calcValue.value = pStr;
            } else if (calcMode.value === 'by_target_profit') {
                let fee = 0;
                if (isFb) {
                    fee = parseFloat(shippingFee.value) || 0;
                } else {
                    if (lastFeeEdited === 'amount') {
                        fee = parseFloat(shopeeFeeAmount.value) || 0;
                    } else {
                        fee = p * ((parseFloat(feePercent.value) || 0) / 100);
                    }
                }
                const net = p - fee;
                const prof = net - cost;
                if (calcValue) {
                    calcValue.value = (cost > 0 && p > 0) ? ((prof / cost) * 100).toFixed(2) : '';
                }
            }
        }
    } else if (trigger === 'calcMode') {
        const p = parseFloat(finalSalePrice.value) || (calcValue ? parseFloat(calcValue.value) : 0) || 0;
        if (calcMode.value === 'by_target_price') {
            if (calcValueLabel) calcValueLabel.textContent = 'ราคาขาย (บาท)';
            if (calcValue) calcValue.value = p > 0 ? p : '';
        } else {
            if (calcValueLabel) calcValueLabel.textContent = '% กำไรที่ต้องการ';
            let fee = 0;
            if (isFb) {
                fee = parseFloat(shippingFee.value) || 0;
            } else {
                if (lastFeeEdited === 'amount') {
                    fee = parseFloat(shopeeFeeAmount.value) || 0;
                } else {
                    fee = p * ((parseFloat(feePercent.value) || 0) / 100);
                }
            }
            const net = p - fee;
            const prof = net - cost;
            if (calcValue) {
                calcValue.value = (cost > 0 && p > 0) ? ((prof / cost) * 100).toFixed(2) : '';
            }
        }
    } else if (trigger === 'feePercent') {
        lastFeeEdited = 'percent';
        if (calcMode && calcMode.value === 'by_target_profit') {
            const val = parseFloat(calcValue.value) || 0;
            const targetNet = cost * (1 + (val / 100));
            const pct = parseFloat(feePercent.value) || 0;
            const ratio = pct / 100;
            const p = ratio < 1 ? targetNet / (1 - ratio) : targetNet;
            finalSalePrice.value = p > 0 ? p.toFixed(2) : '';
        }
    } else if (trigger === 'shopeeFeeAmount') {
        lastFeeEdited = 'amount';
    } else if (trigger === 'shippingFee') {
        if (calcMode && calcMode.value === 'by_target_profit') {
            const val = parseFloat(calcValue.value) || 0;
            const targetNet = cost * (1 + (val / 100));
            const sFee = parseFloat(shippingFee.value) || 0;
            const p = targetNet + sFee;
            finalSalePrice.value = p > 0 ? p.toFixed(2) : '';
        }
    }

    // 2. ราคาขายปัจจุบันที่จะใช้คำนวณสรุป
    const curPrice = parseFloat(finalSalePrice.value) || (calcValue && calcMode && calcMode.value === 'by_target_price' ? parseFloat(calcValue.value) : 0) || 0;

    // 3. ซิงค์ค่าธรรมเนียมระหว่าง feePercent ↔ feeAmount อัตโนมัติ
    let feeAmt = 0;
    if (isFb) {
        feeAmt = parseFloat(shippingFee.value) || 0;
        if (curPrice > 0 && trigger !== 'feePercent') {
            feePercent.value = ((feeAmt / curPrice) * 100).toFixed(2);
        } else if (curPrice === 0) {
            feePercent.value = '0.00';
        }
    } else {
        // Shopee
        if (lastFeeEdited === 'amount') {
            feeAmt = parseFloat(shopeeFeeAmount.value) || 0;
            if (curPrice > 0 && trigger !== 'feePercent') {
                feePercent.value = ((feeAmt / curPrice) * 100).toFixed(2);
            }
        } else {
            const pct = parseFloat(feePercent.value) || 0;
            feeAmt = curPrice > 0 ? (curPrice * pct / 100) : 0;
            if (trigger !== 'shopeeFeeAmount') {
                shopeeFeeAmount.value = feeAmt > 0 ? feeAmt.toFixed(2) : '';
            }
        }
        // ซิงค์ค่าลง shippingFee (name="fee_amount") สำหรับส่ง POST
        shippingFee.value = feeAmt > 0 ? feeAmt.toFixed(2) : '0';
    }

    // 4. สรุปผลกำไร/รายรับ และแสดงใน resultBox อัตโนมัติ
    const netRevenue = curPrice - feeAmt;
    const profit = netRevenue - cost;
    const profitPercent = cost > 0 ? ((profit / cost) * 100) : 0;

    const resultBox = document.getElementById('resultBox');
    if (resultBox) {
        if (curPrice > 0 || cost > 0) {
            resultBox.style.display = 'flex';
            document.getElementById('resSalePrice').textContent = curPrice.toFixed(2) + ' ฿';
            document.getElementById('resFee').textContent = feeAmt.toFixed(2) + ' ฿';
            document.getElementById('resNet').textContent = netRevenue.toFixed(2) + ' ฿';
            const resProfit = document.getElementById('resProfit');
            resProfit.textContent = profit.toFixed(2) + ' ฿ (' + profitPercent.toFixed(2) + '%)';
            resProfit.className = 'fw-bold font-mono ' + (profit >= 0 ? 'text-success' : 'text-danger');
        } else {
            resultBox.style.display = 'none';
        }
    }
}

if (shippingFee) shippingFee.addEventListener('input', () => autoCalculate('shippingFee'));
if (finalSalePrice) finalSalePrice.addEventListener('input', () => autoCalculate('finalSalePrice'));
if (feePercent) feePercent.addEventListener('input', () => autoCalculate('feePercent'));
if (shopeeFeeAmount) shopeeFeeAmount.addEventListener('input', () => autoCalculate('shopeeFeeAmount'));
if (calcValue) calcValue.addEventListener('input', () => autoCalculate('calcValue'));
if (calcMode) calcMode.addEventListener('change', () => autoCalculate('calcMode'));

const productSearchInput = document.getElementById('productSearchInput');
const clearProductSearchBtn = document.getElementById('clearProductSearchBtn');
const productMenu = document.getElementById('productMenu');
const toggleProductMenuBtn = document.getElementById('toggleProductMenuBtn');

function updateClearSearchBtn() {
    if (!clearProductSearchBtn || !productSearchInput) return;
    clearProductSearchBtn.style.display = productSearchInput.value.trim() !== '' ? 'inline-block' : 'none';
}

// รวบรวมสินค้าทั้งหมด พร้อมจัดหมวดหมู่
const allProducts = [];

// 1. เล่มเดี่ยว / เศษเล่ม
availableBooks.forEach(b => {
    allProducts.push({
        type: 'book',
        category: 'เศษเล่ม',
        categoryBadge: 'bg-secondary',
        id: b.id,
        title: `${b.title} เล่ม ${b.volume}`,
        searchKey: `${b.title} เล่ม ${b.volume} เศษ เล่มเดี่ยว ${b.id}`.toLowerCase(),
        cost: parseFloat(b.unit_cost) || 0,
        desc: `เล่มเดี่ยว | ID: #${b.id}`
    });
});

// 2. ชุด (Bundle)
availableBundles.forEach(b => {
    allProducts.push({
        type: 'bundle',
        category: 'ชุด',
        categoryBadge: 'bg-info text-dark',
        id: b.id,
        title: `${b.title} ชุดเล่ม ${b.volume_start}-${b.volume_end}`,
        searchKey: `${b.title} ชุด เล่ม ${b.volume_start}-${b.volume_end} bundle ${b.id}`.toLowerCase(),
        cost: parseFloat(b.total_cost) || 0,
        desc: `ชุด ${b.book_count} เล่ม | Bundle #${b.id}`
    });
});

// 3. รวมกลุ่ม (Bundle Group)
availableBundleGroups.forEach(g => {
    let name = g.label ? g.label : `กลุ่มขายรวม #${g.id}`;
    if (g.titles_summary) {
        name += ` (${g.titles_summary.split('||').join(', ')})`;
    } else if (g.titles_only) {
        name += ` (${g.titles_only})`;
    }
    allProducts.push({
        type: 'bundle_group',
        category: 'รวมกลุ่ม',
        categoryBadge: 'bg-warning text-dark',
        id: g.id,
        title: name,
        searchKey: `${name} กลุ่ม รวมกลุ่ม bundle group ${g.id}`.toLowerCase(),
        cost: parseFloat(g.total_cost) || 0,
        desc: `รวม ${g.bundle_count} เรื่อง (${g.book_count} เล่ม) | Group #${g.id}`
    });
});

let currentCategoryFilter = 'all';

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderProductMenu(query = '') {
    if (!productMenu) return;
    const q = query.trim().toLowerCase();
    const filtered = allProducts.filter(p => {
        const matchCategory = (currentCategoryFilter === 'all' || p.category === currentCategoryFilter);
        const matchQuery = (!q || p.searchKey.includes(q));
        return matchCategory && matchQuery;
    });

    if (filtered.length === 0) {
        productMenu.innerHTML = '<div class="searchable-select-empty"><i class="bi bi-exclamation-circle me-1"></i>ไม่พบสินค้าที่ตรงกับการค้นหา</div>';
        productMenu.style.display = 'block';
        return;
    }

    // จัดกลุ่มตามหมวดหมู่: เศษเล่ม, ชุด, รวมกลุ่ม
    const groups = [
        { name: 'เศษเล่ม', badge: 'bg-secondary', items: [] },
        { name: 'ชุด', badge: 'bg-info text-dark', items: [] },
        { name: 'รวมกลุ่ม', badge: 'bg-warning text-dark', items: [] }
    ];

    filtered.forEach(item => {
        const g = groups.find(x => x.name === item.category);
        if (g) g.items.push(item);
    });

    let html = '';
    groups.forEach(g => {
        if (g.items.length === 0) return;
        html += `<div class="searchable-group-header d-flex justify-content-between align-items-center">
                    <span>${g.name}</span>
                    <span class="badge ${g.badge} py-0">${g.items.length} รายการ</span>
                 </div>`;
        g.items.forEach(item => {
            const isSelected = (refType.value === item.type && String(refId.value) === String(item.id));
            html += `
                <div class="searchable-select-item ${isSelected ? 'active' : ''}"
                     data-type="${item.type}"
                     data-id="${item.id}"
                     data-cost="${item.cost}"
                     data-title="${escapeHtml(item.title)}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-2 text-truncate">
                            <span class="badge ${item.categoryBadge} me-1" style="font-size:0.75rem;">${item.category}</span>
                            <strong>${escapeHtml(item.title)}</strong>
                        </div>
                        <div class="text-end text-nowrap">
                            <span class="text-primary fw-bold font-mono">${item.cost.toFixed(2)} ฿</span>
                        </div>
                    </div>
                    <div class="small text-muted ps-1 mt-1">${escapeHtml(item.desc)}</div>
                </div>
            `;
        });
    });

    productMenu.innerHTML = html;
    productMenu.style.display = 'block';

    productMenu.querySelectorAll('.searchable-select-item').forEach(el => {
        el.addEventListener('mousedown', (e) => {
            e.preventDefault();
            selectProduct(el.dataset.type, el.dataset.id, el.dataset.title, el.dataset.cost);
        });
    });
}

function selectProduct(type, id, title, cost) {
    refType.value = type;
    refId.value = id;
    if (productSearchInput) {
        productSearchInput.value = title;
    }
    costDisplay.value = parseFloat(cost || 0).toFixed(2);
    if (productMenu) productMenu.style.display = 'none';
    updateClearSearchBtn();
    autoCalculate('product');
}

if (productSearchInput) {
    productSearchInput.addEventListener('focus', () => {
        renderProductMenu(productSearchInput.value);
        updateClearSearchBtn();
    });

    productSearchInput.addEventListener('input', () => {
        renderProductMenu(productSearchInput.value);
        if (refId.value) {
            // หากกำลังพิมพ์แก้ไขข้อความที่เลือกไว้
            const cur = allProducts.find(p => p.type === refType.value && String(p.id) === String(refId.value));
            if (cur && cur.title !== productSearchInput.value) {
                refId.value = '';
                costDisplay.value = '0.00';
            }
        }
        updateClearSearchBtn();
    });

    productSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (productMenu) productMenu.style.display = 'none';
        }
    });
}

if (clearProductSearchBtn) {
    clearProductSearchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (productSearchInput) {
            productSearchInput.value = '';
            productSearchInput.focus();
        }
        if (refId) refId.value = '';
        if (costDisplay) costDisplay.value = '0.00';
        updateClearSearchBtn();
        renderProductMenu('');
        autoCalculate('product');
    });
}

// คลิกข้างนอกกล่องให้ปิดเมนู และคืนค่าข้อความสินค้าที่เลือกไว้
document.addEventListener('click', (e) => {
    const wrap = document.querySelector('.searchable-select-wrap');
    const categoryGroup = document.getElementById('categoryFilterGroup');
    if (!wrap) return;

    if (!wrap.contains(e.target) && (!categoryGroup || !categoryGroup.contains(e.target))) {
        if (productMenu) productMenu.style.display = 'none';
        if (productSearchInput) {
            if (!refId.value) {
                productSearchInput.value = '';
                costDisplay.value = '0.00';
            } else {
                const cur = allProducts.find(p => p.type === refType.value && String(p.id) === String(refId.value));
                if (cur) productSearchInput.value = cur.title;
            }
            updateClearSearchBtn();
        }
    }
});

if (toggleProductMenuBtn) {
    toggleProductMenuBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (productMenu && productMenu.style.display === 'block') {
            productMenu.style.display = 'none';
        } else {
            renderProductMenu(productSearchInput ? productSearchInput.value : '');
            if (productSearchInput) productSearchInput.focus();
        }
    });
}

// ปุ่มกรองหมวดหมู่ (ทั้งหมด, เศษเล่ม, ชุด, รวมกลุ่ม)
const categoryButtons = document.querySelectorAll('#categoryFilterGroup button[data-filter]');
categoryButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        categoryButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategoryFilter = btn.dataset.filter;
        renderProductMenu(productSearchInput ? productSearchInput.value : '');
        if (productSearchInput) productSearchInput.focus();
    });
});

// ตอนโหลดหน้า ให้ช่องค้นหาว่างไว้ (ไม่มีค่าเริ่มต้น)
refId.value = '';
refType.value = '';
if (productSearchInput) {
    productSearchInput.value = '';
}
costDisplay.value = '0.00';
updateClearSearchBtn();

if (channel) {
    channel.addEventListener('change', function () {
        const opt = channel.options[channel.selectedIndex];
        feePercent.value = opt.dataset.fee || '0';
        lastFeeEdited = 'percent';
        applyChannelUI();
        autoCalculate('channel');
    });
}

const calcBtn = document.getElementById('calcBtn');
if (calcBtn) {
    calcBtn.addEventListener('click', () => autoCalculate('calcValue'));
}

// ตอน submit: ตรวจสอบการเลือกสินค้า และสำหรับ Shopee ให้ใช้ค่าจาก shopeeFeeAmount
document.getElementById('saleForm').addEventListener('submit', function (e) {
    if (!refId.value) {
        e.preventDefault();
        alert('กรุณาเลือกสินค้าที่ต้องการขาย');
        if (productSearchInput) productSearchInput.focus();
        return;
    }
    if (channel.value === 'shopee') {
        shippingFee.value = parseFloat(shopeeFeeAmount.value) || 0;
    }
});

applyChannelUI();
autoCalculate('init');

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

/* =================== Purchase Detail Modal =================== */
let purchaseModal = null;
const purchaseModalEl = document.getElementById('purchaseDetailModal');
const purchaseModalBody = document.getElementById('purchaseDetailBody');

function ensurePurchaseModal() {
    if (!purchaseModal && typeof bootstrap !== 'undefined' && purchaseModalEl) {
        purchaseModal = new bootstrap.Modal(purchaseModalEl);
    }
    return purchaseModal;
}

function renderPurchaseHtml(d) {
    const p = d.purchase;
    const items = p.items || [];
    const images = d.images || [];
    const totalBookCount = items.reduce((s, it) => s + (parseInt(it.book_count) || 0), 0);
    const itemsTotal = items.reduce((s, it) => s + (parseFloat(it.item_price) || 0), 0);
    const shipPerBook = totalBookCount > 0 ? (parseFloat(p.total_shipping_cost) / totalBookCount) : 0;

    const headerHtml = `
        <div class="mb-3 border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                <div>
                    <span class="badge bg-secondary mb-1">บิล #${p.id}</span>
                    <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-receipt me-1"></i>${escapeHtml(p.purchase_code)}</h5>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>วันที่ซื้อ: <strong>${escapeHtml(p.purchase_date)}</strong>
                </div>
            </div>
            ${p.note ? `<div class="small text-muted mb-2"><i class="bi bi-card-text me-1"></i>หมายเหตุ: ${escapeHtml(p.note)}</div>` : ''}
            <div class="p-2 bg-light rounded d-flex flex-wrap gap-3 small">
                <div>จำนวนเล่มรวม: <strong>${totalBookCount}</strong> เล่ม</div>
                <div>ยอดซื้อ (ไม่รวมส่ง): <strong>${itemsTotal.toFixed(2)} ฿</strong></div>
                <div>ค่าส่ง: <strong>${parseFloat(p.total_shipping_cost).toFixed(2)} ฿</strong></div>
                <div>รวมทั้งบิล: <strong class="text-primary">${(itemsTotal + parseFloat(p.total_shipping_cost)).toFixed(2)} ฿</strong></div>
            </div>
        </div>
    `;

    let itemsHtml = '';
    if (items.length === 0) {
        itemsHtml = '<div class="alert alert-warning">ไม่มีรายการหนังสือในบิลนี้</div>';
    } else {
        itemsHtml = items.map((it, idx) => {
            const volumes = it.volumes || [];
            const volChips = volumes.map(v => {
                const qty = parseInt(v.quantity) || 0;
                const start = parseInt(v.volume_start);
                const end = parseInt(v.volume_end);
                const label = start === end ? `เล่ม ${start}` : `เล่ม ${start}-${end}`;
                return `<span class="badge bg-info text-dark me-1">${label} (${qty})</span>`;
            }).join('');
            const unitCost = (parseFloat(it.unit_cost) || 0) + shipPerBook;
            return `
                <div class="mb-3 border rounded p-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong><i class="bi bi-book text-primary me-1"></i> เรื่องที่ ${idx + 1}: ${escapeHtml(it.title)}</strong>
                        <span class="badge bg-light text-dark border">${parseInt(it.book_count) || 0} เล่ม</span>
                    </div>
                    <div class="small my-2">${volChips || '<span class="text-muted">ไม่มีช่วงเล่ม</span>'}</div>
                    <div class="row g-2 small pt-2 border-top">
                        <div class="col-6 col-md-3">ราคาเรื่องนี้: <strong>${parseFloat(it.item_price).toFixed(2)} ฿</strong></div>
                        <div class="col-6 col-md-3">ต้นทุน/เล่ม (ไม่รวมส่ง): <strong>${(parseFloat(it.unit_cost) || 0).toFixed(2)} ฿</strong></div>
                        <div class="col-6 col-md-3">ค่าส่ง/เล่ม: <strong>${shipPerBook.toFixed(2)} ฿</strong></div>
                        <div class="col-6 col-md-3">ต้นทุนรวม/เล่ม: <strong class="text-primary">${unitCost.toFixed(2)} ฿</strong></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    let imagesHtml = '';
    if (images.length > 0) {
        const imgs = images.map(img => `
            <a href="${BASE_URL}/${escapeHtml(img.image_path)}" target="_blank" title="คลิกดูภาพเต็ม">
                <img src="${BASE_URL}/${escapeHtml(img.image_path)}"
                     alt="slip"
                     style="width:110px;height:110px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">
            </a>
        `).join('');
        imagesHtml = `
            <div class="mt-3">
                <div class="fw-semibold mb-2"><i class="bi bi-image text-primary me-1"></i> รูปสลิปการซื้อ (${images.length})</div>
                <div class="d-flex flex-wrap gap-2">${imgs}</div>
            </div>
        `;
    }

    return headerHtml + itemsHtml + imagesHtml;
}

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.view-purchase-btn');
    if (!btn) return;
    const id = btn.dataset.id;
    if (!id) return;
    const modal = ensurePurchaseModal();
    if (!modal) { alert('Bootstrap ยังโหลดไม่เสร็จ กรุณาลองใหม่'); return; }
    purchaseModalBody.innerHTML = `
        <div class="text-center text-muted py-5">
            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
            กำลังโหลดข้อมูลคำสั่งซื้อ...
        </div>
    `;
    modal.show();
    try {
        const res = await fetch(`${BASE_URL}/index.php?url=purchase/detail/${id}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'โหลดข้อมูลไม่สำเร็จ');
        purchaseModalBody.innerHTML = renderPurchaseHtml(json.data);
    } catch (err) {
        purchaseModalBody.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> ${escapeHtml(err.message)}</div>`;
    }
});
</script>
