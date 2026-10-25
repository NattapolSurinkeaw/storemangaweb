<?php
/**
 * StockController
 * ดูสต็อกคงเหลือ + จัดชุดขาย (Bundling)
 */
class StockController extends Controller
{
    private InventoryModel $inventoryModel;
    private BundleModel $bundleModel;

    private const COVER_ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const COVER_ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const COVER_MAX_BYTES    = 5 * 1024 * 1024; // 5 MB

    public function __construct()
    {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
        $this->bundleModel    = new BundleModel();
    }

    public function index(): void
    {
        $title = $this->input('title', '');

        if ($this->isAjax()) {
            $this->data();
            return;
        }

        $this->view('stock/index', [
            'selectedTitle' => $title,
        ]);
    }

    public function data(): void
    {
        $title = $this->input('title', '');

        $this->json([
            'success'       => true,
            'selectedTitle' => $title,
            'stockSummary'  => $this->inventoryModel->getStockSummaryByTitle(),
            'books'         => $title !== '' ? $this->inventoryModel->getBooksByTitle($title, true) : [],
            'purchases'     => $title !== '' ? $this->inventoryModel->getPurchasesByTitle($title, true) : [],
        ]);
    }

    public function bundle(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';
            if ($action === 'combine') {
                $this->combineBundles();
                return;
            }
            $this->storeBundle();
            return;
        }

        if ($this->isAjax()) {
            $this->bundleData();
            return;
        }

        $this->view('stock/bundle');
    }

    public function bundleData(): void
    {
        $this->requireAdmin();
        $this->json([
            'success'      => true,
            'titles'       => $this->inventoryModel->getDistinctTitlesWithStock(),
            'bundles'      => $this->bundleModel->getAvailableBundles(),
            'bundleGroups' => $this->bundleModel->getAvailableBundleGroups(),
        ]);
    }

    private function storeBundle(): void
    {
        try {
            $label = trim((string) ($this->input('group_label', '') ?? ''));
            $items = $this->collectItemsFromPost();

            if (count($items) === 0) {
                throw new InvalidArgumentException('กรุณาเพิ่มเรื่องอย่างน้อย 1 เรื่อง');
            }

            $coverImage = $this->handleCoverImageUpload('bundle_cover');

            if (count($items) === 1) {
                $it = $items[0];
                $bundleId = $this->bundleModel->createBundle($it['title'], $it['volume_start'], $it['volume_end'], null, $coverImage);
                $this->flash('success', "จัดชุด '{$it['title']}' เล่ม {$it['volume_start']}-{$it['volume_end']} สำเร็จ (Bundle #{$bundleId})");
                $this->redirect('stock/bundle');
                return;
            }

            $groupId = $this->bundleModel->createBundleGroup($items, $label !== '' ? $label : null, $coverImage);
            $this->flash('success', "จัดชุดรวม " . count($items) . " เรื่อง สำเร็จ (Group #{$groupId})");
        } catch (Throwable $e) {
            $this->flash('danger', 'จัดชุดไม่สำเร็จ: ' . $e->getMessage());
        }

        $this->redirect('stock/bundle');
    }

    /**
     * รวมข้อมูลจาก POST รองรับทั้ง single-row (เดิม) และ multi-row (items[])
     * @return array<int,array{title:string,volume_start:int,volume_end:int}>
     */
    private function collectItemsFromPost(): array
    {
        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $row) {
                $title = trim((string) ($row['title'] ?? ''));
                $vs    = (int) ($row['volume_start'] ?? 0);
                $ve    = (int) ($row['volume_end'] ?? 0);
                if ($title === '' || $vs <= 0 || $ve < $vs) continue;
                $items[] = ['title' => $title, 'volume_start' => $vs, 'volume_end' => $ve];
            }
        }
        if (count($items) === 0) {
            $title = trim((string) $this->input('title', ''));
            $vs    = (int) $this->input('volume_start', 0);
            $ve    = (int) $this->input('volume_end', 0);
            if ($title !== '' && $vs > 0 && $ve >= $vs) {
                $items[] = ['title' => $title, 'volume_start' => $vs, 'volume_end' => $ve];
            }
        }
        return $items;
    }

private function combineBundles(): void
    {
        try {
            $ids = $_POST['bundle_ids'] ?? [];
            if (!is_array($ids) || count($ids) < 2) {
                throw new InvalidArgumentException('กรุณาเลือกอย่างน้อย 2 ชุด');
            }
            $label = trim((string) ($this->input('group_label', '') ?? ''));
            $coverImage = $this->handleCoverImageUpload('combine_cover');
            $groupId = $this->bundleModel->combineBundlesToGroup(array_map('intval', $ids), $label !== '' ? $label : null, $coverImage);
            $this->flash('success', "รวมชุดเข้าเป็นกลุ่มเดียวสำเร็จ (Group #{$groupId})");
        } catch (Throwable $e) {
            $this->flash('danger', 'รวมชุดไม่สำเร็จ: ' . $e->getMessage());
        }

        $this->redirect('stock/bundle');
    }

    /**
     * อัปโหลดรูปปกชุดขาย (1 รูป) — บันทึกลง public/uploads/bundles/
     * @return string|null path relative (เช่น uploads/bundles/xxx.jpg) หรือ null ถ้าไม่มีไฟล์
     */
    private function handleCoverImageUpload(string $fieldName): ?string
    {
        if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("อัปโหลดรูปปกไม่สำเร็จ (error code {$file['error']})");
        }
        if ($file['size'] > self::COVER_MAX_BYTES) {
            throw new RuntimeException('ไฟล์รูปปกมีขนาดเกิน 5 MB');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::COVER_ALLOWED_EXT, true)) {
            throw new RuntimeException("นามสกุลไฟล์ '{$ext}' ไม่อนุญาต (อนุญาต: jpg, png, gif, webp)");
        }

        $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : ($file['type'] ?? '');
        if (!in_array($mime, self::COVER_ALLOWED_MIME, true)) {
            throw new RuntimeException("ชนิดไฟล์ไม่ใช่รูปภาพที่อนุญาต");
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/bundles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newName = 'bundle_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $absPath = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            throw new RuntimeException('ย้ายไฟล์รูปปกไม่สำเร็จ');
        }

        return 'uploads/bundles/' . $newName;
    }

    public function dissolveBundle(int $id = 0): void
    {
        $this->requireAdmin();
        try {
            $this->bundleModel->dissolveBundle($id);
            $this->flash('success', "ยกเลิกชุด #{$id} และคืนเล่มเข้าสต็อกแล้ว");
        } catch (Throwable $e) {
            $this->flash('danger', 'ยกเลิกไม่สำเร็จ: ' . $e->getMessage());
        }

        $this->redirect('stock/bundle');
    }

    public function dissolveBundleGroup(int $id = 0): void
    {
        $this->requireAdmin();
        try {
            $this->bundleModel->dissolveBundleGroup($id);
            $this->flash('success', "ยกเลิกกลุ่มขาย #{$id} และคืนเล่มเข้าสต็อกแล้ว");
        } catch (Throwable $e) {
            $this->flash('danger', 'ยกเลิกกลุ่มไม่สำเร็จ: ' . $e->getMessage());
        }

        $this->redirect('stock/bundle');
    }

    /**
     * AJAX: คืน array ของ volume ที่ available ของเรื่องที่ระบุ (volume => qty)
     */
    public function getAvailableVolumes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $title = trim($this->input('title', ''));
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุชื่อเรื่อง']);
                return;
            }
            $map = $this->inventoryModel->getAvailableVolumesForTitle($title);
            echo json_encode(['success' => true, 'data' => $map]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: จำลองการจัดชุด (FIFO preview) เพื่อแสดงราคารวม + เล่มที่จะหยิบก่อนกดจัดชุดจริง
     */
    public function previewBundle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $title      = trim($this->input('title', ''));
            $volumeStart = (int) $this->input('volume_start', 0);
            $volumeEnd   = (int) $this->input('volume_end', 0);

            if ($title === '' || $volumeStart <= 0 || $volumeEnd < $volumeStart) {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
                return;
            }
            $preview = $this->inventoryModel->previewBundle($title, $volumeStart, $volumeEnd);
            echo json_encode(['success' => true, 'data' => $preview]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** AJAX: รายละเอียดของ bundle เดี่ยว (ใช้ใน Modal) */
    public function bundleDetail(int $id = 0): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $detail = $this->bundleModel->getBundleDetail($id);
            if (!$detail['bundle']) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบ bundle']);
                return;
            }
            echo json_encode(['success' => true, 'data' => $detail]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** AJAX: รายละเอียดของ bundle_group (ใช้ใน Modal) */
    public function bundleGroupDetail(int $id = 0): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $detail = $this->bundleModel->getBundleGroupDetail($id);
            if (!$detail['group']) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบกลุ่ม']);
                return;
            }
            echo json_encode(['success' => true, 'data' => $detail]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** AJAX: รายละเอียดรายเล่มและบิลซื้อของเรื่องที่ระบุ (ใช้ใน Modal หน้าสต็อก) */
    public function titleDetail(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $title = trim($this->input('title', ''));
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'ไม่พบชื่อเรื่อง']);
                return;
            }
            $books = $this->inventoryModel->getBooksByTitle($title, true);
            $purchases = $this->inventoryModel->getPurchasesByTitle($title, true);
            echo json_encode([
                'success' => true,
                'data' => [
                    'title' => $title,
                    'books' => $books,
                    'purchases' => $purchases,
                ]
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
