<?php
/**
 * ReportModel
 * ประมวลผลและดึงข้อมูลรายงานการซื้อเข้า, ขายออก, กำไรสุทธิ และเปรียบเทียบตามช่วงเวลา
 */
class ReportModel extends Model
{
    /**
     * ดึงช่วงวันที่แรกสุดและล่าสุดจากฐานข้อมูล (ใช้เป็น default ให้กับ datepicker)
     */
    public function getDateBounds(): array
    {
        $stmtP = $this->db->query("SELECT MIN(purchase_date) AS min_d, MAX(purchase_date) AS max_d FROM purchases");
        $rowP = $stmtP->fetch();

        $stmtS = $this->db->query("SELECT MIN(sale_date) AS min_d, MAX(sale_date) AS max_d FROM sales");
        $rowS = $stmtS->fetch();

        $minDate = null;
        if (!empty($rowP['min_d']) && !empty($rowS['min_d'])) {
            $minDate = min($rowP['min_d'], $rowS['min_d']);
        } else {
            $minDate = $rowP['min_d'] ?? $rowS['min_d'] ?? date('Y-m-01');
        }

        $maxDate = null;
        if (!empty($rowP['max_d']) && !empty($rowS['max_d'])) {
            $maxDate = max($rowP['max_d'], $rowS['max_d']);
        } else {
            $maxDate = $rowP['max_d'] ?? $rowS['max_d'] ?? date('Y-m-d');
        }

        return [
            'min_date' => $minDate,
            'max_date' => $maxDate,
        ];
    }

    /**
     * ดึงภาพรวมสถิติสรุป (KPI Metrics) ตามช่วงเวลา
     */
    public function getSummaryMetrics(?string $startDate = null, ?string $endDate = null, string $channel = 'all'): array
    {
        // 1. ซื้อเข้า
        $pWhere = [];
        $pParams = [];
        if (!empty($startDate)) {
            $pWhere[] = "p.purchase_date >= :p_start";
            $pParams[':p_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $pWhere[] = "p.purchase_date <= :p_end";
            $pParams[':p_end'] = $endDate;
        }
        $pWhereSql = !empty($pWhere) ? 'WHERE ' . implode(' AND ', $pWhere) : '';

        $pSql = "SELECT 
                    COUNT(DISTINCT p.id) AS bill_count,
                    COALESCE(SUM(p.total_book_count), 0) AS total_books_purchased,
                    COALESCE(SUM(p.total_shipping_cost), 0) AS total_shipping,
                    COALESCE(SUM(items.items_total), 0) AS items_total,
                    COALESCE(SUM(p.total_shipping_cost + COALESCE(items.items_total, 0)), 0) AS total_purchases_amount
                 FROM purchases p
                 LEFT JOIN (
                     SELECT purchase_id, SUM(item_price) AS items_total
                     FROM purchase_items
                     GROUP BY purchase_id
                 ) items ON items.purchase_id = p.id
                 {$pWhereSql}";

        $stmtP = $this->db->prepare($pSql);
        $stmtP->execute($pParams);
        $pData = $stmtP->fetch() ?: [];

        // 2. ขายออก
        $sWhere = [];
        $sParams = [];
        if (!empty($startDate)) {
            $sWhere[] = "s.sale_date >= :s_start";
            $sParams[':s_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $sWhere[] = "s.sale_date <= :s_end";
            $sParams[':s_end'] = $endDate;
        }
        if ($channel !== 'all' && in_array($channel, ['shopee', 'facebook'], true)) {
            $sWhere[] = "s.channel = :s_channel";
            $sParams[':s_channel'] = $channel;
        }
        $sWhereSql = !empty($sWhere) ? 'WHERE ' . implode(' AND ', $sWhere) : '';

        $sSql = "SELECT 
                    COUNT(s.id) AS sales_count,
                    COALESCE(SUM(s.sale_price), 0) AS total_sales_amount,
                    COALESCE(SUM(s.fee_amount), 0) AS total_fee_amount,
                    COALESCE(SUM(s.net_revenue), 0) AS total_net_revenue,
                    COALESCE(SUM(s.cost_amount), 0) AS total_cost_amount,
                    COALESCE(SUM(s.profit_amount), 0) AS total_profit_amount,
                    COALESCE(SUM(
                        CASE
                            WHEN s.ref_type = 'book' THEN 1
                            WHEN s.ref_type = 'bundle' THEN COALESCE((SELECT book_count FROM bundles WHERE id = s.ref_id), 1)
                            WHEN s.ref_type = 'bundle_group' THEN COALESCE((SELECT book_count FROM bundle_groups WHERE id = s.ref_id), 1)
                            ELSE 1
                        END
                    ), 0) AS total_books_sold
                 FROM sales s
                 {$sWhereSql}";

        $stmtS = $this->db->prepare($sSql);
        $stmtS->execute($sParams);
        $sData = $stmtS->fetch() ?: [];

        // 3. แยกตามช่องทางขาย (Shopee vs Facebook)
        $cSql = "SELECT 
                    s.channel,
                    COUNT(s.id) AS tx_count,
                    COALESCE(SUM(s.sale_price), 0) AS channel_sales,
                    COALESCE(SUM(s.fee_amount), 0) AS channel_fee,
                    COALESCE(SUM(s.profit_amount), 0) AS channel_profit
                 FROM sales s
                 {$sWhereSql}
                 GROUP BY s.channel";
        $stmtC = $this->db->prepare($cSql);
        $stmtC->execute($sParams);
        $channelData = $stmtC->fetchAll() ?: [];

        $totalSales = (float) ($sData['total_sales_amount'] ?? 0);
        $totalPurchases = (float) ($pData['total_purchases_amount'] ?? 0);
        $totalProfit = (float) ($sData['total_profit_amount'] ?? 0);
        $totalCostSold = (float) ($sData['total_cost_amount'] ?? 0);

        $profitMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0.0;
        $roi = $totalCostSold > 0 ? round(($totalProfit / $totalCostSold) * 100, 2) : 0.0;
        $cashFlow = round($totalSales - $totalPurchases, 2);

        return [
            'purchases' => [
                'total_amount' => $totalPurchases,
                'bill_count'   => (int) ($pData['bill_count'] ?? 0),
                'books_count'  => (int) ($pData['total_books_purchased'] ?? 0),
                'shipping'     => (float) ($pData['total_shipping'] ?? 0),
                'items_total'  => (float) ($pData['items_total'] ?? 0),
            ],
            'sales' => [
                'total_amount' => $totalSales,
                'sales_count'  => (int) ($sData['sales_count'] ?? 0),
                'books_sold'   => (int) ($sData['total_books_sold'] ?? 0),
                'fee_amount'   => (float) ($sData['total_fee_amount'] ?? 0),
                'net_revenue'  => (float) ($sData['total_net_revenue'] ?? 0),
                'cost_amount'  => $totalCostSold,
                'profit'       => $totalProfit,
                'margin'       => $profitMargin,
                'roi'          => $roi,
                'by_channel'   => $channelData,
            ],
            'cash_flow' => $cashFlow,
        ];
    }

    /**
     * ดึงข้อมูลเปรียบเทียบตามช่วงเวลา (สำหรับสร้างกราฟแท่งและตารางสรุป)
     * $groupBy: 'month' | 'day' | 'year'
     */
    public function getTimelineComparison(?string $startDate = null, ?string $endDate = null, string $groupBy = 'month', string $channel = 'all'): array
    {
        $dateFormat = match ($groupBy) {
            'day'   => '%Y-%m-%d',
            'year'  => '%Y',
            default => '%Y-%m',
        };

        // 1. ดึง Purchases ตาม period
        $pWhere = [];
        $pParams = [];
        if (!empty($startDate)) {
            $pWhere[] = "p.purchase_date >= :p_start";
            $pParams[':p_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $pWhere[] = "p.purchase_date <= :p_end";
            $pParams[':p_end'] = $endDate;
        }
        $pWhereSql = !empty($pWhere) ? 'WHERE ' . implode(' AND ', $pWhere) : '';

        $pSql = "SELECT 
                    DATE_FORMAT(p.purchase_date, '{$dateFormat}') AS period,
                    COUNT(DISTINCT p.id) AS bill_count,
                    COALESCE(SUM(p.total_book_count), 0) AS books_purchased,
                    COALESCE(SUM(p.total_shipping_cost), 0) AS total_shipping,
                    COALESCE(SUM(items.items_total), 0) AS items_total,
                    COALESCE(SUM(p.total_shipping_cost + COALESCE(items.items_total, 0)), 0) AS purchase_amount
                 FROM purchases p
                 LEFT JOIN (
                     SELECT purchase_id, SUM(item_price) AS items_total
                     FROM purchase_items
                     GROUP BY purchase_id
                 ) items ON items.purchase_id = p.id
                 {$pWhereSql}
                 GROUP BY period
                 ORDER BY period ASC";

        $stmtP = $this->db->prepare($pSql);
        $stmtP->execute($pParams);
        $pRows = $stmtP->fetchAll();

        // 2. ดึง Sales ตาม period
        $sWhere = [];
        $sParams = [];
        if (!empty($startDate)) {
            $sWhere[] = "s.sale_date >= :s_start";
            $sParams[':s_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $sWhere[] = "s.sale_date <= :s_end";
            $sParams[':s_end'] = $endDate;
        }
        if ($channel !== 'all' && in_array($channel, ['shopee', 'facebook'], true)) {
            $sWhere[] = "s.channel = :s_channel";
            $sParams[':s_channel'] = $channel;
        }
        $sWhereSql = !empty($sWhere) ? 'WHERE ' . implode(' AND ', $sWhere) : '';

        $sSql = "SELECT 
                    DATE_FORMAT(s.sale_date, '{$dateFormat}') AS period,
                    COUNT(s.id) AS sales_count,
                    COALESCE(SUM(s.sale_price), 0) AS sales_amount,
                    COALESCE(SUM(s.fee_amount), 0) AS fee_amount,
                    COALESCE(SUM(s.net_revenue), 0) AS net_revenue,
                    COALESCE(SUM(s.cost_amount), 0) AS cost_amount,
                    COALESCE(SUM(s.profit_amount), 0) AS profit_amount,
                    COALESCE(SUM(
                        CASE
                            WHEN s.ref_type = 'book' THEN 1
                            WHEN s.ref_type = 'bundle' THEN COALESCE((SELECT book_count FROM bundles WHERE id = s.ref_id), 1)
                            WHEN s.ref_type = 'bundle_group' THEN COALESCE((SELECT book_count FROM bundle_groups WHERE id = s.ref_id), 1)
                            ELSE 1
                        END
                    ), 0) AS books_sold
                 FROM sales s
                 {$sWhereSql}
                 GROUP BY period
                 ORDER BY period ASC";

        $stmtS = $this->db->prepare($sSql);
        $stmtS->execute($sParams);
        $sRows = $stmtS->fetchAll();

        // 3. รวม periods
        $pMap = [];
        foreach ($pRows as $r) {
            $pMap[$r['period']] = $r;
        }

        $sMap = [];
        foreach ($sRows as $r) {
            $sMap[$r['period']] = $r;
        }

        $allPeriods = array_unique(array_merge(array_keys($pMap), array_keys($sMap)));
        sort($allPeriods);

        $result = [];
        foreach ($allPeriods as $period) {
            $p = $pMap[$period] ?? [];
            $s = $sMap[$period] ?? [];

            $purchaseAmount = (float) ($p['purchase_amount'] ?? 0);
            $billCount      = (int) ($p['bill_count'] ?? 0);
            $booksPurchased = (int) ($p['books_purchased'] ?? 0);

            $salesAmount  = (float) ($s['sales_amount'] ?? 0);
            $salesCount   = (int) ($s['sales_count'] ?? 0);
            $booksSold    = (int) ($s['books_sold'] ?? 0);
            $feeAmount    = (float) ($s['fee_amount'] ?? 0);
            $netRevenue   = (float) ($s['net_revenue'] ?? 0);
            $costAmount   = (float) ($s['cost_amount'] ?? 0);
            $profitAmount = (float) ($s['profit_amount'] ?? 0);

            $profitPercent = $salesAmount > 0 ? round(($profitAmount / $salesAmount) * 100, 1) : 0.0;
            $netCashFlow   = round($salesAmount - $purchaseAmount, 2);

            $result[] = [
                'period'          => $period,
                'label'           => $this->formatPeriodLabel($period, $groupBy),
                'purchase_amount' => $purchaseAmount,
                'bill_count'      => $billCount,
                'books_purchased' => $booksPurchased,
                'sales_amount'    => $salesAmount,
                'sales_count'     => $salesCount,
                'books_sold'      => $booksSold,
                'fee_amount'      => $feeAmount,
                'net_revenue'     => $netRevenue,
                'cost_amount'     => $costAmount,
                'profit_amount'   => $profitAmount,
                'profit_percent'  => $profitPercent,
                'net_cash_flow'   => $netCashFlow,
            ];
        }

        return $result;
    }

    /**
     * ดึงรายการบิลซื้อเข้าทั้งหมดตามช่วงเวลา
     */
    public function getPurchasesList(?string $startDate = null, ?string $endDate = null): array
    {
        $where = [];
        $params = [];
        if (!empty($startDate)) {
            $where[] = "p.purchase_date >= :p_start";
            $params[':p_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "p.purchase_date <= :p_end";
            $params[':p_end'] = $endDate;
        }
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT p.*,
                    COALESCE(items.items_total, 0) AS items_total,
                    (p.total_shipping_cost + COALESCE(items.items_total, 0)) AS grand_total,
                    items.item_titles
                FROM purchases p
                LEFT JOIN (
                    SELECT 
                        pi.purchase_id,
                        SUM(pi.item_price) AS items_total,
                        GROUP_CONCAT(DISTINCT pi.title ORDER BY pi.title SEPARATOR ', ') AS item_titles
                    FROM purchase_items pi
                    GROUP BY pi.purchase_id
                ) items ON items.purchase_id = p.id
                {$whereSql}
                ORDER BY p.purchase_date DESC, p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ดึงรายการขายออกทั้งหมดตามช่วงเวลา
     */
    public function getSalesList(?string $startDate = null, ?string $endDate = null, string $channel = 'all'): array
    {
        $where = [];
        $params = [];
        if (!empty($startDate)) {
            $where[] = "s.sale_date >= :s_start";
            $params[':s_start'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "s.sale_date <= :s_end";
            $params[':s_end'] = $endDate;
        }
        if ($channel !== 'all' && in_array($channel, ['shopee', 'facebook'], true)) {
            $where[] = "s.channel = :s_channel";
            $params[':s_channel'] = $channel;
        }
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT s.*,
                    CASE
                        WHEN s.ref_type = 'book' THEN 1
                        WHEN s.ref_type = 'bundle' THEN COALESCE((SELECT book_count FROM bundles WHERE id = s.ref_id), 1)
                        WHEN s.ref_type = 'bundle_group' THEN COALESCE((SELECT book_count FROM bundle_groups WHERE id = s.ref_id), 1)
                        ELSE 1
                    END AS book_count,
                    ROUND((s.profit_amount / NULLIF(s.sale_price, 0)) * 100, 1) AS profit_margin_percent
                FROM sales s
                {$whereSql}
                ORDER BY s.sale_date DESC, s.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * จัดรูปแบบ Label ภาษาไทยให้ช่วงเวลา
     */
    private function formatPeriodLabel(string $period, string $groupBy): string
    {
        $thaiMonths = [
            '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
            '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
            '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.',
        ];

        if ($groupBy === 'year') {
            $yearCE = (int) $period;
            $yearBE = $yearCE + 543;
            return "ปี {$yearCE} ({$yearBE})";
        }

        if ($groupBy === 'day') {
            $parts = explode('-', $period);
            if (count($parts) === 3) {
                $d = (int) $parts[2];
                $m = $thaiMonths[$parts[1]] ?? $parts[1];
                $y = (int) $parts[0];
                return "{$d} {$m} {$y}";
            }
            return $period;
        }

        // month ('YYYY-MM')
        $parts = explode('-', $period);
        if (count($parts) === 2) {
            $m = $thaiMonths[$parts[1]] ?? $parts[1];
            $y = (int) $parts[0];
            return "{$m} {$y}";
        }

        return $period;
    }
}
