<?php
/**
 * BundleModel
 * จัดชุดขาย (Bundling) โดยใช้หลัก FIFO ต่อเล่มในช่วงที่เลือก
 */
class BundleModel extends Model
{
    /**
     * จัดชุดจากช่วงเล่ม (title, volume_start-volume_end)
     * สำหรับแต่ละเลขเล่มในช่วง จะหยิบเล่ม 'available' ที่ purchase_date เก่าสุด (FIFO) มา 1 เล่ม
     * ถ้าเล่มไหนไม่มีสต็อกพอ (available = 0) จะ throw exception และไม่บันทึกอะไรเลย (all or nothing)
     * ถ้าระบุ $groupId จะผูก bundle นี้เข้ากับกลุ่ม (ใช้ตอนสร้างหลายเรื่องในกลุ่มเดียว)
     */
    public function createBundle(string $title, int $volumeStart, int $volumeEnd, ?int $groupId = null, ?string $coverImage = null): int
    {
        if ($volumeEnd < $volumeStart) {
            throw new InvalidArgumentException('ช่วงเล่มไม่ถูกต้อง');
        }

        $this->db->beginTransaction();
        try {
            $pickedBookIds = [];
            $totalCost = 0.0;
            $this->pickBooksForRange($title, $volumeStart, $volumeEnd, $pickedBookIds, $totalCost);

            $bundleId = $this->insertBundleRow($title, $volumeStart, $volumeEnd, count($pickedBookIds), $totalCost, $groupId, $coverImage);
            $this->markBooksInBundle($pickedBookIds, $bundleId);

            $this->db->commit();
            return $bundleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** หยิบเล่ม FIFO (ต้องเรียกภายใน transaction ที่ active อยู่) — ใช้ร่วมระหว่าง createBundle/createBundleGroup */
    private function pickBooksForRange(string $title, int $volumeStart, int $volumeEnd, array &$pickedBookIds, float &$totalCost): void
    {
        $fifoStmt = $this->db->prepare(
            "SELECT id, unit_cost FROM inventory_books
              WHERE title = :title AND volume = :volume AND status = 'available'
              ORDER BY purchase_date ASC, id ASC
              LIMIT 1
              FOR UPDATE"
        );

        for ($vol = $volumeStart; $vol <= $volumeEnd; $vol++) {
            $fifoStmt->execute([':title' => $title, ':volume' => $vol]);
            $book = $fifoStmt->fetch();

            if (!$book) {
                throw new RuntimeException("สต็อกไม่พอ: '{$title}' เล่ม {$vol} ไม่มีเล่มว่าง (available)");
            }

            $pickedBookIds[] = (int) $book['id'];
            $totalCost += (float) $book['unit_cost'];
        }
    }

    /** INSERT bundle row (ต้องเรียกภายใน transaction ที่ active อยู่) */
    private function insertBundleRow(string $title, int $volumeStart, int $volumeEnd, int $bookCount, float $totalCost, ?int $groupId, ?string $coverImage = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO bundles (title, volume_start, volume_end, book_count, total_cost, cover_image, status, group_id)
             VALUES (:title, :vs, :ve, :count, :cost, :cover, 'available', :group_id)"
        );
        $stmt->execute([
            ':title'    => $title,
            ':vs'       => $volumeStart,
            ':ve'       => $volumeEnd,
            ':count'    => $bookCount,
            ':cost'     => round($totalCost, 2),
            ':cover'    => $coverImage,
            ':group_id' => $groupId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** UPDATE inventory_books ให้เป็น in_bundle (ต้องเรียกภายใน transaction ที่ active อยู่) */
    private function markBooksInBundle(array $pickedBookIds, int $bundleId): void
    {
        $upd = $this->db->prepare(
            "UPDATE inventory_books SET status = 'in_bundle', bundle_id = :bundle_id WHERE id = :id"
        );
        foreach ($pickedBookIds as $bookId) {
            $upd->execute([':bundle_id' => $bundleId, ':id' => $bookId]);
        }
    }

    /**
     * สร้างกลุ่มขายรวมหลายเรื่อง (atomic — ถ้าเรื่องใด fail ทั้งหมด rollback)
     * @param array<int,array{title:string,volume_start:int,volume_end:int}> $items
     */
    public function createBundleGroup(array $items, ?string $label = null, ?string $coverImage = null): int
    {
        if (count($items) < 1) {
            throw new InvalidArgumentException('ต้องมีรายการอย่างน้อย 1 เรื่อง');
        }

        $this->db->beginTransaction();
        try {
            $grpStmt = $this->db->prepare(
                "INSERT INTO bundle_groups (label, book_count, total_cost, bundle_count, status)
                 VALUES (:label, 0, 0, 0, 'available')"
            );
            $grpStmt->execute([':label' => ($label !== null && $label !== '') ? $label : null]);
            $groupId = (int) $this->db->lastInsertId();

            $totalBooks = 0;
            $totalCost  = 0.0;
            foreach ($items as $it) {
                $title = trim((string) ($it['title'] ?? ''));
                $vs    = (int) ($it['volume_start'] ?? 0);
                $ve    = (int) ($it['volume_end'] ?? 0);
                $cover = $coverImage; // ใช้รูปเดียวกันทุก bundle ในกลุ่ม
                if ($title === '' || $vs <= 0 || $ve < $vs) {
                    throw new InvalidArgumentException("ข้อมูลเรื่อง '{$title}' ไม่ถูกต้อง");
                }

                $picked = [];
                $cost   = 0.0;
                $this->pickBooksForRange($title, $vs, $ve, $picked, $cost);
                $bundleId = $this->insertBundleRow($title, $vs, $ve, count($picked), $cost, $groupId, $cover);
                $this->markBooksInBundle($picked, $bundleId);

                $totalBooks += count($picked);
                $totalCost  += $cost;
            }

            $upd = $this->db->prepare(
                "UPDATE bundle_groups SET book_count = :bc, total_cost = :tc, bundle_count = :nbc
                 WHERE id = :id"
            );
            $upd->execute([
                ':bc'  => $totalBooks,
                ':tc'  => round($totalCost, 2),
                ':nbc' => count($items),
                ':id'  => $groupId,
            ]);

            $this->db->commit();
            return $groupId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * รวม bundle สำเร็จรูปที่มีอยู่แล้ว (ยังไม่ถูกขาย และยังไม่อยู่ในกลุ่มอื่น) เข้าเป็นกลุ่มเดียวกัน
     */
    public function combineBundlesToGroup(array $bundleIds, ?string $label = null, ?string $coverImage = null): int
    {
        $bundleIds = array_values(array_unique(array_map('intval', $bundleIds)));
        if (count($bundleIds) < 2) {
            throw new InvalidArgumentException('ต้องเลือกอย่างน้อย 2 ชุดที่จะรวม');
        }

        $this->db->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($bundleIds), '?'));
            $checkStmt = $this->db->prepare(
                "SELECT id, title, book_count, total_cost, status, group_id
                 FROM bundles WHERE id IN ({$placeholders}) FOR UPDATE"
            );
            $checkStmt->execute($bundleIds);
            $rows = $checkStmt->fetchAll();

            if (count($rows) !== count($bundleIds)) {
                throw new RuntimeException('มี bundle id ที่ไม่ถูกต้อง');
            }
            foreach ($rows as $r) {
                if ($r['status'] !== 'available') {
                    throw new RuntimeException("Bundle #{$r['id']} ไม่อยู่ในสถานะ available");
                }
                if ($r['group_id'] !== null) {
                    throw new RuntimeException("Bundle #{$r['id']} อยู่ในกลุ่มอื่นอยู่แล้ว");
                }
            }

            $totalBooks = array_sum(array_column($rows, 'book_count'));
            $totalCost  = array_sum(array_map('floatval', array_column($rows, 'total_cost')));

            $grpStmt = $this->db->prepare(
                "INSERT INTO bundle_groups (label, book_count, total_cost, bundle_count, status)
                 VALUES (:label, :bc, :tc, :nbc, 'available')"
            );
            $grpStmt->execute([
                ':label' => $label !== '' ? $label : null,
                ':bc'    => $totalBooks,
                ':tc'    => round($totalCost, 2),
                ':nbc'   => count($rows),
            ]);
            $groupId = (int) $this->db->lastInsertId();

            if ($coverImage !== null) {
                $updCover = $this->db->prepare("UPDATE bundles SET group_id = :gid, cover_image = :cover WHERE id = :id");
                foreach ($bundleIds as $bid) {
                    $updCover->execute([':gid' => $groupId, ':cover' => $coverImage, ':id' => $bid]);
                }
            } else {
                $upd = $this->db->prepare("UPDATE bundles SET group_id = :gid WHERE id = :id");
                foreach ($bundleIds as $bid) {
                    $upd->execute([':gid' => $groupId, ':id' => $bid]);
                }
            }

            $this->db->commit();
            return $groupId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** ยกเลิกชุด คืนเล่มทั้งหมดกลับเป็น available (เผื่อกดจัดชุดผิด) */
    public function dissolveBundle(int $bundleId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT status FROM bundles WHERE id = :id");
            $stmt->execute([':id' => $bundleId]);
            $bundle = $stmt->fetch();
            if (!$bundle || $bundle['status'] !== 'available') {
                throw new RuntimeException('ไม่สามารถยกเลิกชุดที่ขายไปแล้วได้');
            }

            $upd = $this->db->prepare(
                "UPDATE inventory_books SET status = 'available', bundle_id = NULL WHERE bundle_id = :bid"
            );
            $upd->execute([':bid' => $bundleId]);

            $del = $this->db->prepare("DELETE FROM bundles WHERE id = :id");
            $del->execute([':id' => $bundleId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** ยกเลิกกลุ่มขายรวม (คืนเล่มเข้าสต็อก + ลบกลุ่ม) */
    public function dissolveBundleGroup(int $groupId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT status FROM bundle_groups WHERE id = :id");
            $stmt->execute([':id' => $groupId]);
            $grp = $stmt->fetch();
            if (!$grp || $grp['status'] !== 'available') {
                throw new RuntimeException('ไม่สามารถยกเลิกกลุ่มที่ขายไปแล้วได้');
            }

            $upd = $this->db->prepare(
                "UPDATE inventory_books SET status = 'available', bundle_id = NULL
                 WHERE bundle_id IN (SELECT id FROM bundles WHERE group_id = :gid)"
            );
            $upd->execute([':gid' => $groupId]);

            $delBundles = $this->db->prepare("DELETE FROM bundles WHERE group_id = :gid");
            $delBundles->execute([':gid' => $groupId]);

            $delGroup = $this->db->prepare("DELETE FROM bundle_groups WHERE id = :id");
            $delGroup->execute([':id' => $groupId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAvailableBundles(): array
    {
        $stmt = $this->db->query(
            "SELECT b.*,
                    (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.id, ':', p.purchase_code) ORDER BY p.id ASC SEPARATOR '||')
                        FROM inventory_books ib
                        JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                        JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                        JOIN purchases p ON p.id = pi.purchase_id
                        WHERE ib.bundle_id = b.id
                    ) AS purchase_bills
             FROM bundles b
             WHERE b.status = 'available' AND b.group_id IS NULL
             ORDER BY b.id DESC"
        );
        return $stmt->fetchAll();
    }

    /** รายการ bundle_groups ที่พร้อมขาย (รวม cover_image จาก bundle แรกในกลุ่ม และรายชื่อเรื่องทั้งหมด) */
    public function getAvailableBundleGroups(): array
    {
        $stmt = $this->db->query(
            "SELECT g.*,
                    (SELECT b.cover_image
                     FROM bundles b
                     WHERE b.group_id = g.id AND b.cover_image IS NOT NULL
                     ORDER BY b.volume_start ASC, b.id ASC
                     LIMIT 1) AS cover_image,
                    (SELECT GROUP_CONCAT(CONCAT(b.title, ' (เล่ม ', b.volume_start, '-', b.volume_end, ')') ORDER BY b.title ASC SEPARATOR '||')
                     FROM bundles b
                     WHERE b.group_id = g.id) AS titles_summary,
                    (SELECT GROUP_CONCAT(DISTINCT b.title ORDER BY b.title ASC SEPARATOR ', ')
                     FROM bundles b
                     WHERE b.group_id = g.id) AS titles_only,
                    (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(p.id, ':', p.purchase_code) ORDER BY p.id ASC SEPARATOR '||')
                        FROM bundles b
                        JOIN inventory_books ib ON ib.bundle_id = b.id
                        JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                        JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                        JOIN purchases p ON p.id = pi.purchase_id
                        WHERE b.group_id = g.id
                    ) AS purchase_bills
             FROM bundle_groups g
             WHERE g.status = 'available'
             ORDER BY g.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function getBundleGroupById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM bundle_groups WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBundlesByGroupId(int $groupId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM bundles WHERE group_id = :gid ORDER BY title ASC, volume_start ASC"
        );
        $stmt->execute([':gid' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * ดึงรายละเอียดกลุ่ม: ข้อมูลกลุ่ม + list ของ bundle ย่อย + หนังสือทุกเล่มในแต่ละ bundle
     * @return array{group:?array, bundles: array<int, array{bundle:array, books:array[]}>}
     */
    public function getBundleGroupDetail(int $groupId): array
    {
        $group = $this->getBundleGroupById($groupId);
        if (!$group) return ['group' => null, 'bundles' => []];

        $bStmt = $this->db->prepare(
            "SELECT * FROM bundles WHERE group_id = :gid ORDER BY title ASC, volume_start ASC"
        );
        $bStmt->execute([':gid' => $groupId]);
        $bundles = $bStmt->fetchAll();

        $bookStmt = $this->db->prepare(
            "SELECT ib.*, p.id AS purchase_id, p.purchase_code
             FROM inventory_books ib
             LEFT JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
             LEFT JOIN purchase_items pi ON pi.id = piv.purchase_item_id
             LEFT JOIN purchases p ON p.id = pi.purchase_id
             WHERE ib.bundle_id = :bid
             ORDER BY ib.volume ASC, ib.purchase_date ASC"
        );
        $items = [];
        foreach ($bundles as $b) {
            $bookStmt->execute([':bid' => $b['id']]);
            $items[] = ['bundle' => $b, 'books' => $bookStmt->fetchAll()];
        }
        return ['group' => $group, 'bundles' => $items];
    }

    /**
     * ดึงรายละเอียดของ bundle เดี่ยว + รายชื่อหนังสือทุกเล่ม
     * @return array{bundle:?array, books:array[]}
     */
    public function getBundleDetail(int $bundleId): array
    {
        $bundle = $this->getBundleById($bundleId);
        if (!$bundle) return ['bundle' => null, 'books' => []];
        return ['bundle' => $bundle, 'books' => $this->getBundleBooks($bundleId)];
    }

    public function getBundleById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM bundles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBundleBooks(int $bundleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ib.*, p.id AS purchase_id, p.purchase_code
             FROM inventory_books ib
             LEFT JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
             LEFT JOIN purchase_items pi ON pi.id = piv.purchase_item_id
             LEFT JOIN purchases p ON p.id = pi.purchase_id
             WHERE ib.bundle_id = :bid
             ORDER BY ib.volume ASC"
        );
        $stmt->execute([':bid' => $bundleId]);
        return $stmt->fetchAll();
    }

    public function markSold(int $bundleId): bool
    {
        $stmt = $this->db->prepare("UPDATE bundles SET status = 'sold' WHERE id = :id");
        return $stmt->execute([':id' => $bundleId]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bundles WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return (int) $stmt->fetchColumn();
    }
}
