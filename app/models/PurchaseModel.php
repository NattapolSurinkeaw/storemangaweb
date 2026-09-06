<?php
/**
 * PurchaseModel
 * บันทึกบิลซื้อเข้า + ปันส่วนค่าส่ง + แตกสต็อกรายเล่ม
 *
 * รองรับการซื้อแบบขาดเล่ม (non-contiguous volumes):
 *   เช่น ดันดาดัน เล่ม 1-10, 15-20, 22 -> 1 เรื่อง (purchase_item) มี 3 ช่วง (purchase_item_volumes)
 *
 * สูตรต้นทุนต่อเล่ม:
 *   item_qty       = SUM(quantity) ของทุกช่วงในเรื่องเดียวกัน
 *   base_per_book  = item_price / item_qty
 *   shipping_share = total_shipping / total_book_count  (เหมือนเดิมทุกเรื่อง)
 *   unit_cost      = base_per_book + shipping_share
 */
class PurchaseModel extends Model
{
    /**
     * สร้างบิลซื้อพร้อมรายการ (หลายเรื่องใน 1 บิล) แล้วแตกเป็น inventory_books
     *
     * @param array $header  ['purchase_date' => 'Y-m-d', 'total_shipping_cost' => float, 'note' => string]
     * @param array $items   [
     *                          [
     *                            'title' => 'ดันดาดัน',
     *                            'item_price' => 1500,
     *                            'ranges' => [
     *                              ['volume_start' => 1, 'volume_end' => 10],
     *                              ['volume_start' => 15, 'volume_end' => 20],
     *                              ['volume_start' => 22, 'volume_end' => 22],
     *                            ],
     *                          ],
     *                          ...
     *                        ]
     * @return int purchase_id
     */
    public function createPurchase(array $header, array $items): int
    {
        if (empty($items)) {
            throw new InvalidArgumentException('ต้องมีอย่างน้อย 1 รายการในบิล');
        }

        $validated = $this->validateAndCompute($header, $items);

        $this->db->beginTransaction();

        try {
            $purchaseCode = 'PO-' . date('Ymd-His');

            $stmt = $this->db->prepare(
                "INSERT INTO purchases (purchase_code, purchase_date, total_shipping_cost, total_book_count, note)
                 VALUES (:code, :pdate, :ship, :count, :note)"
            );
            $stmt->execute([
                ':code'  => $purchaseCode,
                ':pdate' => $validated['header']['purchase_date'],
                ':ship'  => $validated['header']['total_shipping_cost'],
                ':count' => $validated['totalBookCount'],
                ':note'  => $validated['header']['note'] ?? null,
            ]);
            $purchaseId = (int) $this->db->lastInsertId();

            $this->insertItemsAndBooks($purchaseId, $validated['items'], $validated['header'], $validated['shippingPerBook']);

            $this->db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * แก้ไขบิลซื้อ: ลบ items/volumes/available books เดิม แล้วใส่ใหม่ตามข้อมูลที่ส่งมา
     * - ห้ามแก้ถ้ามีหนังสือถูกขายแล้ว / อยู่ในชุดขาย (เพื่อรักษาประวัติ)
     * - คง purchase_id, purchase_code, รูปสลิปไว้
     *
     * @return array{updated: array{items:int, volumes:int, books:int}}
     */
    public function replacePurchaseItems(int $purchaseId, array $header, array $items): array
    {
        if (empty($items)) {
            throw new InvalidArgumentException('ต้องมีอย่างน้อย 1 รายการในบิล');
        }

        $validated = $this->validateAndCompute($header, $items);

        $this->db->beginTransaction();
        try {
            // 1) ตรวจว่ามีบิลนี้อยู่จริง + ล็อกแถว
            $chk = $this->db->prepare("SELECT id FROM purchases WHERE id = :id FOR UPDATE");
            $chk->execute([':id' => $purchaseId]);
            if (!$chk->fetch()) {
                throw new RuntimeException('ไม่พบบิลซื้อนี้');
            }

            // 2) safety check: ห้ามแก้ถ้ามีหนังสือถูกขาย/อยู่ในชุด
            $cntStmt = $this->db->prepare(
                "SELECT SUM(CASE WHEN ib.status = 'sold'     THEN 1 ELSE 0 END) AS sold_count,
                        SUM(CASE WHEN ib.status = 'in_bundle' THEN 1 ELSE 0 END) AS bundled_count
                 FROM inventory_books ib
                 JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                 JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                 WHERE pi.purchase_id = :pid"
            );
            $cntStmt->execute([':pid' => $purchaseId]);
            $counts = $cntStmt->fetch();
            $sold    = (int) ($counts['sold_count'] ?? 0);
            $bundled = (int) ($counts['bundled_count'] ?? 0);
            if ($sold > 0) {
                throw new RuntimeException(
                    "ไม่สามารถแก้ไขบิลนี้ได้ เนื่องจากมีหนังสือ {$sold} เล่มถูกขายไปแล้ว"
                );
            }
            if ($bundled > 0) {
                throw new RuntimeException(
                    "ไม่สามารถแก้ไขบิลนี้ได้ เนื่องจากมีหนังสือ {$bundled} เล่มอยู่ในชุดขาย (in_bundle)"
                );
            }

            // 3) นับจำนวนก่อนลบ (เพื่อรายงาน)
            $oldVolStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM purchase_item_volumes piv
                   JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                  WHERE pi.purchase_id = :pid"
            );
            $oldVolStmt->execute([':pid' => $purchaseId]);
            $oldVolumeCount = (int) $oldVolStmt->fetchColumn();

            $oldBookStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM inventory_books ib
                   JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                   JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                  WHERE pi.purchase_id = :pid"
            );
            $oldBookStmt->execute([':pid' => $purchaseId]);
            $oldBookCount = (int) $oldBookStmt->fetchColumn();

            // 4) ลบ items เดิม → CASCADE ลบ volumes + inventory_books (เฉพาะ available)
            $delItems = $this->db->prepare("DELETE FROM purchase_items WHERE purchase_id = :pid");
            $delItems->execute([':pid' => $purchaseId]);
            $deletedItemCount = $delItems->rowCount();

            // 5) อัปเดต header ของ purchases
            $upd = $this->db->prepare(
                "UPDATE purchases
                    SET purchase_date = :pdate,
                        total_shipping_cost = :ship,
                        total_book_count = :count,
                        note = :note
                  WHERE id = :id"
            );
            $upd->execute([
                ':pdate' => $validated['header']['purchase_date'],
                ':ship'  => $validated['header']['total_shipping_cost'],
                ':count' => $validated['totalBookCount'],
                ':note'  => $validated['header']['note'] ?? null,
                ':id'    => $purchaseId,
            ]);

            // 6) ใส่ items/volumes/books ใหม่
            $this->insertItemsAndBooks($purchaseId, $validated['items'], $validated['header'], $validated['shippingPerBook']);

            $this->db->commit();

            return [
                'updated' => [
                    'items'   => $deletedItemCount,
                    'volumes' => $oldVolumeCount,
                    'books'   => $oldBookCount,
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Validation ร่วม: ตรวจ items/ranges, รวมจำนวนเล่ม, คำนวณ shipping/เล่ม
     */
    private function validateAndCompute(array $header, array $items): array
    {
        $totalBookCount = 0;
        foreach ($items as &$item) {
            if (empty($item['ranges'])) {
                throw new InvalidArgumentException("'{$item['title']}' ต้องมีอย่างน้อย 1 ช่วงเล่ม");
            }
            $itemQty = 0;
            foreach ($item['ranges'] as $range) {
                $vs = (int) $range['volume_start'];
                $ve = (int) $range['volume_end'];
                if ($ve < $vs) {
                    throw new InvalidArgumentException("'{$item['title']}' ช่วงเล่ม {$vs}-{$ve} ไม่ถูกต้อง");
                }
                $itemQty += ($ve - $vs) + 1;
            }
            if ($itemQty <= 0) {
                throw new InvalidArgumentException("'{$item['title']}' มีจำนวนเล่มเป็น 0");
            }
            $item['__qty'] = $itemQty;
            $totalBookCount += $itemQty;
        }
        unset($item);

        $normalizedHeader = [
            'purchase_date'       => $header['purchase_date'],
            'total_shipping_cost' => (float) ($header['total_shipping_cost'] ?? 0),
            'note'                => $header['note'] ?? null,
        ];
        $shippingPerBook = $totalBookCount > 0 ? $normalizedHeader['total_shipping_cost'] / $totalBookCount : 0;

        return [
            'header'          => $normalizedHeader,
            'items'           => $items,
            'totalBookCount'  => $totalBookCount,
            'shippingPerBook' => $shippingPerBook,
        ];
    }

    /**
     * INSERT purchase_items + purchase_item_volumes + inventory_books
     * ใช้ร่วมกันทั้ง create และ replace
     */
    private function insertItemsAndBooks(int $purchaseId, array $items, array $header, float $shippingPerBook): void
    {
        $itemStmt = $this->db->prepare(
            "INSERT INTO purchase_items (purchase_id, title, item_price)
             VALUES (:pid, :title, :price)"
        );

        $volStmt = $this->db->prepare(
            "INSERT INTO purchase_item_volumes (purchase_item_id, volume_start, volume_end, quantity)
             VALUES (:piid, :vs, :ve, :qty)"
        );

        $bookStmt = $this->db->prepare(
            "INSERT INTO inventory_books (purchase_item_volume_id, title, volume, unit_cost, purchase_date)
             VALUES (:piv, :title, :volume, :cost, :pdate)"
        );

        foreach ($items as $item) {
            $itemPrice = (float) $item['item_price'];
            $itemQty   = (int) $item['__qty'];

            $itemStmt->execute([
                ':pid'   => $purchaseId,
                ':title' => $item['title'],
                ':price' => $itemPrice,
            ]);
            $purchaseItemId = (int) $this->db->lastInsertId();

            $baseCostPerBook = $itemPrice / $itemQty;
            $unitCost = round($baseCostPerBook + $shippingPerBook, 2);

            foreach ($item['ranges'] as $range) {
                $vs = (int) $range['volume_start'];
                $ve = (int) $range['volume_end'];

                $qty = ($ve - $vs) + 1;
                $volStmt->execute([
                    ':piid' => $purchaseItemId,
                    ':vs'   => $vs,
                    ':ve'   => $ve,
                    ':qty'  => $qty,
                ]);
                $volumeId = (int) $this->db->lastInsertId();

                for ($vol = $vs; $vol <= $ve; $vol++) {
                    $bookStmt->execute([
                        ':piv'    => $volumeId,
                        ':title'  => $item['title'],
                        ':volume' => $vol,
                        ':cost'   => $unitCost,
                        ':pdate'  => $header['purchase_date'],
                    ]);
                }
            }
        }
    }

    public function getAllPurchases(): array
    {
        $stmt = $this->db->query(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM inventory_books ib
                       JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                       JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                      WHERE pi.purchase_id = p.id) AS book_count,
                    (SELECT COALESCE(SUM(item_price),0) FROM purchase_items WHERE purchase_id = p.id) AS items_total,
                    (SELECT COUNT(*) FROM purchase_images WHERE purchase_id = p.id) AS image_count,
                    (SELECT image_path FROM purchase_images WHERE purchase_id = p.id ORDER BY id ASC LIMIT 1) AS first_image
             FROM purchases p ORDER BY p.purchase_date DESC"
        );
        return $stmt->fetchAll();
    }

    public function getPurchaseWithItems(int $purchaseId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM purchases WHERE id = :id");
        $stmt->execute([':id' => $purchaseId]);
        $purchase = $stmt->fetch();
        if (!$purchase) return null;

        $itemStmt = $this->db->prepare("SELECT * FROM purchase_items WHERE purchase_id = :id ORDER BY id ASC");
        $itemStmt->execute([':id' => $purchaseId]);
        $items = $itemStmt->fetchAll();

        if (!empty($items)) {
            $volStmt = $this->db->prepare(
                "SELECT * FROM purchase_item_volumes WHERE purchase_item_id IN (" . implode(',', array_fill(0, count($items), '?')) . ") ORDER BY volume_start ASC"
            );
            $volStmt->execute(array_column($items, 'id'));
            $allVolumes = $volStmt->fetchAll();

            $byItem = [];
            foreach ($allVolumes as $v) {
                $byItem[$v['purchase_item_id']][] = $v;
            }
            foreach ($items as &$it) {
                $it['volumes'] = $byItem[$it['id']] ?? [];
            }
            unset($it);
        }

        $purchase['items'] = $items;
        return $purchase;
    }

    public function getTotalPurchaseAmount(): float
    {
        $stmt = $this->db->query(
            "SELECT (SELECT COALESCE(SUM(item_price),0) FROM purchase_items)
                  + (SELECT COALESCE(SUM(total_shipping_cost),0) FROM purchases) AS total"
        );
        return (float) $stmt->fetchColumn();
    }

    /**
     * บันทึกรูปภาพแนบการซื้อเข้า (เรียกหลัง createPurchase)
     * @param string $relPath path แบบ relative เช่น uploads/purchases/PO-xxx_abc.jpg
     */
    public function addPurchaseImage(int $purchaseId, string $relPath, ?string $originalName = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO purchase_images (purchase_id, image_path, original_name) VALUES (:pid, :path, :name)"
        );
        $stmt->execute([
            ':pid'  => $purchaseId,
            ':path' => $relPath,
            ':name' => $originalName,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getPurchaseImages(int $purchaseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM purchase_images WHERE purchase_id = :pid ORDER BY id ASC"
        );
        $stmt->execute([':pid' => $purchaseId]);
        return $stmt->fetchAll();
    }

    /**
     * ลบบิลซื้อ + รายการ + ช่วงเล่ม + inventory_books (เฉพาะเล่มที่ยัง available) + รูปภาพ
     * จะไม่อนุญาตให้ลบถ้าในบิลมีหนังสือที่ถูกขายไปแล้ว (status='sold') หรืออยู่ในชุดขาย (status='in_bundle')
     * เพื่อรักษาความถูกต้องของประวัติการขาย/สต็อก
     *
     * @return array{success:bool, deleted:array{purchases:int, items:int, volumes:int, books:int, images:int}}
     */
    public function deletePurchase(int $purchaseId): array
    {
        $this->db->beginTransaction();
        try {
            // 1) ตรวจว่ามีบิลนี้อยู่จริง
            $chk = $this->db->prepare("SELECT id, purchase_code FROM purchases WHERE id = :id FOR UPDATE");
            $chk->execute([':id' => $purchaseId]);
            $purchase = $chk->fetch();
            if (!$purchase) {
                throw new RuntimeException('ไม่พบบิลซื้อนี้');
            }

            // 2) นับสถานะหนังสือในบิล
            $cntStmt = $this->db->prepare(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN ib.status = 'sold'     THEN 1 ELSE 0 END) AS sold_count,
                        SUM(CASE WHEN ib.status = 'in_bundle' THEN 1 ELSE 0 END) AS bundled_count
                 FROM inventory_books ib
                 JOIN purchase_item_volumes piv ON piv.id = ib.purchase_item_volume_id
                 JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                 WHERE pi.purchase_id = :pid"
            );
            $cntStmt->execute([':pid' => $purchaseId]);
            $counts = $cntStmt->fetch();

            $sold    = (int) ($counts['sold_count'] ?? 0);
            $bundled = (int) ($counts['bundled_count'] ?? 0);
            if ($sold > 0) {
                throw new RuntimeException(
                    "ไม่สามารถลบบิลนี้ได้ เนื่องจากมีหนังสือ {$sold} เล่มถูกขายไปแล้ว"
                );
            }
            if ($bundled > 0) {
                throw new RuntimeException(
                    "ไม่สามารถลบบิลนี้ได้ เนื่องจากมีหนังสือ {$bundled} เล่มอยู่ในชุดขาย (in_bundle)"
                );
            }

            // 3) เก็บ path รูปไว้ลบไฟล์หลัง commit
            $imgStmt = $this->db->prepare("SELECT image_path FROM purchase_images WHERE purchase_id = :pid");
            $imgStmt->execute([':pid' => $purchaseId]);
            $images = $imgStmt->fetchAll();

            // 4) นับจำนวน items / volumes ก่อนลบ (เพื่อรายงานผล)
            $itemStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_items WHERE purchase_id = :pid");
            $itemStmt->execute([':pid' => $purchaseId]);
            $itemCount = (int) $itemStmt->fetchColumn();

            $volStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM purchase_item_volumes piv
                  JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                  WHERE pi.purchase_id = :pid"
            );
            $volStmt->execute([':pid' => $purchaseId]);
            $volCount = (int) $volStmt->fetchColumn();

            // 5) ลบบิล — CASCADE ลบ purchase_items, purchase_item_volumes,
            //    inventory_books (เฉพาะ available), purchase_images
            $del = $this->db->prepare("DELETE FROM purchases WHERE id = :id");
            $del->execute([':id' => $purchaseId]);
            $deletedPurchases = $del->rowCount();
            $bookCount = (int) ($counts['total'] ?? 0);

            $this->db->commit();

            // 6) ลบไฟล์รูปภาพ (ทำหลัง commit)
            $uploadDir = dirname(__DIR__, 2) . '/public';
            foreach ($images as $img) {
                $abs = $uploadDir . '/' . $img['image_path'];
                if (is_file($abs)) @unlink($abs);
            }

            return [
                'success' => true,
                'deleted' => [
                    'purchases' => $deletedPurchases,
                    'items'     => $itemCount,
                    'volumes'   => $volCount,
                    'books'     => $bookCount,
                    'images'    => count($images),
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * ลบรูปภาพ (DB + ไฟล์จริง) — เผื่อใช้ในอนาคต
     */
    public function deletePurchaseImage(int $imageId): bool
    {
        $stmt = $this->db->prepare("SELECT image_path FROM purchase_images WHERE id = :id");
        $stmt->execute([':id' => $imageId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        $del = $this->db->prepare("DELETE FROM purchase_images WHERE id = :id");
        $del->execute([':id' => $imageId]);

        $abs = dirname(__DIR__, 2) . '/public/' . $row['image_path'];
        if (is_file($abs)) @unlink($abs);
        return true;
    }
}