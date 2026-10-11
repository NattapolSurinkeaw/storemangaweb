<?php
/**
 * ReportController
 * จัดการหน้ารายงานการซื้อเข้า, ขายออก, กำไร และการส่งออกข้อมูล
 */
class ReportController extends Controller
{
    private ReportModel $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new ReportModel();
    }

    /**
     * หน้ารายงานหลัก พร้อมกราฟแท่งและตารางสรุป
     */
    public function index(): void
    {
        $bounds = $this->reportModel->getDateBounds();

        // รับค่า filter
        $preset = $this->input('preset', 'all');
        $groupBy = $this->input('group_by', 'month');
        $channel = $this->input('channel', 'all');

        $startDate = $this->input('start_date', '');
        $endDate   = $this->input('end_date', '');

        // ประมวลผล Preset
        $currentYear = date('Y');
        $currentMonth = date('m');

        if ($preset === 'this_year') {
            $startDate = "{$currentYear}-01-01";
            $endDate   = "{$currentYear}-12-31";
        } elseif ($preset === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-t');
        } elseif ($preset === 'last_30_days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate   = date('Y-m-d');
        } elseif ($preset === 'all') {
            $startDate = '';
            $endDate   = '';
        } elseif ($preset === 'custom') {
            // ใช้ค่าที่ส่งมาจาก input
        } else {
            $preset = 'all';
            $startDate = '';
            $endDate   = '';
        }

        // หาก group_by ไม่ถูกต้อง ให้ fallback เป็น month
        if (!in_array($groupBy, ['month', 'day', 'year'], true)) {
            $groupBy = 'month';
        }

        // ดึงข้อมูล
        $summary   = $this->reportModel->getSummaryMetrics($startDate ?: null, $endDate ?: null, $channel);
        $timeline  = $this->reportModel->getTimelineComparison($startDate ?: null, $endDate ?: null, $groupBy, $channel);
        $purchases = $this->reportModel->getPurchasesList($startDate ?: null, $endDate ?: null);
        $sales     = $this->reportModel->getSalesList($startDate ?: null, $endDate ?: null, $channel);

        $this->view('report/index', [
            'bounds'    => $bounds,
            'preset'    => $preset,
            'groupBy'   => $groupBy,
            'channel'   => $channel,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'summary'   => $summary,
            'timeline'  => $timeline,
            'purchases' => $purchases,
            'sales'     => $sales,
        ]);
    }

    /**
     * ส่งออกไฟล์ CSV (รองรับภาษาไทยสำหรับ Excel)
     */
    public function export(): void
    {
        $type      = $this->input('type', 'summary');
        $startDate = $this->input('start_date', '') ?: null;
        $endDate   = $this->input('end_date', '') ?: null;
        $groupBy   = $this->input('group_by', 'month');
        $channel   = $this->input('channel', 'all');

        $filename = 'manga-report-' . $type . '-' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // เขียน UTF-8 BOM สำหรับ Excel
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($type === 'purchases') {
            fputcsv($out, ['วันที่ซื้อ', 'เลขที่บิล', 'รายการเรื่อง', 'จำนวนเล่ม', 'ค่าส่ง (฿)', 'ยอดซื้อรวม (฿)', 'หมายเหตุ']);
            $list = $this->reportModel->getPurchasesList($startDate, $endDate);
            foreach ($list as $p) {
                fputcsv($out, [
                    $p['purchase_date'],
                    $p['purchase_code'],
                    $p['item_titles'] ?? '-',
                    $p['total_book_count'],
                    number_format((float) $p['total_shipping_cost'], 2, '.', ''),
                    number_format((float) $p['grand_total'], 2, '.', ''),
                    $p['note'] ?? '',
                ]);
            }
        } elseif ($type === 'sales') {
            fputcsv($out, ['วันที่ขาย', 'รายการสินค้า', 'ช่องทาง', 'ราคาขาย (฿)', 'ค่าธรรมเนียม (฿)', 'รายรับสุทธิ (฿)', 'ต้นทุน (฿)', 'กำไรสุทธิ (฿)', '% กำไร']);
            $list = $this->reportModel->getSalesList($startDate, $endDate, $channel);
            foreach ($list as $s) {
                fputcsv($out, [
                    $s['sale_date'],
                    $s['title_snapshot'],
                    strtoupper($s['channel']),
                    number_format((float) $s['sale_price'], 2, '.', ''),
                    number_format((float) $s['fee_amount'], 2, '.', ''),
                    number_format((float) $s['net_revenue'], 2, '.', ''),
                    number_format((float) $s['cost_amount'], 2, '.', ''),
                    number_format((float) $s['profit_amount'], 2, '.', ''),
                    ($s['profit_margin_percent'] ?? 0) . '%',
                ]);
            }
        } else {
            // summary timeline
            fputcsv($out, ['ช่วงเวลา', 'ยอดซื้อเข้า (฿)', 'บิลซื้อ', 'เล่มที่ซื้อ', 'ยอดขายออก (฿)', 'รายการขาย', 'เล่มที่ขาย', 'ค่าธรรมเนียม (฿)', 'ต้นทุนขาย (฿)', 'กำไรสุทธิ (฿)', '% กำไร', 'กระแสเงินสดสุทธิ (฿)']);
            $timeline = $this->reportModel->getTimelineComparison($startDate, $endDate, $groupBy, $channel);
            foreach ($timeline as $t) {
                fputcsv($out, [
                    $t['label'],
                    number_format($t['purchase_amount'], 2, '.', ''),
                    $t['bill_count'],
                    $t['books_purchased'],
                    number_format($t['sales_amount'], 2, '.', ''),
                    $t['sales_count'],
                    $t['books_sold'],
                    number_format($t['fee_amount'], 2, '.', ''),
                    number_format($t['cost_amount'], 2, '.', ''),
                    number_format($t['profit_amount'], 2, '.', ''),
                    $t['profit_percent'] . '%',
                    number_format($t['net_cash_flow'], 2, '.', ''),
                ]);
            }
        }

        fclose($out);
        exit;
    }
}
