<?php
/**
 * PurchaseController
 * บันทึกบิลซื้อเข้า (หลายรายการต่อบิล) + ปันส่วนค่าส่ง + แนบรูปภาพ
 *
 * รองรับการซื้อแบบขาดเล่ม (1 เรื่อง มีหลายช่วงเล่ม เช่น 1-10, 15-20, 22)
 */
class PurchaseController extends Controller
{
    private PurchaseModel $purchaseModel;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_BYTES    = 5 * 1024 * 1024; // 5 MB ต่อไฟล์

    public function __construct()
    {
        parent::__construct();
        $this->purchaseModel = new PurchaseModel();
    }

    public function index(): void
    {
        $this->view('purchase/create', [
            'purchases' => $this->purchaseModel->getAllPurchases(),
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        // ตรวจ ?edit={id} สำหรับโหมดแก้ไข
        $editId = (int) ($_GET['edit'] ?? 0);
        $editing = null;
        $editingImages = [];
        if ($editId > 0) {
            $editing = $this->purchaseModel->getPurchaseWithItems($editId);
            if (!$editing) {
                $this->flash('warning', 'ไม่พบบิลซื้อที่ต้องการแก้ไข');
                $this->redirect('purchase/create');
            }
            $editingImages = $this->purchaseModel->getPurchaseImages($editId);
            // แปลง volumes → ranges เพื่อให้ form pre-fill ได้ง่าย
            foreach ($editing['items'] as &$it) {
                $it['ranges'] = array_map(fn($v) => [
                    'start' => (int) $v['volume_start'],
                    'end'   => (int) $v['volume_end'],
                ], $it['volumes'] ?? []);
            }
            unset($it);
        }

        $this->view('purchase/create', [
            'purchases'     => $this->purchaseModel->getAllPurchases(),
            'editing'       => $editing,
            'editingImages' => $editingImages,
        ]);
    }

    /**
     * บันทึกการแก้ไขบิลซื้อ (POST เท่านั้น)
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->flash('danger', 'Method not allowed');
            $this->redirect('purchase/create');
        }

        try {
            $header = [
                'purchase_date'       => $this->input('purchase_date', date('Y-m-d')),
                'total_shipping_cost' => (float) $this->input('total_shipping_cost', 0),
                'note'                => $this->input('note', ''),
            ];

            $items = $this->parseItemsFromPost();

            $result = $this->purchaseModel->replacePurchaseItems($id, $header, $items);
            $u = $result['updated'];

            $this->flash('success',
                "แก้ไขบิลซื้อ #{$id} สำเร็จ (อัปเดต {$u['items']} รายการ, {$u['volumes']} ช่วงเล่ม, {$u['books']} เล่ม)"
            );
        } catch (Throwable $e) {
            $this->flash('danger', 'แก้ไขไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('purchase/create?edit=' . $id);
            return;
        }

        $this->redirect('purchase/create');
    }

    /**
     * ดึงรายละเอียดบิลซื้อ (JSON) — ใช้กับ modal ในหน้า create, bundle, stock, report
     */
    public function detail($id = 0): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $purchase = null;
            if (is_numeric($id) && (int) $id > 0) {
                $purchase = $this->purchaseModel->getPurchaseWithItems((int) $id);
            }
            if (!$purchase && !empty($id)) {
                $purchase = $this->purchaseModel->getPurchaseByCode((string) $id);
            }

            if (!$purchase) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบบิลซื้อนี้']);
                return;
            }

            $purchaseId = (int) $purchase['id'];
            $images = $this->purchaseModel->getPurchaseImages($purchaseId);

            // คำนวณ total_book_count ของแต่ละ item
            foreach ($purchase['items'] as &$it) {
                $qty = 0;
                foreach (($it['volumes'] ?? []) as $v) {
                    $qty += (int) ($v['quantity'] ?? 0);
                }
                $it['book_count'] = $qty;
                $it['unit_cost']  = $qty > 0 ? round(((float) $it['item_price']) / $qty, 2) : 0.0;
            }
            unset($it);

            echo json_encode([
                'success' => true,
                'data'    => [
                    'purchase' => $purchase,
                    'images'   => $images,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * ลบบิลซื้อ (POST เท่านั้น) — ใช้กับปุ่มลบในหน้า create
     * รับเฉพาะ POST เพื่อความปลอดภัย + ป้องกันการเปิดลิงก์ลบโดยบังเอิญ
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $result = $this->purchaseModel->deletePurchase($id);
            $d = $result['deleted'];
            $msg = "ลบบิลซื้อเรียบร้อย (ลบ {$d['items']} รายการ, {$d['books']} เล่ม";
            if ($d['images'] > 0) {
                $msg .= ", ลบรูปแนบ {$d['images']} ไฟล์";
            }
            $msg .= ')';
            echo json_encode([
                'success' => true,
                'message' => $msg,
                'data'    => $d,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function store(): void
    {
        try {
            $header = [
                'purchase_date'       => $this->input('purchase_date', date('Y-m-d')),
                'total_shipping_cost' => (float) $this->input('total_shipping_cost', 0),
                'note'                => $this->input('note', ''),
            ];

            $items = $this->parseItemsFromPost();

            $purchaseId = $this->purchaseModel->createPurchase($header, $items);

            // อัปโหลดรูป (ถ้ามี) - ทำหลัง commit แล้ว เพื่อไม่ให้ไฟล์หลงเหลือหาก transaction fail
            $savedImages = $this->handleImageUploads($purchaseId);

            $msg = "บันทึกบิลซื้อสำเร็จ (รหัสบิล #{$purchaseId}) และแตกสต็อกรายเล่มเรียบร้อย";
            if (!empty($savedImages)) {
                $msg .= ' (แนบรูป ' . count($savedImages) . ' ไฟล์)';
            }
            $this->flash('success', $msg);
        } catch (Throwable $e) {
            $this->flash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        $this->redirect('purchase/create');
    }

    /**
     * แปลง $_POST['items'] ให้เป็นโครงสร้างที่ Model ใช้
     */
    private function parseItemsFromPost(): array
    {
        $raw = $_POST['items'] ?? [];
        $items = [];

        foreach ($raw as $rawItem) {
            $title = trim($rawItem['title'] ?? '');
            if ($title === '') continue;

            $ranges = [];
            foreach (($rawItem['ranges'] ?? []) as $r) {
                $vs = (int) ($r['start'] ?? 0);
                $ve = (int) ($r['end'] ?? 0);
                if ($vs <= 0 || $ve < $vs) continue;
                $ranges[] = ['volume_start' => $vs, 'volume_end' => $ve];
            }
            if (empty($ranges)) continue;

            $items[] = [
                'title'      => $title,
                'item_price' => (float) ($rawItem['price'] ?? 0),
                'ranges'     => $ranges,
            ];
        }

        return $items;
    }

    /**
     * จัดการไฟล์อัปโหลดจาก $_FILES['purchase_images']
     * - normalize array (รองรับทั้ง single และ multi file)
     * - validate mime / ext / size
     * - บันทึกลง public/uploads/purchases/{purchaseCode}_{uniqid}.{ext}
     * - เพิ่ม record ใน purchase_images
     *
     * @return array รายชื่อไฟล์ที่บันทึกสำเร็จ
     */
    private function handleImageUploads(int $purchaseId): array
    {
        $saved = [];
        if (empty($_FILES['purchase_images']) || empty($_FILES['purchase_images']['name'][0])) {
            return $saved;
        }

        // Normalize ให้เป็น array ของไฟล์ (รองรับทั้ง single และ multi input)
        $files = [];
        foreach ($_FILES['purchase_images']['name'] as $i => $name) {
            if ($_FILES['purchase_images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name'     => $name,
                'type'     => $_FILES['purchase_images']['type'][$i],
                'tmp_name' => $_FILES['purchase_images']['tmp_name'][$i],
                'error'    => $_FILES['purchase_images']['error'][$i],
                'size'     => $_FILES['purchase_images']['size'][$i],
            ];
        }

        if (empty($files)) return $saved;

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/purchases';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // ดึงรหัสบิลเพื่อใช้ตั้งชื่อไฟล์
        $purchase = $this->purchaseModel->getPurchaseWithItems($purchaseId);
        $codeSlug = $purchase ? preg_replace('/[^A-Za-z0-9\-]/', '', $purchase['purchase_code']) : "PO-{$purchaseId}";

        foreach ($files as $file) {
            try {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("อัปโหลดไฟล์ '{$file['name']}' ไม่สำเร็จ (error code {$file['error']})");
                }
                if ($file['size'] > self::MAX_BYTES) {
                    throw new RuntimeException("ไฟล์ '{$file['name']}' มีขนาดเกิน 5 MB");
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, self::ALLOWED_EXT, true)) {
                    throw new RuntimeException("นามสกุลไฟล์ '{$ext}' ไม่อนุญาต (อนุญาต: jpg, png, gif, webp)");
                }

                // ใช้ finfo ตรวจ mime จริง (กันคน rename .php เป็น .jpg)
                $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : ($file['type'] ?? '');
                if (!in_array($mime, self::ALLOWED_MIME, true)) {
                    throw new RuntimeException("ชนิดไฟล์ '{$file['name']}' (mime: {$mime}) ไม่ใช่รูปภาพที่อนุญาต");
                }

                $newName = $codeSlug . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $absPath = $uploadDir . '/' . $newName;

                if (!move_uploaded_file($file['tmp_name'], $absPath)) {
                    throw new RuntimeException("ย้ายไฟล์ '{$file['name']}' ไม่สำเร็จ");
                }

                $relPath = 'uploads/purchases/' . $newName;
                $this->purchaseModel->addPurchaseImage($purchaseId, $relPath, $file['name']);
                $saved[] = $newName;
            } catch (Throwable $e) {
                // เก็บ flash แจ้งเตือนไฟล์ที่ล้มเหลว แต่ไม่หยุดการทำงานส่วนอื่น
                $this->flash('warning', 'อัปโหลดรูปไม่สำเร็จ: ' . $e->getMessage());
            }
        }

        return $saved;
    }
}