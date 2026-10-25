document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('table.table-flex-mobile').forEach(function (table) {
        var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
            return th.textContent.trim();
        });
        if (!headers.length) return;

        table.querySelectorAll('tbody tr').forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length === 1 && cells[0].hasAttribute('colspan')) return;
            cells.forEach(function (cell, i) {
                if (headers[i]) cell.setAttribute('data-label', headers[i]);
            });
        });
    });

    // ป้องกัน Browser Warning: Blocked aria-hidden on an element because its descendant retained focus
    // เมื่อ Modal กำลังจะปิด ให้ปลด focus (blur) ออกจากปุ่มหรือ element ภายใน Modal ทันที
    document.addEventListener('hide.bs.modal', function (event) {
        if (document.activeElement && event.target && typeof event.target.contains === 'function') {
            if (event.target.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        }
    });
});
