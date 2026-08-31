<div class="page-title">
    <i class="bi bi-collection"></i>
    <h3>จัดชุดขาย <span class="subtitle">(Create Bundle)</span></h3>
</div>

<div class="card p-4 mb-4">
    <form method="POST" action="<?= BASE_URL ?>/index.php?url=stock/bundle" id="bundleForm" enctype="multipart/form-data">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong><i class="bi bi-grid"></i> รายการเรื่องที่จะจัดชุด</strong>
            <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle"></i> เพิ่มเรื่อง
            </button>
        </div>

        <div class="row g-2 mb-2 text-muted small d-none d-md-flex">
            <div class="col-md-4">เรื่อง</div>
            <div class="col-md-3">เล่มเริ่ม</div>
            <div class="col-md-3">เล่มสุดท้าย</div>
            <div class="col-md-2 text-end">ลบ</div>
        </div>

        <div id="itemsArea">
            <!-- rows injected by JS -->
        </div>

        <div class="row g-3 align-items-end mt-3">
            <div class="col-md-4">
                <label class="form-label">ชื่อกลุ่ม (ถ้ามี ใช้เมื่อจัดชุดรวมหลายเรื่อง)</label>
                <input type="text" name="group_label" class="form-control" placeholder="เช่น เซ็ตลดราคาส่งท้ายปี">
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-image"></i> รูปปกชุดขาย (ไม่บังคับ, 1 รูป)</label>
                <input type="file" name="bundle_cover" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                <div id="bundleCoverPreview" class="mt-2" style="display:none;">
                    <img id="bundleCoverImg" src="" style="max-height:120px;border:1px solid #ddd;border-radius:6px;">
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-primary p-3" id="previewBox" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="bi bi-eye"></i> ตัวอย่าง Bundle ที่จะจัด</strong>
                        <span id="previewStatus" class="badge bg-success">พร้อมจัด</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">จำนวนเล่ม</div>
                            <div class="fs-5 fw-bold"><span id="previewCount">0</span> เล่ม</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">จำนวนเรื่อง</div>
                            <div class="fs-5 fw-bold"><span id="previewTitles">0</span> เรื่อง</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">ต้นทุนรวม</div>
                            <div class="fs-5 fw-bold text-primary"><span id="previewCost">0.00</span> ฿</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">เล่มเฉลี่ย/ต้นทุน</div>
                            <div class="fs-5 fw-bold"><span id="previewAvg">0.00</span> ฿</div>
                        </div>
                    </div>
                    <div id="missingList" class="alert alert-danger py-2 small mt-2 mb-0" style="display:none;"></div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" id="submitBtn" class="btn btn-primary" disabled>
                <i class="bi bi-collection"></i> ยืนยันจัดชุด
            </button>
            <span id="submitHint" class="text-muted small ms-2">เพิ่มเรื่องและเลือกช่วงเล่มที่มีสต็อกครบก่อน</span>
        </div>
    </form>
</div>

<?php if (!empty($bundles)): ?>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="panel-title mb-0"><i class="bi bi-link-45deg"></i> รวมชุดสำเร็จรูปเข้าเป็นกลุ่มขายเดียว</div>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/index.php?url=stock/bundle" id="combineForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="combine">
        <div class="row g-2 align-items-end mb-2">
            <div class="col-md-4">
                <label class="form-label">ชื่อกลุ่ม (ไม่บังคับ)</label>
                <input type="text" name="group_label" class="form-control" placeholder="เช่น เซ็ตรวม Naruto + One Piece">
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-image"></i> รูปปกกลุ่ม (ไม่บังคับ)</label>
                <input type="file" name="combine_cover" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="col-md-4">
                <button type="submit" id="combineBtn" class="btn btn-warning" disabled>
                    <i class="bi bi-box2-heart"></i> รวมชุดที่เลือกเป็นกลุ่มเดียว
                </button>
                <span class="text-muted small ms-2">เลือกอย่างน้อย 2 ชุด</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr>
                    <th style="width:40px;"><input type="checkbox" id="checkAllBundles"></th>
                    <th>#</th><th>รูป</th><th>เรื่อง</th><th>ช่วงเล่ม</th><th>จำนวนเล่ม</th><th>ต้นทุนรวม</th>
                </tr></thead>
                <tbody>
                <?php foreach ($bundles as $b): ?>
                    <tr>
                        <td><input type="checkbox" class="bundle-check" name="bundle_ids[]" value="<?= (int)$b['id'] ?>"></td>
                        <td><?= (int)$b['id'] ?></td>
                        <td>
                            <?php if (!empty($b['cover_image'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['cover_image']) ?>"
                                     alt="cover"
                                     style="width:48px;height:64px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                            <?php else: ?>
                                <span class="text-muted small">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td><span class="badge bg-info text-dark">เล่ม <?= (int)$b['volume_start'] ?> - <?= (int)$b['volume_end'] ?></span></td>
                        <td><?= (int)$b['book_count'] ?></td>
                        <td><?= number_format((float)$b['total_cost'], 2) ?> ฿</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card p-4">
    <div class="panel-title"><i class="bi bi-bookshelf"></i> ชุดที่พร้อมขาย</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr>
                <th>#</th><th>ประเภท</th><th>รูป</th><th>เรื่อง</th><th>ช่วงเล่ม</th>
                <th>จำนวนเล่ม</th><th>ต้นทุนรวม</th><th>ต้นทุน/เล่ม</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($bundleGroups as $g): ?>
                <tr>
                    <td>#<?= (int)$g['id'] ?></td>
                    <td><span class="badge bg-warning text-dark"><i class="bi bi-box2-heart"></i> กลุ่มรวม</span></td>
                    <td>
                        <?php if (!empty($g['cover_image'])): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($g['cover_image']) ?>"
                                 alt="cover"
                                 style="width:48px;height:64px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                        <?php else: ?>
                            <span class="text-muted small">ไม่มีรูป</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($g['label'] ?? 'กลุ่มขายรวม') ?></div>
                        <div class="small text-muted"><?= (int)$g['bundle_count'] ?> เรื่อง</div>
                    </td>
                    <td><span class="badge bg-info text-dark">รวมหลายเรื่อง</span></td>
                    <td><?= (int)$g['book_count'] ?></td>
                    <td><?= number_format((float)$g['total_cost'], 2) ?> ฿</td>
                    <td class="text-muted"><?= $g['book_count'] > 0 ? number_format((float)$g['total_cost'] / $g['book_count'], 2) : '0.00' ?> ฿</td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary view-group-btn"
                                data-id="<?= (int)$g['id'] ?>">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </button>
                        <a class="btn btn-sm btn-outline-danger"
                           href="<?= BASE_URL ?>/index.php?url=stock/dissolveBundleGroup/<?= (int)$g['id'] ?>"
                           onclick="return confirm('ยืนยันยกเลิกกลุ่ม #<?= (int)$g['id'] ?> และคืนเล่มทุกเรื่องเข้าสต็อก?');">
                           <i class="bi bi-x-circle"></i> ยกเลิกกลุ่ม
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($bundles as $b): ?>
                <tr>
                    <td><?= (int)$b['id'] ?></td>
                    <td><span class="badge bg-secondary"><i class="bi bi-collection"></i> ชุดเดี่ยว</span></td>
                    <td>
                        <?php if (!empty($b['cover_image'])): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['cover_image']) ?>"
                                 alt="cover"
                                 style="width:48px;height:64px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                        <?php else: ?>
                            <span class="text-muted small">ไม่มีรูป</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($b['title']) ?></td>
                    <td><span class="badge bg-info text-dark">เล่ม <?= (int)$b['volume_start'] ?> - <?= (int)$b['volume_end'] ?></span></td>
                    <td><?= (int)$b['book_count'] ?></td>
                    <td><?= number_format((float)$b['total_cost'], 2) ?> ฿</td>
                    <td class="text-muted"><?= $b['book_count'] > 0 ? number_format((float)$b['total_cost'] / $b['book_count'], 2) : '0.00' ?> ฿</td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary view-bundle-btn"
                                data-id="<?= (int)$b['id'] ?>">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </button>
                        <a class="btn btn-sm btn-outline-danger"
                           href="<?= BASE_URL ?>/index.php?url=stock/dissolveBundle/<?= (int)$b['id'] ?>"
                           onclick="return confirm('ยืนยันยกเลิกชุดนี้และคืนเล่มเข้าสต็อก?');">
                           <i class="bi bi-x-circle"></i> ยกเลิกชุด
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($bundles) && empty($bundleGroups)): ?>
                <tr><td colspan="9" class="text-center text-muted">ยังไม่มีชุดที่จัดไว้</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: รายละเอียด Bundle -->
<div class="modal fade" id="bundleDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye"></i> รายละเอียดชุด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="bundleDetailBody">
                <div class="text-center text-muted py-4">กำลังโหลด...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
const TITLES = <?= json_encode($titles, JSON_UNESCAPED_UNICODE) ?>;

const itemsArea     = document.getElementById('itemsArea');
const addItemBtn    = document.getElementById('addItemBtn');
const previewBox    = document.getElementById('previewBox');
const previewCount  = document.getElementById('previewCount');
const previewTitles = document.getElementById('previewTitles');
const previewCost   = document.getElementById('previewCost');
const previewAvg    = document.getElementById('previewAvg');
const previewStatus = document.getElementById('previewStatus');
const missingList   = document.getElementById('missingList');
const submitBtn     = document.getElementById('submitBtn');
const submitHint    = document.getElementById('submitHint');

let rowCounter = 0;
let previewTimer = null;
const rowsByIndex = new Map(); // index -> {titleEl, vsEl, veEl, previewEl, availableMap, chipsEl, fillBtn}

/* =================== Multi-row form =================== */

function titleOptions() {
    return TITLES.map(t => `<option value="">-- เลือกเรื่อง --</option><option value="${escapeAttr(t)}">${escapeHtml(t)}</option>`).join('');
}
function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function escapeAttr(s) { return escapeHtml(s); }

function addRow() {
    const idx = rowCounter++;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end item-row mb-2';
    row.dataset.idx = idx;
    row.innerHTML = `
        <div class="col-md-4">
            <label class="form-label d-md-none">เรื่อง</label>
            <select class="form-select item-title" required>
                ${titleOptions()}
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label d-md-none">เล่มเริ่ม</label>
            <input type="number" class="form-control item-vs" min="1" placeholder="เล่มเริ่ม" required>
        </div>
        <div class="col-md-3">
            <label class="form-label d-md-none">เล่มสุดท้าย</label>
            <input type="number" class="form-control item-ve" min="1" placeholder="เล่มสุดท้าย" required>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="ลบเรื่องนี้">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="col-12">
            <div class="item-chips-area small text-muted" style="display:none;"></div>
            <div class="item-preview small mt-1"></div>
        </div>
    `;
    itemsArea.appendChild(row);

    const titleEl = row.querySelector('.item-title');
    const vsEl    = row.querySelector('.item-vs');
    const veEl    = row.querySelector('.item-ve');
    const chipsEl = row.querySelector('.item-chips-area');
    const previewEl = row.querySelector('.item-preview');
    const removeBtn = row.querySelector('.remove-row-btn');

    rowsByIndex.set(idx, { titleEl, vsEl, veEl, chipsEl, previewEl, availableMap: {} });

    titleEl.addEventListener('change', () => onTitleChange(idx));
    vsEl.addEventListener('input', () => scheduleRowPreview(idx));
    veEl.addEventListener('input', () => scheduleRowPreview(idx));
    removeBtn.addEventListener('click', () => {
        row.remove();
        rowsByIndex.delete(idx);
        schedulePreview();
    });
}

async function onTitleChange(idx) {
    const r = rowsByIndex.get(idx);
    if (!r) return;
    const title = r.titleEl.value;
    r.chipsEl.style.display = 'none';
    r.chipsEl.innerHTML = '';
    r.previewEl.innerHTML = '';
    r.availableMap = {};
    if (!title) {
        schedulePreview();
        return;
    }
    try {
        const res = await fetch(`${BASE_URL}/index.php?url=stock/getAvailableVolumes&title=${encodeURIComponent(title)}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        r.availableMap = json.data || {};
        renderRowChips(idx);
        scheduleRowPreview(idx);
        schedulePreview();
    } catch (e) {
        r.previewEl.innerHTML = `<span class="text-danger">โหลดข้อมูลไม่สำเร็จ: ${escapeHtml(e.message)}</span>`;
    }
}

function renderRowChips(idx) {
    const r = rowsByIndex.get(idx);
    if (!r) return;
    const map = r.availableMap;
    const keys = Object.keys(map).map(Number);
    if (keys.length === 0) {
        r.chipsEl.style.display = 'none';
        return;
    }
    const minV = Math.min(...keys);
    const maxV = Math.max(...keys);
    const title = r.titleEl.value;
    let html = `<div class="mb-1"><i class="bi bi-grid-3x3-gap"></i> <strong>${escapeHtml(title)}</strong> — คลิกที่เล่มเพื่อตั้งเป็นจุดเริ่ม/จุดสุดท้าย:</div><div class="d-flex flex-wrap gap-1">`;
    for (let v = minV; v <= maxV; v++) {
        const has = map[v] && map[v] > 0;
        html += `<button type="button" class="btn btn-sm ${has ? 'btn-success' : 'btn-outline-secondary'}"
                         style="min-width:40px;" ${has ? '' : 'disabled'}
                         data-vol="${v}" data-idx="${idx}">
                    ${v}${has && map[v] > 1 ? ' ×' + map[v] : ''}
                </button>`;
    }
    html += `</div>`;
    r.chipsEl.innerHTML = html;
    r.chipsEl.style.display = 'block';
    r.chipsEl.querySelectorAll('button[data-vol]').forEach(btn => {
        btn.addEventListener('click', () => {
            const v = parseInt(btn.dataset.vol);
            r.vsEl.value = v;
            let end = v;
            while (r.availableMap[end + 1] && r.availableMap[end + 1] > 0) end++;
            r.veEl.value = end;
            scheduleRowPreview(idx);
            schedulePreview();
        });
    });
}

function scheduleRowPreview(idx) {
    const r = rowsByIndex.get(idx);
    if (!r) return;
    clearTimeout(r._timer);
    r._timer = setTimeout(() => runRowPreview(idx), 200);
}

async function runRowPreview(idx) {
    const r = rowsByIndex.get(idx);
    if (!r) return;
    const title = r.titleEl.value;
    const vs = parseInt(r.vsEl.value);
    const ve = parseInt(r.veEl.value);
    if (!title || !vs || !ve || ve < vs) {
        r.previewEl.innerHTML = '';
        return;
    }
    try {
        const res = await fetch(`${BASE_URL}/index.php?url=stock/previewBundle&title=${encodeURIComponent(title)}&volume_start=${vs}&volume_end=${ve}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const d = json.data;
        if (d.ok) {
            r.previewEl.innerHTML = `<span class="badge bg-success">พร้อม</span> ${d.books.length} เล่ม — ต้นทุน ${d.total_cost.toFixed(2)} ฿`;
        } else {
            r.previewEl.innerHTML = `<span class="badge bg-danger">สต็อกไม่ครบ</span> ขาด ${d.missing.length} เล่ม (${d.missing.map(v => 'เล่ม '+v).join(', ')})`;
        }
    } catch (e) {
        r.previewEl.innerHTML = `<span class="text-danger">${escapeHtml(e.message)}</span>`;
    }
}

/* =================== Total preview across rows =================== */

function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(runPreview, 250);
}

async function runPreview() {
    const rows = collectValidRows();
    if (rows.length === 0) {
        previewBox.style.display = 'none';
        submitBtn.disabled = true;
        submitHint.textContent = 'เพิ่มเรื่องและเลือกช่วงเล่มที่มีสต็อกครบก่อน';
        return;
    }
    // เรียก preview แต่ละเรื่อง แล้วรวม
    let totalBooks = 0, totalCost = 0.0, allMissing = [];
    const titlesSet = new Set();
    let allOk = true;
    for (const r of rows) {
        try {
            const res = await fetch(`${BASE_URL}/index.php?url=stock/previewBundle&title=${encodeURIComponent(r.title)}&volume_start=${r.vs}&volume_end=${r.ve}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            const d = json.data;
            totalBooks += d.books.length;
            totalCost  += d.total_cost;
            titlesSet.add(r.title);
            if (!d.ok) {
                allOk = false;
                d.missing.forEach(v => allMissing.push(`${r.title} เล่ม ${v}`));
            }
        } catch (e) {
            allOk = false;
            allMissing.push(`${r.title}: ${e.message}`);
        }
    }

    previewBox.style.display = 'block';
    previewCount.textContent  = totalBooks;
    previewTitles.textContent = titlesSet.size;
    previewCost.textContent   = totalCost.toFixed(2);
    previewAvg.textContent    = totalBooks > 0 ? (totalCost / totalBooks).toFixed(2) : '0.00';

    if (allOk && allMissing.length === 0) {
        previewStatus.textContent = titlesSet.size > 1 ? 'พร้อมจัด (กลุ่มรวม)' : 'พร้อมจัด';
        previewStatus.className = 'badge bg-success';
        missingList.style.display = 'none';
        submitBtn.disabled = false;
        submitHint.innerHTML = titlesSet.size > 1
            ? `<span class="text-success">สต็อกครบทุกเรื่อง พร้อมจัดเป็นกลุ่มรวมได้</span>`
            : `<span class="text-success">สต็อกครบทุกเล่ม พร้อมจัดชุดได้</span>`;
    } else {
        previewStatus.textContent = 'สต็อกไม่ครบ';
        previewStatus.className = 'badge bg-danger';
        missingList.innerHTML = '<strong>เล่มที่ขาด:</strong> ' + allMissing.join(', ');
        missingList.style.display = 'block';
        submitBtn.disabled = true;
        submitHint.innerHTML = '<span class="text-danger">กรุณาเลือกช่วงเล่มให้สต็อกครบ</span>';
    }
}

function collectValidRows() {
    const out = [];
    rowsByIndex.forEach((r) => {
        const title = r.titleEl.value;
        const vs = parseInt(r.vsEl.value);
        const ve = parseInt(r.veEl.value);
        if (title && vs > 0 && ve >= vs) {
            out.push({ title, vs, ve });
        }
    });
    return out;
}

/* =================== Form submit -> items[] =================== */
document.getElementById('bundleForm').addEventListener('submit', (e) => {
    const rows = collectValidRows();
    if (rows.length === 0) {
        e.preventDefault();
        alert('กรุณาเพิ่มเรื่องและเลือกช่วงเล่มให้ถูกต้อง');
        return;
    }
    // แปลง hidden inputs เป็น items[]
    const form = e.target;
    // ลบ hidden เก่า (ถ้ามี)
    form.querySelectorAll('input[name^="items["]').forEach(el => el.remove());
    rows.forEach((r, i) => {
        [['title', r.title], ['volume_start', r.vs], ['volume_end', r.ve]].forEach(([k, v]) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = `items[${i}][${k}]`;
            inp.value = v;
            form.appendChild(inp);
        });
    });
});

/* =================== Combine existing bundles =================== */
const combineBtn = document.getElementById('combineBtn');
const checkAll = document.getElementById('checkAllBundles');
if (checkAll) {
    checkAll.addEventListener('change', () => {
        document.querySelectorAll('.bundle-check').forEach(cb => cb.checked = checkAll.checked);
        updateCombineBtn();
    });
}
document.querySelectorAll('.bundle-check').forEach(cb => cb.addEventListener('change', updateCombineBtn));
function updateCombineBtn() {
    const n = document.querySelectorAll('.bundle-check:checked').length;
    if (combineBtn) combineBtn.disabled = n < 2;
}

/* =================== Detail modal =================== */
let bundleModal = null;
const bundleModalBody = document.getElementById('bundleDetailBody');
function ensureModal() {
    if (!bundleModal && typeof bootstrap !== 'undefined') {
        bundleModal = new bootstrap.Modal(document.getElementById('bundleDetailModal'));
    }
    return bundleModal;
}

document.querySelectorAll('.view-bundle-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modal = ensureModal();
        if (!modal) { alert('Bootstrap ยังโหลดไม่เสร็จ กรุณาลองใหม่'); return; }
        bundleModalBody.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';
        modal.show();
        try {
            const res = await fetch(`${BASE_URL}/index.php?url=stock/bundleDetail/${id}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            bundleModalBody.innerHTML = renderBundleHtml(json.data);
        } catch (e) {
            bundleModalBody.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
        }
    });
});

document.querySelectorAll('.view-group-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modal = ensureModal();
        if (!modal) { alert('Bootstrap ยังโหลดไม่เสร็จ กรุณาลองใหม่'); return; }
        bundleModalBody.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลด...</div>';
        modal.show();
        try {
            const res = await fetch(`${BASE_URL}/index.php?url=stock/bundleGroupDetail/${id}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            bundleModalBody.innerHTML = renderGroupHtml(json.data);
        } catch (e) {
            bundleModalBody.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
        }
    });
});

function renderBundleHtml(d) {
    const b = d.bundle;
    const books = d.books;
    const cover = b.cover_image || '';
    const coverBlock = cover
        ? `<div class="mb-2 text-center">
               <a href="${BASE_URL}/${escapeAttr(cover)}" target="_blank" title="คลิกเพื่อดูภาพเต็ม">
                   <img src="${BASE_URL}/${escapeAttr(cover)}"
                        style="max-height:260px;max-width:100%;object-fit:contain;border-radius:6px;border:1px solid #ddd;">
               </a>
           </div>`
        : `<div class="alert alert-light text-muted text-center py-2 mb-2">ไม่มีรูปปกชุด</div>`;
    let rows = books.map(bk => `
        <tr>
            <td>${bk.volume}</td>
            <td>${escapeHtml(bk.purchase_date)}</td>
            <td>${parseFloat(bk.unit_cost).toFixed(2)} ฿</td>
            <td><span class="text-muted small">#${bk.id}</span></td>
        </tr>
    `).join('');
    return `
        ${coverBlock}
        <div class="mb-2">
            <span class="badge bg-secondary mb-1">Bundle #${b.id}</span>
            <h5 class="mb-1">${escapeHtml(b.title)}</h5>
            <div>ช่วงเล่ม: <span class="badge bg-info text-dark">${b.volume_start} - ${b.volume_end}</span>
                 | จำนวน: <strong>${b.book_count}</strong> เล่ม
                 | ต้นทุนรวม: <strong class="text-primary">${parseFloat(b.total_cost).toFixed(2)} ฿</strong></div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>เล่ม</th><th>วันที่ซื้อ</th><th>ต้นทุน</th><th>Book ID</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="4" class="text-center text-muted">ไม่มีเล่ม</td></tr>'}</tbody>
            </table>
        </div>
    `;
}

function renderGroupHtml(d) {
    const g = d.group;
    const bundles = d.bundles;
    const groupCover = g.cover_image || '';
    const groupCoverBlock = groupCover
        ? `<div class="mb-2 text-center">
               <a href="${BASE_URL}/${escapeAttr(groupCover)}" target="_blank" title="คลิกเพื่อดูภาพเต็ม">
                   <img src="${BASE_URL}/${escapeAttr(groupCover)}"
                        style="max-height:260px;max-width:100%;object-fit:contain;border-radius:6px;border:1px solid #ddd;">
               </a>
           </div>`
        : `<div class="alert alert-light text-muted text-center py-2 mb-2">ไม่มีรูปปกกลุ่ม</div>`;

    let html = `
        ${groupCoverBlock}
        <div class="mb-3">
            <span class="badge bg-warning text-dark mb-1">Group #${g.id}</span>
            <h5 class="mb-1">${escapeHtml(g.label ?? 'กลุ่มขายรวม')}</h5>
            <div>
                จำนวนเรื่อง: <strong>${g.bundle_count}</strong>
                | จำนวนเล่ม: <strong>${g.book_count}</strong>
                | ต้นทุนรวม: <strong class="text-primary">${parseFloat(g.total_cost).toFixed(2)} ฿</strong>
            </div>
        </div>
    `;
    bundles.forEach(item => {
        const b = item.bundle;
        const books = item.books;
        let rows = books.map(bk => `
            <tr>
                <td>${bk.volume}</td>
                <td>${escapeHtml(bk.purchase_date)}</td>
                <td>${parseFloat(bk.unit_cost).toFixed(2)} ฿</td>
            </tr>
        `).join('');
        html += `
            <div class="mb-3 border rounded p-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong><i class="bi bi-collection"></i> ${escapeHtml(b.title)}</strong>
                    <span class="badge bg-info text-dark">เล่ม ${b.volume_start} - ${b.volume_end}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>เล่ม</th><th>วันที่ซื้อ</th><th>ต้นทุน</th></tr></thead>
                        <tbody>${rows || '<tr><td colspan="3" class="text-center text-muted">ไม่มีเล่ม</td></tr>'}</tbody>
                    </table>
                </div>
                <div class="text-end small text-muted mt-1">
                    ${b.book_count} เล่ม | ต้นทุน ${parseFloat(b.total_cost).toFixed(2)} ฿
                </div>
            </div>
        `;
    });
    return html;
}

/* =================== Init =================== */
addItemBtn.addEventListener('click', addRow);
addRow(); // start with 1 row

// Preview รูปปกชุดที่เลือก
const bundleCoverInput = document.querySelector('input[name="bundle_cover"]');
const bundleCoverPreview = document.getElementById('bundleCoverPreview');
const bundleCoverImg = document.getElementById('bundleCoverImg');
if (bundleCoverInput) {
    bundleCoverInput.addEventListener('change', function () {
        const f = this.files && this.files[0];
        if (!f) {
            bundleCoverPreview.style.display = 'none';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            bundleCoverImg.src = e.target.result;
            bundleCoverPreview.style.display = 'block';
        };
        reader.readAsDataURL(f);
    });
}

// Bootstrap JS โหลดจาก footer.php หลังจากนี้ -> สร้าง Modal ตอน DOM พร้อม
document.addEventListener('DOMContentLoaded', ensureModal);
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    ensureModal();
}
</script>