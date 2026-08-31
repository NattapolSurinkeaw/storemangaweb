<?php
/**
 * InventoryModel
 * ดูสต็อกคงเหลือ แยกตามเรื่อง/เล่ม
 */
class InventoryModel extends Model
{
    /** สรุปสต็อกคงเหลือ (สถานะ available) แยกตามชื่อเรื่อง */
    public function getStockSummaryByTitle(): array
    {
        $stmt = $this->db->query(
            "SELECT title,
                    COUNT(*) AS available_count,
                    MIN(volume) AS min_volume,
                    MAX(volume) AS max_volume,
                    SUM(unit_cost) AS total_cost
             FROM inventory_books
             WHERE status = 'available'
             GROUP BY title
             ORDER BY title ASC"
        );
        return $stmt->fetchAll();
    }

    /** รายเล่มทั้งหมดของเรื่องหนึ่ง (ทุกสถานะ) */
    public function getBooksByTitle(string $title): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM inventory_books WHERE title = :title ORDER BY volume ASC, purchase_date ASC"
        );
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
     * ใช้แสดงที่มาของสต็อกในหน้า stock/index
     */
    public function getPurchasesByTitle(string $title): array
    {
        // ดึงบิลซื้อที่เกี่ยวข้อง (มีเล่มของเรื่องนี้อยู่) + จำนวนเล่มของเรื่องนี้ในบิลนั้น
        // หมายเหตุ: PDO::ATTR_EMULATE_PREPARES=false ทำให้ MySQL นับ placeholder ซ้ำเป็นคนละตัว
        // จึงต้องใช้ชื่อ placeholder แยกกัน (:title_a, :title_b) และ bind ทั้งสองค่า
        $stmt = $this->db->prepare(
            "SELECT p.id, p.purchase_code, p.purchase_date, p.total_shipping_cost, p.note,
                    (SELECT COUNT(*) FROM inventory_books ib
                       JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                       JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                      WHERE pi.purchase_id = p.id AND ib.title = :title_a) AS book_count_for_title
             FROM purchases p
             WHERE EXISTS (
                 SELECT 1 FROM inventory_books ib
                 JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                 JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                 WHERE pi.purchase_id = p.id AND ib.title = :title_b
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
