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
        $stmt = $this->db->query("SELECT * FROM sales ORDER BY sale_date DESC, id DESC");
        return $stmt->fetchAll();
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
