<div class="page-title">
    <i class="bi bi-boxes"></i>
    <h3>สต็อกมังงะคงเหลือ</h3>
</div>

<div class="card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="panel-title mb-0"><i class="bi bi-journal-bookmark"></i> สรุปตามเรื่อง</div>
        <div class="d-flex align-items-center gap-2" style="max-width: 360px; width: 100%;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="stockSearchInput" class="form-control border-start-0 ps-0" placeholder="ค้นหาชื่อเรื่อง..." autocomplete="off">
                <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" style="display: none;" title="ล้างการค้นหา">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-flex-mobile" id="stockSummaryTable">
            <thead><tr><th>เรื่อง</th><th>เล่มที่มี (available)</th><th>จำนวน</th><th>ต้นทุนรวม</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stockSummary as $s): ?>
                <tr class="stock-item-row" data-title="<?= htmlspecialchars(mb_strtolower($s['title'], 'UTF-8')) ?>">
                    <td class="stock-title"><?= htmlspecialchars($s['title']) ?></td>
                    <td><?= $s['min_volume'] ?> - <?= $s['max_volume'] ?></td>
                    <td><?= $s['available_count'] ?></td>
                    <td><?= number_format($s['total_cost'], 2) ?> ฿</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary view-title-detail-btn" data-title="<?= htmlspecialchars($s['title']) ?>">
                            <i class="bi bi-eye"></i> ดูรายเล่ม
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="noSearchMatchRow" style="display: none;">
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-search me-1"></i> ไม่พบชื่อเรื่องที่ตรงกับการค้นหา
                </td>
            </tr>
            <?php if (empty($stockSummary)): ?>
                <tr><td colspan="5" class="text-center text-muted">ยังไม่มีสต็อก</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: รายละเอียดรายเล่มและที่มาของสต็อก -->
<div class="modal fade" id="stockDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark text-primary"></i>
                    <span id="stockModalTitle">รายละเอียดสต็อก</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="stockModalBody">
                <div class="text-center text-muted py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                    กำลังโหลดข้อมูล...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
let stockModal = null;
const stockModalEl = document.getElementById('stockDetailModal');
const stockModalTitle = document.getElementById('stockModalTitle');
const stockModalBody = document.getElementById('stockModalBody');

function ensureStockModal() {
    if (!stockModal && typeof bootstrap !== 'undefined' && stockModalEl) {
        stockModal = new bootstrap.Modal(stockModalEl);
    }
    return stockModal;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function openTitleDetail(title) {
    if (!title) return;
    const modal = ensureStockModal();
    if (!modal) return;

    stockModalTitle.textContent = title;
    stockModalBody.innerHTML = `
        <div class="text-center text-muted py-5">
            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
            กำลังโหลดข้อมูลของ "${escapeHtml(title)}"...
        </div>
    `;
    modal.show();

    try {
        const res = await fetch(`${BASE_URL}/index.php?url=stock/titleDetail&title=${encodeURIComponent(title)}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        renderStockDetail(json.data);
    } catch (e) {
        stockModalBody.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> ${escapeHtml(e.message)}</div>`;
    }
}

function renderStockDetail(data) {
    const books = data.books || [];
    const purchases = data.purchases || [];
    const title = data.title || '';

    // สร้างตารางรายเล่ม
    let bookRows = '';
    books.forEach(b => {
        bookRows += `
            <tr>
                <td><strong>เล่ม ${b.volume}</strong></td>
                <td>${parseFloat(b.unit_cost).toFixed(2)} ฿</td>
                <td>${escapeHtml(b.purchase_date)}</td>
                <td><span class="badge badge-status-${b.status}">${b.status}</span></td>
            </tr>
        `;
    });

    if (books.length === 0) {
        bookRows = `<tr><td colspan="4" class="text-center text-muted py-3">ไม่พบข้อมูลรายเล่ม</td></tr>`;
    }

    // สร้างกล่องบิลซื้อ
    let purchaseCards = '';
    purchases.forEach(p => {
        let imgHtml = '';
        if (p.images && p.images.length > 0) {
            imgHtml = `<div class="d-flex flex-wrap gap-2 mt-2">` +
                p.images.map(img => `
                    <a href="${BASE_URL}/${escapeHtml(img.image_path)}" target="_blank" title="คลิกเพื่อดูภาพเต็ม">
                        <img src="${BASE_URL}/${escapeHtml(img.image_path)}"
                             style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                    </a>
                `).join('') +
            `</div>`;
        } else {
            imgHtml = `<div class="small text-muted fst-italic mt-2">ไม่มีรูปแนบ</div>`;
        }

        purchaseCards += `
            <div class="col-md-6">
                <div class="receipt-card p-3 h-100 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold">
                                <i class="bi bi-receipt text-primary"></i> ${escapeHtml(p.purchase_code)}
                            </div>
                            <div class="small text-muted mt-1">
                                วันที่ซื้อ: ${escapeHtml(p.purchase_date)}
                                | เล่มของเรื่องนี้: <strong>${parseInt(p.book_count_for_title)}</strong> เล่ม
                                | ค่าส่ง: ${parseFloat(p.total_shipping_cost).toFixed(2)} ฿
                            </div>
                            ${p.note ? `<div class="small text-muted mt-1">หมายเหตุ: ${escapeHtml(p.note)}</div>` : ''}
                        </div>
                    </div>
                    ${imgHtml}
                </div>
            </div>
        `;
    });

    if (purchases.length === 0) {
        purchaseCards = `<div class="col-12"><div class="text-muted fst-italic">ไม่พบข้อมูลบิลซื้อที่เกี่ยวข้อง</div></div>`;
    }

    stockModalBody.innerHTML = `
        <div class="card p-3 mb-4 shadow-sm border-0 bg-white">
            <div class="panel-title mb-3 fw-bold text-dark">
                <i class="bi bi-list-columns text-primary me-1"></i> รายเล่มทั้งหมดของ "${escapeHtml(title)}" (${books.length} เล่ม)
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>เล่มที่</th><th>ต้นทุน/เล่ม</th><th>วันที่ซื้อ</th><th>สถานะ</th></tr></thead>
                    <tbody>${bookRows}</tbody>
                </table>
            </div>
        </div>

        <div class="card p-3 shadow-sm border-0 bg-white">
            <div class="panel-title mb-3 fw-bold text-dark">
                <i class="bi bi-image text-primary me-1"></i> ที่มาของสต็อก — บิลซื้อที่เกี่ยวข้อง (${purchases.length} บิล)
            </div>
            <div class="row g-3">
                ${purchaseCards}
            </div>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('stockSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const rows = document.querySelectorAll('#stockSummaryTable tbody tr.stock-item-row');
    const noMatchRow = document.getElementById('noSearchMatchRow');

    // Click handler for ดูรายเล่ม buttons
    document.querySelectorAll('.view-title-detail-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            openTitleDetail(btn.dataset.title);
        });
    });

    // ตรวจสอบว่ามี ?title= ใน URL หรือไม่ ถ้ามีให้เปิด modal อัตโนมัติ
    const urlParams = new URLSearchParams(window.location.search);
    const initialTitle = urlParams.get('title');
    if (initialTitle) {
        openTitleDetail(initialTitle);
    }

    if (!searchInput) return;

    function filterStock() {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        if (clearBtn) {
            clearBtn.style.display = query !== '' ? 'inline-block' : 'none';
        }

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            if (query === '' || title.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noMatchRow) {
            noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', filterStock);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            filterStock();
            searchInput.focus();
        });
    }
});
</script>