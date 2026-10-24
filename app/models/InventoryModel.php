<?php
/**
 * InventoryModel
 * ดูสต็อกคงเหลือ แยกตามเรื่อง/เล่ม
 */
class InventoryModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->syncSoldBooksStatus();
    }

    /**
     * ซิงค์สถานะหนังสือที่ขายแล้วให้ถูกต้อง (Self-healing data sync)
     * แก้ปัญหาข้อมูลย้อนหลัง: กรณีที่ขาย bundle หรือขายเดี่ยวไปแล้ว แต่สถานะใน inventory_books ยังค้างเป็น 'in_bundle' หรือ 'available'
     */
    public function syncSoldBooksStatus(): void
    {
        try {
            // 1. ซิงค์สถานะ bundles ที่ถูกบันทึกขายในตาราง sales แล้ว
            $this->db->exec(
                "UPDATE bundles b
                 JOIN sales s ON s.ref_type = 'bundle' AND s.ref_id = b.id
                 SET b.status = 'sold'
                 WHERE b.status != 'sold'"
            );

            // 2. ซิงค์สถานะ bundle_groups ที่ถูกบันทึกขายในตาราง sales แล้ว
            $this->db->exec(
                "UPDATE bundle_groups bg
                 JOIN sales s ON s.ref_type = 'bundle_group' AND s.ref_id = bg.id
                 SET bg.status = 'sold'
                 WHERE bg.status != 'sold'"
            );

            // 3. เล่มที่อยู่ใน bundle ที่มีสถานะ 'sold' ให้เปลี่ยนเป็น 'sold'
            $this->db->exec(
                "UPDATE inventory_books ib
                 JOIN bundles b ON ib.bundle_id = b.id
                 SET ib.status = 'sold'
                 WHERE b.status = 'sold' AND ib.status != 'sold'"
            );

            // 4. เล่มที่อยู่ใน bundle group ที่มีสถานะ 'sold' ให้เปลี่ยนเป็น 'sold'
            $this->db->exec(
                "UPDATE inventory_books ib
                 JOIN bundles b ON ib.bundle_id = b.id
                 JOIN bundle_groups bg ON b.group_id = bg.id
                 SET ib.status = 'sold'
                 WHERE bg.status = 'sold' AND ib.status != 'sold'"
            );

            // 5. เล่มที่ถูกขายเดี่ยวตามตาราง sales (ref_type = 'book')
            $this->db->exec(
                "UPDATE inventory_books ib
                 JOIN sales s ON s.ref_type = 'book' AND s.ref_id = ib.id
                 SET ib.status = 'sold'
                 WHERE ib.status != 'sold'"
            );

            // 6. เล่มที่ถูกขายแบบ bundle ตามตาราง sales (ref_type = 'bundle')
            $this->db->exec(
                "UPDATE inventory_books ib
                 JOIN sales s ON s.ref_type = 'bundle' AND s.ref_id = ib.bundle_id
                 SET ib.status = 'sold'
                 WHERE ib.status != 'sold'"
            );

            // 7. เล่มที่ถูกขายแบบ bundle_group ตามตาราง sales (ref_type = 'bundle_group')
            $this->db->exec(
                "UPDATE inventory_books ib
                 JOIN bundles b ON ib.bundle_id = b.id
                 JOIN sales s ON s.ref_type = 'bundle_group' AND s.ref_id = b.group_id
                 SET ib.status = 'sold'
                 WHERE ib.status != 'sold'"
            );
        } catch (Throwable $e) {
            error_log('syncSoldBooksStatus failed: ' . $e->getMessage());
        }
    }

    /** สรุปสต็อกคงเหลือแยกตามชื่อเรื่อง (แสดงหนังสือทั้งหมดที่ยังไม่ขาย) พร้อมแยกจำนวนจัดชุดแล้วและยังไม่จัดชุด */
    public function getStockSummaryByTitle(): array
    {
        $this->syncSoldBooksStatus();

        $stmt = $this->db->query(
            "SELECT ib.title,
                    COUNT(*) AS total_count,
                    COUNT(*) AS book_count,
                    SUM(CASE WHEN ib.status = 'in_bundle' AND (b.id IS NULL OR b.status != 'sold') AND (bg.id IS NULL OR bg.status != 'sold') THEN 1 ELSE 0 END) AS in_bundle_count,
                    SUM(CASE WHEN ib.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                    MIN(ib.volume) AS min_volume,
                    MAX(ib.volume) AS max_volume,
                    GROUP_CONCAT(DISTINCT ib.volume ORDER BY ib.volume ASC SEPARATOR ', ') AS volume_list,
                    SUM(ib.unit_cost) AS total_cost
             FROM inventory_books ib
             LEFT JOIN bundles b ON ib.bundle_id = b.id
             LEFT JOIN bundle_groups bg ON b.group_id = bg.id
             WHERE ib.status != 'sold'
               AND (b.id IS NULL OR b.status != 'sold')
               AND (bg.id IS NULL OR bg.status != 'sold')
             GROUP BY ib.title
             HAVING total_count > 0
             ORDER BY ib.title ASC"
        );
        return $stmt->fetchAll();
    }

    /** รายเล่มของเรื่องหนึ่ง (ค่าเริ่มต้นดึงเฉพาะเล่มที่ยังไม่ขาย) */
    public function getBooksByTitle(string $title, bool $unsoldOnly = true): array
    {
        $sql = "SELECT ib.*,
                       b.volume_start AS bundle_vol_start,
                       b.volume_end AS bundle_vol_end,
                       b.status AS bundle_status
                FROM inventory_books ib
                LEFT JOIN bundles b ON ib.bundle_id = b.id
                LEFT JOIN bundle_groups bg ON b.group_id = bg.id
                WHERE ib.title = :title";
        if ($unsoldOnly) {
            $sql .= " AND ib.status != 'sold'
                      AND (b.id IS NULL OR b.status != 'sold')
                      AND (bg.id IS NULL OR bg.status != 'sold')";
        }
        $sql .= " ORDER BY ib.volume ASC, ib.purchase_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':title' => $title]);
        return $stmt->fetchAll();
    }

    /** รายชื่อเรื่องทั้งหมดที่เคยซื้อเข้ามา (ไว้ทำ dropdown) */
    public function getDistinctTitles(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT title FROM inventory_books ORDER BY title ASC");
        return array_column($stmt->fetchAll(), 'title');
    }

    /** รายชื่อเรื่องที่ยังมีเล่มว่าง (available) อย่างน้อย 1 เล่ม — ใช้กับหน้าจัดชุด */
    public function getDistinctTitlesWithStock(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT title FROM inventory_books
              WHERE status = 'available'
              ORDER BY title ASC"
        );
        return array_column($stmt->fetchAll(), 'title');
    }

    /** เล่มที่ available ของเรื่อง+เล่มที่ระบุ เรียง FIFO (ซื้อมาก่อนได้ก่อน) */
    public function getAvailableBooksFIFO(string $title, int $volume): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM inventory_books
              WHERE title = :title AND volume = :volume AND status = 'available'
              ORDER BY purchase_date ASC, id ASC"
        );
        $stmt->execute([':title' => $title, ':volume' => $volume]);
        return $stmt->fetchAll();
    }

    /**
     * สรุปว่าเรื่องนี้มีเล่มอะไร available บ้าง (volume -> count)
     * ใช้กับหน้าจัดชุดเพื่อแสดง chip สีเขียว/เทา
     */
    public function getAvailableVolumesForTitle(string $title): array
    {
        $stmt = $this->db->prepare(
            "SELECT volume, COUNT(*) AS qty
             FROM inventory_books
             WHERE title = :title AND status = 'available'
             GROUP BY volume
             ORDER BY volume ASC"
        );
        $stmt->execute([':title' => $title]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['volume']] = (int) $r['qty'];
        }
        return $map;
    }

    /**
     * จำลองการหยิบเล่มแบบ FIFO ตามช่วงที่ระบุ เพื่อคำนวณราคารวม + รายชื่อเล่มล่วงหน้า
     * ไม่เปลี่ยนแปลง DB — ใช้แสดงตัวอย่างก่อนกดจัดชุดจริง
     *
     * @return array{ok:bool, missing: int[], books: array[], total_cost: float}
     */
    public function previewBundle(string $title, int $volumeStart, int $volumeEnd): array
    {
        if ($volumeEnd < $volumeStart) {
            return ['ok' => false, 'missing' => [], 'books' => [], 'total_cost' => 0.0, 'error' => 'ช่วงเล่มไม่ถูกต้อง'];
        }

        $stmt = $this->db->prepare(
            "SELECT id, volume, unit_cost, purchase_date
             FROM inventory_books
             WHERE title = :title AND volume = :volume AND status = 'available'
             ORDER BY purchase_date ASC, id ASC
             LIMIT 1"
        );

        $books = [];
        $totalCost = 0.0;
        $missing = [];

        for ($vol = $volumeStart; $vol <= $volumeEnd; $vol++) {
            $stmt->execute([':title' => $title, ':volume' => $vol]);
            $book = $stmt->fetch();
            if (!$book) {
                $missing[] = $vol;
            } else {
                $books[] = $book;
                $totalCost += (float) $book['unit_cost'];
            }
        }

        return [
            'ok'         => empty($missing),
            'missing'    => $missing,
            'books'      => $books,
            'total_cost' => round($totalCost, 2),
        ];
    }

    public function getBookById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM inventory_books WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAvailableSingleBooks(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM inventory_books WHERE status = 'available' ORDER BY title ASC, volume ASC"
        );
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?int $bundleId = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE inventory_books SET status = :status, bundle_id = :bundle_id WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':bundle_id' => $bundleId, ':id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inventory_books WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * ดึงรายการบิลซื้อทั้งหมดที่เกี่ยวข้องกับเรื่องหนึ่งๆ พร้อมรูปภาพแนบ
     * ใช้แสดงที่มาของสต็อกในหน้า stock/index (ค่าเริ่มต้นกรองเฉพาะเล่มที่ยังไม่ขาย)
     */
    public function getPurchasesByTitle(string $title, bool $unsoldOnly = true): array
    {
        // ดึงบิลซื้อที่เกี่ยวข้อง (มีเล่มของเรื่องนี้อยู่) + จำนวนเล่มของเรื่องนี้ในบิลนั้น
        // หมายเหตุ: PDO::ATTR_EMULATE_PREPARES=false ทำให้ MySQL นับ placeholder ซ้ำเป็นคนละตัว
        // จึงต้องใช้ชื่อ placeholder แยกกัน (:title_a, :title_b) และ bind ทั้งสองค่า
        $soldCond = $unsoldOnly ? "AND ib.status != 'sold' AND (b.id IS NULL OR b.status != 'sold') AND (bg.id IS NULL OR bg.status != 'sold')" : "";
        $stmt = $this->db->prepare(
            "SELECT p.id, p.purchase_code, p.purchase_date, p.total_shipping_cost, p.note,
                    (SELECT COUNT(*) FROM inventory_books ib
                       JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                       JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                       LEFT JOIN bundles b ON ib.bundle_id = b.id
                       LEFT JOIN bundle_groups bg ON b.group_id = bg.id
                      WHERE pi.purchase_id = p.id AND ib.title = :title_a {$soldCond}) AS book_count_for_title
             FROM purchases p
             WHERE EXISTS (
                 SELECT 1 FROM inventory_books ib
                 JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                 JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                 LEFT JOIN bundles b ON ib.bundle_id = b.id
                 LEFT JOIN bundle_groups bg ON b.group_id = bg.id
                 WHERE pi.purchase_id = p.id AND ib.title = :title_b {$soldCond}
             )
             ORDER BY p.purchase_date DESC, p.id DESC"
        );
        $stmt->execute([':title_a' => $title, ':title_b' => $title]);
        $purchases = $stmt->fetchAll();

        if (empty($purchases)) return [];

        // ดึงรูปทั้งหมดของบิลที่เกี่ยวข้อง
        $ids = array_column($purchases, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $imgStmt = $this->db->prepare(
            "SELECT id, purchase_id, image_path FROM purchase_images WHERE purchase_id IN ({$placeholders}) ORDER BY id ASC"
        );
        $imgStmt->execute($ids);
        $images = $imgStmt->fetchAll();

        $byPurchase = [];
        foreach ($images as $img) {
            $byPurchase[$img['purchase_id']][] = $img;
        }

        foreach ($purchases as &$p) {
            $p['images'] = $byPurchase[$p['id']] ?? [];
        }
        unset($p);

        return $purchases;
    }
}
