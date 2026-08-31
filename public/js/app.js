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
});
