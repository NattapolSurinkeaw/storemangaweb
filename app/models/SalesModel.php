<?php
/**
 * SalesModel
 * บันทึกการขาย + คำนวณค่าธรรมเนียม/กำไรสุทธิ
 */
class SalesModel extends Model
{
    /**
     * คำนวณราคาขาย/กำไร แบบไม่บันทึกลง DB (ใช้กับ Pricing Calculator ฝั่งหน้าเว็บ/AJAX)
     *
     * โหมด 'by_target_price'  : รู้ราคาขาย -> หากำไร
     * โหมด 'by_target_profit' : รู้ % กำไรที่ต้องการ (บนต้นทุน) -> หาราคาขายที่ควรตั้ง
     */
    public function calculate(float $cost, string $channel, float $feePercent, float $feeAmount, string $mode, float $value): array
    {
        if ($mode === 'by_target_price') {
            $salePrice = $value;
        } else {
            // mode = by_target_profit_percent : value = % กำไรที่ต้องการบนต้นทุน
            // net_revenue ต้องการ = cost * (1 + profit%)
            // sale_price - fee_amount = cost*(1+profit%)
            if ($channel === 'facebook') {
                $targetNet = $cost * (1 + ($value / 100));
                $salePrice = $targetNet + $feeAmount;
            } else {
                $targetNet = $cost * (1 + ($value / 100));
                $feeRatio = $feePercent / 100;
                $salePrice = $feeRatio < 1 ? $targetNet / (1 - $feeRatio) : $targetNet;
            }
        }

        if ($channel === 'facebook') {
            // ผู้ใช้กรอก fee_amount (ค่าส่ง) -> คำนวณ fee_percent จาก sale_price
            $calcFeeAmount = round($feeAmount, 2);
            $calcFeePercent = $salePrice > 0 ? round(($calcFeeAmount / $salePrice) * 100, 2) : 0.0;
        } else {
            $calcFeePercent = $feePercent;
            $calcFeeAmount  = round($salePrice * ($feePercent / 100), 2);
        }

        $netRevenue  = round($salePrice - $calcFeeAmount, 2);
        $profit      = round($netRevenue - $cost, 2);
        $profitPercent = $cost > 0 ? round(($profit / $cost) * 100, 2) : 0;

        return [
            'cost'           => round($cost, 2),
            'sale_price'     => round($salePrice, 2),
            'fee_percent'    => $calcFeePercent,
            'fee_amount'     => $calcFeeAmount,
            'net_revenue'    => $netRevenue,
            'profit_amount'  => $profit,
            'profit_percent' => $profitPercent,
        ];
    }

    /**
     * บันทึกการขายจริงลง DB และเปลี่ยนสถานะสินค้าเป็น sold
     *
     * @param string $refType 'book' | 'bundle' | 'bundle_group'
     */
    public function recordSale(string $refType, int $refId, string $titleSnapshot, float $cost, string $channel, float $feePercent, float $feeAmount, float $salePrice, string $saleDate): int
    {
        if ($channel === 'facebook') {
            // ผู้ใช้กรอก fee_amount (ค่าส่ง) -> คำนวณ fee_percent จาก sale_price
            $finalFeeAmount  = round($feeAmount, 2);
            $finalFeePercent = $salePrice > 0 ? round(($finalFeeAmount / $salePrice) * 100, 2) : 0.0;
        } else {
            $finalFeePercent = $feePercent;
            $finalFeeAmount  = round($salePrice * ($feePercent / 100), 2);
        }

        $netRevenue = round($salePrice - $finalFeeAmount, 2);
        $profit     = round($netRevenue - $cost, 2);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sales (ref_type, ref_id, title_snapshot, channel, fee_percent, sale_price, fee_amount, net_revenue, cost_amount, profit_amount, sale_date)
                 VALUES (:ref_type, :ref_id, :title, :channel, :fee_percent, :sale_price, :fee_amount, :net_revenue, :cost, :profit, :sale_date)"
            );
            $stmt->execute([
                ':ref_type'    => $refType,
                ':ref_id'      => $refId,
                ':title'       => $titleSnapshot,
                ':channel'     => $channel,
                ':fee_percent' => $finalFeePercent,
                ':sale_price'  => $salePrice,
                ':fee_amount'  => $finalFeeAmount,
                ':net_revenue' => $netRevenue,
                ':cost'        => $cost,
                ':profit'      => $profit,
                ':sale_date'   => $saleDate,
            ]);
            $saleId = (int) $this->db->lastInsertId();

            if ($refType === 'book') {
                $upd = $this->db->prepare("UPDATE inventory_books SET status = 'sold' WHERE id = :id");
                $upd->execute([':id' => $refId]);
            } elseif ($refType === 'bundle') {
                $upd = $this->db->prepare("UPDATE bundles SET status = 'sold' WHERE id = :id");
                $upd->execute([':id' => $refId]);
                $updBooks = $this->db->prepare("UPDATE inventory_books SET status = 'sold' WHERE bundle_id = :id");
                $updBooks->execute([':id' => $refId]);
            } else { // bundle_group
                // atomic: mark group + every bundle in group as sold
                $chk = $this->db->prepare("SELECT status FROM bundle_groups WHERE id = :id FOR UPDATE");
                $chk->execute([':id' => $refId]);
                $grp = $chk->fetch();
                if (!$grp || $grp['status'] !== 'available') {
                    throw new RuntimeException('กลุ่มนี้ไม่อยู่ในสถานะพร้อมขาย');
                }
                $updBundles = $this->db->prepare(
                    "UPDATE bundles SET status = 'sold' WHERE group_id = :gid AND status = 'available'"
                );
                $updBundles->execute([':gid' => $refId]);
                $updGroup = $this->db->prepare("UPDATE bundle_groups SET status = 'sold' WHERE id = :id");
                $updGroup->execute([':id' => $refId]);
                $updBooks = $this->db->prepare(
                    "UPDATE inventory_books ib
                     JOIN bundles b ON ib.bundle_id = b.id
                     SET ib.status = 'sold'
                     WHERE b.group_id = :gid"
                );
                $updBooks->execute([':gid' => $refId]);
            }

            $this->db->commit();
            return $saleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAllSales(): array
    {
        $stmt = $this->db->query(
            "SELECT s.*,
                CASE
                    WHEN s.ref_type = 'book' THEN (
                        SELECT CONCAT(p.id, ':', p.purchase_code)
                        FROM inventory_books ib
                        JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                        JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                        JOIN purchases p ON p.id = pi.purchase_id
                        WHERE ib.id = s.ref_id
                        LIMIT 1
                    )
                    WHEN s.ref_type = 'bundle' THEN (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.id, ':', p.purchase_code) ORDER BY p.id ASC SEPARATOR '||')
                        FROM inventory_books ib
                        JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                        JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                        JOIN purchases p ON p.id = pi.purchase_id
                        WHERE ib.bundle_id = s.ref_id
                    )
                    WHEN s.ref_type = 'bundle_group' THEN (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.id, ':', p.purchase_code) ORDER BY p.id ASC SEPARATOR '||')
                        FROM bundles b
                        JOIN inventory_books ib ON ib.bundle_id = b.id
                        JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                        JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                        JOIN purchases p ON p.id = pi.purchase_id
                        WHERE b.group_id = s.ref_id
                    )
                    ELSE NULL
                END AS purchase_bills
             FROM sales s
             ORDER BY s.sale_date DESC, s.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function findSale(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sales WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * แก้ไขการขาย (เฉพาะราคา/ค่าธรรมเนียม/วันที่/ช่องทาง — ไม่เปลี่ยน ref_type/ref_id)
     * คำนวณ net_revenue / profit ใหม่จาก cost_amount เดิม
     */
    public function updateSale(int $id, string $channel, float $feePercent, float $feeAmount, float $salePrice, string $saleDate): void
    {
        $this->db->beginTransaction();
        try {
            // lock แถวเดิมเพื่อกัน race
            $chk = $this->db->prepare("SELECT cost_amount FROM sales WHERE id = :id FOR UPDATE");
            $chk->execute([':id' => $id]);
            $sale = $chk->fetch();
            if (!$sale) {
                throw new RuntimeException('ไม่พบรายการขายนี้');
            }

            if ($channel === 'facebook') {
                $finalFeeAmount  = round($feeAmount, 2);
                $finalFeePercent = $salePrice > 0 ? round(($finalFeeAmount / $salePrice) * 100, 2) : 0.0;
            } else {
                $finalFeePercent = $feePercent;
                $finalFeeAmount  = round($salePrice * ($feePercent / 100), 2);
            }
            $cost        = (float) $sale['cost_amount'];
            $netRevenue  = round($salePrice - $finalFeeAmount, 2);
            $profit      = round($netRevenue - $cost, 2);

            $upd = $this->db->prepare(
                "UPDATE sales
                    SET channel       = :channel,
                        fee_percent   = :fee_percent,
                        fee_amount    = :fee_amount,
                        sale_price    = :sale_price,
                        net_revenue   = :net_revenue,
                        profit_amount = :profit,
                        sale_date     = :sale_date
                  WHERE id = :id"
            );
            $upd->execute([
                ':channel'     => $channel,
                ':fee_percent' => $finalFeePercent,
                ':fee_amount'  => $finalFeeAmount,
                ':sale_price'  => $salePrice,
                ':net_revenue' => $netRevenue,
                ':profit'      => $profit,
                ':sale_date'   => $saleDate,
                ':id'          => $id,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * ลบการขาย + revert สถานะสินค้ากลับเป็น 'available'
     * - book         -> inventory_books.status = 'available'
     * - bundle       -> bundles.status = 'available'
     * - bundle_group -> bundle_groups + bundles ใน group = 'available'
     */
    public function deleteSale(int $id): array
    {
        $this->db->beginTransaction();
        try {
            $chk = $this->db->prepare("SELECT ref_type, ref_id FROM sales WHERE id = :id FOR UPDATE");
            $chk->execute([':id' => $id]);
            $row = $chk->fetch();
            if (!$row) {
                throw new RuntimeException('ไม่พบรายการขายนี้');
            }

            $refType = $row['ref_type'];
            $refId   = (int) $row['ref_id'];

            // revert สถานะสินค้า
            if ($refType === 'book') {
                $u = $this->db->prepare("UPDATE inventory_books SET status = 'available' WHERE id = :id");
                $u->execute([':id' => $refId]);
            } elseif ($refType === 'bundle') {
                $u = $this->db->prepare("UPDATE bundles SET status = 'available' WHERE id = :id");
                $u->execute([':id' => $refId]);
                $uBooks = $this->db->prepare("UPDATE inventory_books SET status = 'in_bundle' WHERE bundle_id = :id");
                $uBooks->execute([':id' => $refId]);
            } elseif ($refType === 'bundle_group') {
                $u1 = $this->db->prepare("UPDATE bundles SET status = 'available' WHERE group_id = :gid");
                $u1->execute([':gid' => $refId]);
                $u2 = $this->db->prepare("UPDATE bundle_groups SET status = 'available' WHERE id = :id");
                $u2->execute([':id' => $refId]);
                $uBooks = $this->db->prepare(
                    "UPDATE inventory_books ib
                     JOIN bundles b ON ib.bundle_id = b.id
                     SET ib.status = 'in_bundle'
                     WHERE b.group_id = :gid"
                );
                $uBooks->execute([':gid' => $refId]);
            } else {
                throw new RuntimeException("ประเภทสินค้าไม่ถูกต้อง: {$refType}");
            }

            $del = $this->db->prepare("DELETE FROM sales WHERE id = :id");
            $del->execute([':id' => $id]);

            $this->db->commit();
            return [
                'ref_type' => $refType,
                'ref_id'   => $refId,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function getTotals(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COALESCE(SUM(sale_price),0)     AS total_sales,
                COALESCE(SUM(fee_amount),0)     AS total_fee,
                COALESCE(SUM(net_revenue),0)    AS total_net_revenue,
                COALESCE(SUM(cost_amount),0)    AS total_cost,
                COALESCE(SUM(profit_amount),0)  AS total_profit,
                COUNT(*)                        AS total_transactions
             FROM sales"
        );
        return $stmt->fetch();
    }

    public function getTotalsByChannel(): array
    {
        $stmt = $this->db->query(
            "SELECT channel,
                    COUNT(*) AS tx_count,
                    COALESCE(SUM(sale_price),0) AS total_sales,
                    COALESCE(SUM(fee_amount),0) AS total_fee,
                    COALESCE(SUM(profit_amount),0) AS total_profit
             FROM sales GROUP BY channel"
        );
        return $stmt->fetchAll();
    }
}
