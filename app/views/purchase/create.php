<div class="page-title">
    <i class="bi bi-bag-plus"></i>
    <h3>บันทึกบิลซื้อเข้า</h3>
</div>

<div class="card p-4 mb-4">
    <form method="POST" action="<?= BASE_URL ?>/index.php?url=purchase/create" id="purchaseForm" enctype="multipart/form-data">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">วันที่ซื้อ</label>
                <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">ค่าส่งรวมทั้งบิล (บาท)</label>
                <input type="number" step="0.01" min="0" name="total_shipping_cost" id="total_shipping_cost" class="form-control" value="0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">หมายเหตุ</label>
                <input type="text" name="note" class="form-control" placeholder="เช่น ชื่อร้าน / เพจที่ซื้อ">
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">รายการมังงะในบิลนี้ <small class="text-muted">(1 เรื่อง มีได้หลายช่วงเล่ม เช่น 1-10, 15-20, 22)</small></div>
            <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> เพิ่มเรื่อง</button>
        </div>

        <div class="card mb-3 border-info">
            <div class="card-body py-2">
                <label class="form-label fw-semibold mb-1"><i class="bi bi-image"></i> แนบรูปภาพบิลซื้อ (ถ้ามี)</label>
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

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกบิลซื้อ</button>
    </form>
</div>

<div class="card p-4">
    <div class="panel-title"><i class="bi bi-clock-history"></i> ประวัติบิลซื้อเข้า</div>
    <div class="table-responsive">
        <table class="table table-sm table-flex-mobile align-middle">
            <thead><tr><th>#</th><th>รหัสบิล</th><th>วันที่</th><th>note</th><th>จำนวนเล่ม</th><th>ยอดซื้อ (ไม่รวมส่ง)</th><th>ค่าส่ง</th><th>ยอดรวม</th><th>รูปสลิป</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
                <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td><?= htmlspecialchars($p['purchase_code']) ?></td>
                    <td><?= htmlspecialchars($p['purchase_date']) ?></td>
                    <td><?= htmlspecialchars($p['note']) ?></td>
                    <td><?= (int)$p['book_count'] ?></td>
                    <td><?= number_format((float)$p['items_total'], 2) ?> ฿</td>
                    <td><?= number_format((float)$p['total_shipping_cost'], 2) ?> ฿</td>
                    <td><?= number_format((float)$p['items_total'] + (float)$p['total_shipping_cost'], 2) ?> ฿</td>
                    <td>
                        <?php if (!empty($p['first_image'])): ?>
                            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($p['first_image']) ?>" target="_blank" title="ดูรูปสลิป">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['first_image']) ?>"
                                     alt="slip"
                                     style="width:54px;height:54px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">
                            </a>
                            <?php if ((int)($p['image_count'] ?? 0) > 1): ?>
                                <span class="badge bg-secondary">+<?= (int)$p['image_count'] - 1 ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary view-purchase-btn"
                                data-id="<?= (int)$p['id'] ?>">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-purchase-btn"
                                data-id="<?= (int)$p['id'] ?>"
                                data-code="<?= htmlspecialchars($p['purchase_code']) ?>"
                                data-books="<?= (int)$p['book_count'] ?>">
                            <i class="bi bi-trash"></i> ลบบิล
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?>
                <tr><td colspan="8" class="text-center text-muted">ยังไม่มีบิลซื้อ</td></tr>
            <?php endif; ?>
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
        <div class="col-auto"><span class="text-muted">เล่ม</span></div>
        <div class="col-2">
            <input type="number" name="items[__ITEM_INDEX__][ranges][__RANGE_INDEX__][start]" class="form-control range-start" min="1" placeholder="เริ่ม" required>
        </div>
        <div class="col-auto"><span class="text-muted">ถึง</span></div>
        <div class="col-2">
            <input type="number" name="items[__ITEM_INDEX__][ranges][__RANGE_INDEX__][end]" class="form-control range-end" min="1" placeholder="สุดท้าย" required>
        </div>
        <div class="col-auto small text-muted">(<span class="range-qty">0</span> เล่ม)</div>
        <div class="col-auto">
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

document.querySelectorAll('.view-purchase-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modal = ensurePurchaseModal();
        if (!modal) { alert('Bootstrap ยังโหลดไม่เสร็จ กรุณาลองใหม่'); return; }
        purchaseModalBody.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';
        modal.show();
        try {
            const res = await fetch(`${PURCHASE_BASE_URL}/index.php?url=purchase/detail/${id}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'โหลดข้อมูลไม่สำเร็จ');
            purchaseModalBody.innerHTML = renderPurchaseHtml(json.data);
        } catch (e) {
            purchaseModalBody.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
        }
    });
});

document.addEventListener('DOMContentLoaded', ensurePurchaseModal);
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    ensurePurchaseModal();
}

/* =================== Delete purchase =================== */
document.querySelectorAll('.delete-purchase-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
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
            const res = await fetch(`${PURCHASE_BASE_URL}/index.php?url=purchase/delete/${id}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json().catch(() => ({ success: false, message: 'ตอบกลับไม่ถูกต้อง' }));
            if (!json.success) throw new Error(json.message || 'ลบไม่สำเร็จ');

            alert(json.message || 'ลบบิลซื้อเรียบร้อย');
            window.location.reload();
        } catch (e) {
            alert('❌ ' + e.message);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
});
</script>