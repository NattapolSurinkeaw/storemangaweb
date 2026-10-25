<div class="page-title">
    <i class="bi bi-bag-plus"></i>
    <h3>
        <?php if (!empty($editing)): ?>
            แก้ไขบิลซื้อเข้า
            <span class="badge bg-warning text-dark ms-2">
                <i class="bi bi-pencil-square"></i> แก้ไข #<?= (int) $editing['id'] ?> · <?= htmlspecialchars($editing['purchase_code']) ?>
            </span>
        <?php else: ?>
            บันทึกบิลซื้อเข้า
            <?php if (!$__isAdmin): ?><span class="badge bg-secondary ms-2">ดูอย่างเดียว</span><?php endif; ?>
        <?php endif; ?>
    </h3>
</div>

<?php if (!empty($editing)): ?>
    <?php $soldCount = 0; $bundleCount = 0; /* เช็คเพื่อแจ้งเตือน */ ?>
    <div class="alert alert-warning d-flex align-items-start mb-3">
        <i class="bi bi-exclamation-triangle me-2 fs-5 mt-1"></i>
        <div class="small">
            <strong>โหมดแก้ไข:</strong> ระบบจะลบรายการเดิมและสร้างใหม่ตามข้อมูลด้านล่าง
            <strong class="text-danger">ไม่สามารถแก้ไขได้หากมีหนังสือในบิลถูกขายไปแล้วหรืออยู่ในชุดขาย</strong>
            <br>
            <span class="text-muted">รูปสลิปเดิมจะยังคงอยู่ ไม่ถูกลบ</span>
        </div>
    </div>
<?php endif; ?>

<?php if ($__isAdmin): ?>
<div class="card p-4 mb-4">
    <form method="POST"
          action="<?= !empty($editing) ? url('purchase/update/' . (int) $editing['id']) : url('purchase/create') ?>"
          id="purchaseForm" enctype="multipart/form-data">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">วันที่ซื้อ</label>
                <input type="date" name="purchase_date" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['purchase_date']) : date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">ค่าส่งรวมทั้งบิล (บาท)</label>
                <input type="number" step="0.01" min="0" name="total_shipping_cost" id="total_shipping_cost" class="form-control"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['total_shipping_cost']) : '0' ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">หมายเหตุ</label>
                <input type="text" name="note" class="form-control" placeholder="เช่น ชื่อร้าน / เพจที่ซื้อ"
                       value="<?= !empty($editing) ? htmlspecialchars($editing['note'] ?? '') : '' ?>">
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">รายการมังงะในบิลนี้ <small class="text-muted">(1 เรื่อง มีได้หลายช่วงเล่ม เช่น 1-10, 15-20, 22)</small></div>
            <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> เพิ่มเรื่อง</button>
        </div>

        <?php if (!empty($editing)): ?>
        <div class="card mb-3 border-secondary">
            <div class="card-body py-2">
                <div class="fw-semibold mb-1"><i class="bi bi-images"></i> รูปสลิปเดิม</div>
                <div class="d-flex flex-wrap gap-2" id="existingImages">
                    <?php foreach (($editingImages ?? []) as $img): ?>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($img['image_path']) ?>" target="_blank">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($img['image_path']) ?>"
                                 alt="slip"
                                 style="width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($editingImages)): ?>
                        <span class="text-muted small">ไม่มีรูปสลิป</span>
                    <?php endif; ?>
                </div>
                <div class="form-text">หากต้องการเพิ่มรูปใหม่ ให้เลือกไฟล์ด้านล่าง (รูปเดิมจะไม่ถูกลบ)</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3 border-info">
            <div class="card-body py-2">
                <label class="form-label fw-semibold mb-1"><i class="bi bi-image"></i> แนบรูปภาพบิลซื้อเพิ่ม (ถ้ามี)</label>
                <input type="file" name="purchase_images[]" id="purchaseImages" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                <div class="form-text">รองรับ jpg/png/gif/webp ขนาดไม่เกิน 5MB ต่อไฟล์ (เลือกได้หลายไฟล์)</div>
                <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
            </div>
        </div>

        <div id="itemsContainer"></div>

        <div class="text-end mb-3 mt-3 font-mono">
            <span class="me-4">จำนวนเล่มรวมทั้งบิล: <strong id="totalQtyDisplay">0</strong></span>
            <span>ยอดซื้อรวม (ไม่รวมค่าส่ง): <strong id="totalPriceDisplay">0.00</strong> ฿</span>
        </div>

        <div class="submit-bar">
            <?php if (!empty($editing)): ?>
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-check-circle"></i> บันทึกการแก้ไข
                </button>
                <a href="<?= url('purchase/create') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> ยกเลิกการแก้ไข
                </a>
                <p class="submit-hint text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> กำลังแก้ไขบิล <strong>#<?= (int) $editing['id'] ?></strong> — การบันทึกจะลบรายการเดิมและสร้างใหม่
                </p>
            <?php else: ?>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> บันทึกบิลซื้อ
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
<script>
const PURCHASE_PREFILL = <?= json_encode(!empty($editing) ? $editing['items'] : [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php else: ?>
<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-info-circle me-2 fs-4"></i>
    <div>บัญชีของคุณอยู่ในสิทธิ์ <strong>ผู้ใช้ทั่วไป</strong> สามารถดูข้อมูลบิลซื้อได้อย่างเดียว ไม่สามารถเพิ่ม/ลบบิลซื้อได้</div>
</div>
<?php endif; ?>

<div class="card p-4">
    <div class="panel-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> ประวัติบิลซื้อเข้า</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshPurchases"><i class="bi bi-arrow-clockwise"></i> รีเฟรช</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-flex-mobile align-middle">
            <thead><tr><th>#</th><th>รหัสบิล</th><th>วันที่</th><th>note</th><th>จำนวนเล่ม</th><th>ยอดซื้อ (ไม่รวมส่ง)</th><th>ค่าส่ง</th><th>ยอดรวม</th><th>รูปสลิป</th><th></th></tr></thead>
            <tbody id="purchaseHistoryTbody">
                <tr><td colspan="10" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm text-primary me-2"></span> กำลังโหลดประวัติบิลซื้อ...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: รายละเอียดบิลซื้อ -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye"></i> รายละเอียดบิลซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="purchaseDetailBody">
                <div class="text-center text-muted py-4">กำลังโหลด...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<template id="itemTemplate">
    <div class="card mb-3 item-card border" data-item-index="__INDEX__">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="item-header">เรื่องที่ __NUM__</strong>
                <button type="button" class="btn btn-sm btn-outline-danger removeItemBtn"><i class="bi bi-trash"></i> ลบเรื่อง</button>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <input type="text" name="items[__INDEX__][title]" class="form-control item-title" placeholder="ชื่อเรื่อง เช่น ดันดาดัน" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0" name="items[__INDEX__][price]" class="form-control item-price" placeholder="ราคารวมของเรื่องนี้" value="0" required>
                </div>
                <div class="col-md-3">
                    <div class="form-control-plaintext small text-muted">
                        รวม <span class="item-qty">0</span> เล่ม | ต้นทุน/เล่ม <span class="item-unitcost">0.00</span> ฿
                    </div>
                </div>
            </div>

            <div class="ranges-container ps-3"></div>

            <button type="button" class="btn btn-sm btn-outline-secondary addRangeBtn mt-2">
                <i class="bi bi-plus"></i> เพิ่มช่วงเล่ม
            </button>
        </div>
    </div>
</template>

<template id="rangeTemplate">
    <div class="row g-2 align-items-center mb-2 range-row">
        <div class="col-auto"><span class="text-muted small">เล่ม</span></div>
        <div class="col-4 col-sm-3 col-md-2">
            <input type="number" name="items[__ITEM_INDEX__][ranges][__RANGE_INDEX__][start]" class="form-control range-start" min="1" placeholder="เริ่ม" required>
        </div>
        <div class="col-auto"><span class="text-muted small">ถึง</span></div>
        <div class="col-4 col-sm-3 col-md-2">
            <input type="number" name="items[__ITEM_INDEX__][ranges][__RANGE_INDEX__][end]" class="form-control range-end" min="1" placeholder="สุดท้าย" required>
        </div>
        <div class="col-auto small text-muted">(<span class="range-qty">0</span> เล่ม)</div>
        <div class="col-auto ms-auto ms-sm-0">
            <button type="button" class="btn btn-sm btn-outline-danger removeRangeBtn"><i class="bi bi-x"></i></button>
        </div>
    </div>
</template>

<script>
const itemsContainer = document.getElementById('itemsContainer');
const itemTpl = document.getElementById('itemTemplate');
const rangeTpl = document.getElementById('rangeTemplate');
const shippingInput = document.getElementById('total_shipping_cost');
let itemIndexCounter = 0;

function addItem() {
    const idx = itemIndexCounter++;
    const node = itemTpl.content.cloneNode(true);
    node.querySelector('.item-card').dataset.itemIndex = idx;
    node.querySelector('.item-header').textContent = 'เรื่องที่ ' + (idx + 1);
    // Replace __INDEX__ placeholders in all inputs
    node.querySelectorAll('[name*="__INDEX__"]').forEach(el => {
        el.name = el.name.replace(/__INDEX__/g, idx);
    });
    itemsContainer.appendChild(node);
    // Add default 1 range
    const itemCard = itemsContainer.lastElementChild;
    addRange(itemCard, idx);
    recalcAll();
}

function addRange(itemCard, itemIdx) {
    const container = itemCard.querySelector('.ranges-container');
    const rangeNodes = container.querySelectorAll('.range-row');
    const rangeIdx = rangeNodes.length;
    const node = rangeTpl.content.cloneNode(true);
    node.querySelectorAll('[name*="__RANGE_INDEX__"]').forEach(el => {
        el.name = el.name.replace(/__RANGE_INDEX__/g, rangeIdx).replace(/__ITEM_INDEX__/g, itemIdx);
    });
    container.appendChild(node);
    recalcAll();
}

function getItemQty(itemCard) {
    let qty = 0;
    itemCard.querySelectorAll('.range-row').forEach(row => {
        const vs = parseInt(row.querySelector('.range-start').value) || 0;
        const ve = parseInt(row.querySelector('.range-end').value) || 0;
        if (ve >= vs && vs > 0) qty += (ve - vs + 1);
        row.querySelector('.range-qty').textContent = Math.max(0, ve >= vs ? (ve - vs + 1) : 0);
    });
    return qty;
}

function recalcAll() {
    let totalQty = 0;
    let totalPrice = 0;

    itemsContainer.querySelectorAll('.item-card').forEach(card => {
        const qty = getItemQty(card);
        const price = parseFloat(card.querySelector('.item-price').value) || 0;
        card.querySelector('.item-qty').textContent = qty;
        totalQty += qty;
        totalPrice += price;
    });

    const shipping = parseFloat(shippingInput.value) || 0;
    const shippingPerBook = totalQty > 0 ? (shipping / totalQty) : 0;

    itemsContainer.querySelectorAll('.item-card').forEach(card => {
        const qty = getItemQty(card);
        const price = parseFloat(card.querySelector('.item-price').value) || 0;
        const basePerBook = qty > 0 ? (price / qty) : 0;
        const unitCost = basePerBook + shippingPerBook;
        card.querySelector('.item-unitcost').textContent = unitCost.toFixed(2);
    });

    document.getElementById('totalQtyDisplay').textContent = totalQty;
    document.getElementById('totalPriceDisplay').textContent = totalPrice.toFixed(2);
}

document.getElementById('addItemBtn').addEventListener('click', addItem);

// Pre-fill form เมื่ออยู่ในโหมดแก้ไข
if (Array.isArray(PURCHASE_PREFILL) && PURCHASE_PREFILL.length > 0) {
    // ลบ default row แรกออก
    const defaultCard = itemsContainer.querySelector('.item-card');
    if (defaultCard) defaultCard.remove();

    PURCHASE_PREFILL.forEach(item => {
        addItem();
        const card = itemsContainer.lastElementChild;
        if (!card) return;
        card.querySelector('.item-title').value = item.title || '';
        card.querySelector('.item-price').value = parseFloat(item.item_price || 0).toFixed(2);

        // ลบ range default แล้วใส่จากข้อมูลเดิม
        const rangesContainer = card.querySelector('.ranges-container');
        if (rangesContainer) rangesContainer.innerHTML = '';

        const ranges = item.ranges || [];
        if (ranges.length === 0) {
            addRange(card, parseInt(card.dataset.itemIndex));
        } else {
            ranges.forEach(r => {
                addRange(card, parseInt(card.dataset.itemIndex));
                const rangeRows = card.querySelectorAll('.range-row');
                const lastRow = rangeRows[rangeRows.length - 1];
                if (lastRow) {
                    lastRow.querySelector('.range-start').value = r.start;
                    lastRow.querySelector('.range-end').value = r.end;
                }
            });
        }
    });
    recalcAll();
    // เลื่อนไปที่ฟอร์ม
    setTimeout(() => {
        const form = document.getElementById('purchaseForm');
        if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

itemsContainer.addEventListener('click', function (e) {
    const itemCard = e.target.closest('.item-card');
    if (!itemCard) return;
    const itemIdx = parseInt(itemCard.dataset.itemIndex);

    if (e.target.closest('.addRangeBtn')) {
        addRange(itemCard, itemIdx);
    }
    if (e.target.closest('.removeRangeBtn')) {
        const row = e.target.closest('.range-row');
        row.remove();
        recalcAll();
    }
    if (e.target.closest('.removeItemBtn')) {
        if (itemsContainer.querySelectorAll('.item-card').length <= 1) {
            alert('ต้องมีอย่างน้อย 1 เรื่องในบิล');
            return;
        }
        itemCard.remove();
        recalcAll();
    }
});

itemsContainer.addEventListener('input', function (e) {
    if (e.target.matches('.item-price, .range-start, .range-end')) {
        recalcAll();
    }
});

shippingInput.addEventListener('input', recalcAll);

// เริ่มต้นด้วย 1 เรื่อง
addItem();

// Preview รูปภาพที่เลือก
const imageInput = document.getElementById('purchaseImages');
const imagePreview = document.getElementById('imagePreview');
imageInput.addEventListener('change', function () {
    imagePreview.innerHTML = '';
    const files = Array.from(this.files || []);
    files.forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;width:90px;height:90px;border:1px solid #ddd;border-radius:8px;overflow:hidden;';
            wrap.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;"><div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:10px;text-align:center;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${file.name}</div>`;
            imagePreview.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
});

/* =================== Detail modal =================== */
const PURCHASE_BASE_URL = "<?= BASE_URL ?>";
let purchaseModal = null;
const purchaseModalBody = document.getElementById('purchaseDetailBody');

function ensurePurchaseModal() {
    if (!purchaseModal && typeof bootstrap !== 'undefined') {
        purchaseModal = new bootstrap.Modal(document.getElementById('purchaseDetailModal'));
    }
    return purchaseModal;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeAttr(s) {
    return escapeHtml(s);
}

function renderPurchaseHtml(d) {
    const p = d.purchase;
    const items = p.items || [];
    const images = d.images || [];
    const totalBookCount = items.reduce((s, it) => s + (parseInt(it.book_count) || 0), 0);
    const itemsTotal = items.reduce((s, it) => s + (parseFloat(it.item_price) || 0), 0);
    const shipPerBook = totalBookCount > 0 ? (parseFloat(p.total_shipping_cost) / totalBookCount) : 0;

    const headerHtml = `
        <div class="mb-3">
            <span class="badge bg-secondary mb-1">บิล #${p.id}</span>
            <h5 class="mb-1">${escapeHtml(p.purchase_code)}</h5>
            <div class="small text-muted">
                วันที่: <strong>${escapeHtml(p.purchase_date)}</strong>
                ${p.note ? ' | หมายเหตุ: ' + escapeHtml(p.note) : ''}
            </div>
            <div class="mt-1">
                จำนวนเล่ม: <strong>${totalBookCount}</strong> เล่ม
                | ยอดซื้อ (ไม่รวมส่ง): <strong>${itemsTotal.toFixed(2)} ฿</strong>
                | ค่าส่ง: <strong>${parseFloat(p.total_shipping_cost).toFixed(2)} ฿</strong>
                | รวมทั้งบิล: <strong class="text-primary">${(itemsTotal + parseFloat(p.total_shipping_cost)).toFixed(2)} ฿</strong>
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
                <div class="mb-3 border rounded p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong><i class="bi bi-book"></i> เรื่องที่ ${idx + 1}: ${escapeHtml(it.title)}</strong>
                        <span class="text-muted small">${parseInt(it.book_count) || 0} เล่ม</span>
                    </div>
                    <div class="small mb-2">${volChips || '<span class="text-muted">ไม่มีช่วงเล่ม</span>'}</div>
                    <div class="row g-2 small">
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
            <a href="${PURCHASE_BASE_URL}/${escapeHtml(img.image_path)}" target="_blank">
                <img src="${PURCHASE_BASE_URL}/${escapeHtml(img.image_path)}"
                     alt="slip"
                     style="width:110px;height:110px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">
            </a>
        `).join('');
        imagesHtml = `
            <div class="mt-3">
                <div class="fw-semibold mb-1"><i class="bi bi-image"></i> รูปสลิปการซื้อ (${images.length})</div>
                <div class="d-flex flex-wrap gap-2">${imgs}</div>
            </div>
        `;
    }

    return headerHtml + itemsHtml + imagesHtml;
}

const IS_ADMIN = <?= !empty($__isAdmin) ? 'true' : 'false' ?>;

function renderPurchasesTable(purchases) {
    const $tbody = $('#purchaseHistoryTbody');
    if (!purchases || purchases.length === 0) {
        $tbody.html('<tr><td colspan="10" class="text-center text-muted py-4">ยังไม่มีบิลซื้อ</td></tr>');
        return;
    }

    let html = '';
    purchases.forEach(p => {
        const id = parseInt(p.id);
        const bookCount = parseInt(p.book_count) || 0;
        const itemsTotal = parseFloat(p.items_total) || 0;
        const shipping = parseFloat(p.total_shipping_cost) || 0;
        const total = itemsTotal + shipping;
        const imgCount = parseInt(p.image_count) || 0;

        let imgHtml = '<span class="text-muted small">—</span>';
        if (p.first_image) {
            let badge = imgCount > 1 ? `<span class="badge bg-secondary ms-1">+${imgCount - 1}</span>` : '';
            imgHtml = `
                <a href="${BASE_URL}/${escapeAttr(p.first_image)}" target="_blank" title="ดูรูปสลิป">
                    <img src="${BASE_URL}/${escapeAttr(p.first_image)}" alt="slip" style="width:54px;height:54px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">
                </a>${badge}
            `;
        }

        let adminActions = '';
        if (IS_ADMIN) {
            adminActions = `
                <a class="btn btn-sm btn-outline-warning" href="${BASE_URL}/purchase/create?edit=${id}">
                    <i class="bi bi-pencil-square"></i> แก้ไข
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger delete-purchase-btn"
                        data-id="${id}"
                        data-code="${escapeAttr(p.purchase_code || '')}"
                        data-books="${bookCount}">
                    <i class="bi bi-trash"></i> ลบ
                </button>
            `;
        }

        html += `
            <tr>
                <td data-label="#">${id}</td>
                <td data-label="รหัสบิล">${escapeHtml(p.purchase_code || '')}</td>
                <td data-label="วันที่">${escapeHtml(p.purchase_date || '')}</td>
                <td data-label="note">${escapeHtml(p.note || '')}</td>
                <td data-label="จำนวนเล่ม">${bookCount.toLocaleString()}</td>
                <td data-label="ยอดซื้อ (ไม่รวมส่ง)">${itemsTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</td>
                <td data-label="ค่าส่ง">${shipping.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</td>
                <td data-label="ยอดรวม">${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</td>
                <td data-label="รูปสลิป">${imgHtml}</td>
                <td data-label="" class="row-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary view-purchase-btn" data-id="${id}">
                        <i class="bi bi-eye"></i> ดู
                    </button>
                    ${adminActions}
                </td>
            </tr>
        `;
    });

    $tbody.html(html);
}

function loadPurchasesList() {
    $('#purchaseHistoryTbody').html('<tr><td colspan="10" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm text-primary me-2"></span> กำลังโหลดประวัติบิลซื้อ...</td></tr>');
    $.getJSON(`${BASE_URL}/purchase/list`, function(res) {
        if (res && res.success) {
            renderPurchasesTable(res.purchases || []);
        } else {
            $('#purchaseHistoryTbody').html('<tr><td colspan="10" class="text-center text-danger py-4">โหลดข้อมูลไม่สำเร็จ</td></tr>');
        }
    }).fail(function() {
        $('#purchaseHistoryTbody').html('<tr><td colspan="10" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
    });
}

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.view-purchase-btn');
    if (!btn) return;
    const id = btn.dataset.id;
    const modal = ensurePurchaseModal();
    if (!modal) { alert('Bootstrap ยังโหลดไม่เสร็จ กรุณาลองใหม่'); return; }
    purchaseModalBody.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';
    modal.show();
    try {
        const res = await fetch(`${BASE_URL}/purchase/detail/${id}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'โหลดข้อมูลไม่สำเร็จ');
        purchaseModalBody.innerHTML = renderPurchaseHtml(json.data);
    } catch (err) {
        purchaseModalBody.innerHTML = `<div class="alert alert-danger">${escapeHtml(err.message)}</div>`;
    }
});

document.addEventListener('DOMContentLoaded', ensurePurchaseModal);
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    ensurePurchaseModal();
}

/* =================== Delete purchase =================== */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.delete-purchase-btn');
    if (!btn) return;

    const id   = btn.dataset.id;
    const code = btn.dataset.code || ('#' + id);
    const books = parseInt(btn.dataset.books) || 0;

    const ok = confirm(
        `ยืนยันลบบิลซื้อ "${code}"?\n\n` +
        `จะลบ:\n` +
        `• รายการหนังสือทั้งหมดในบิล\n` +
        `• หนังสือในสต็อก (${books} เล่ม)\n` +
        `• รูปสลิปแนบ (ถ้ามี)\n\n` +
        `⚠️ ไม่สามารถลบได้หากมีหนังสือในบิลถูกขายไปแล้วหรืออยู่ในชุดขาย`
    );
    if (!ok) return;

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังลบ...';

    try {
        const res = await fetch(`${BASE_URL}/purchase/delete/${id}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json().catch(() => ({ success: false, message: 'ตอบกลับไม่ถูกต้อง' }));
        if (!json.success) throw new Error(json.message || 'ลบไม่สำเร็จ');

        alert(json.message || 'ลบบิลซื้อเรียบร้อย');
        loadPurchasesList();
    } catch (err) {
        alert('❌ ' + err.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
});

// Load purchases list on page load
loadPurchasesList();

const btnRefreshPurchases = document.getElementById('btnRefreshPurchases');
if (btnRefreshPurchases) {
    btnRefreshPurchases.addEventListener('click', loadPurchasesList);
}
</script>