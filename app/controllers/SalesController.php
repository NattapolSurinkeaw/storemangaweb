<?php
/**
 * SalesController
 * เลือกสินค้า (เล่มเดี่ยว/ชุด) -> คำนวณราคา/ค่าธรรมเนียม/กำไร -> บันทึกขาย
 */
class SalesController extends Controller
{
    private SalesModel $salesModel;
    private InventoryModel $inventoryModel;
    private BundleModel $bundleModel;

    public function __construct()
    {
        parent::__construct();
        $this->salesModel     = new SalesModel();
        $this->inventoryModel = new InventoryModel();
        $this->bundleModel    = new BundleModel();
    }

    public function index(): void
    {
        $this->create();
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        if ($this->isAjax()) {
            $this->data();
            return;
        }

        // โหมดแก้ไข: ?edit={id}
        $editId = (int) ($_GET['edit'] ?? 0);
        $editing = null;
        if ($editId > 0) {
            $editing = $this->salesModel->findSale($editId);
            if (!$editing) {
                $this->flash('warning', 'ไม่พบรายการขายที่ต้องการแก้ไข');
                $this->redirect('sales/create');
            }
        }

        $this->view('sales/create', [
            'shopeeFee'   => SHOPEE_FEE_PERCENT,
            'facebookFee' => FACEBOOK_FEE_PERCENT,
            'editing'     => $editing,
        ]);
    }

    public function data(): void
    {
        $this->json([
            'success'               => true,
            'availableBooks'        => $this->inventoryModel->getAvailableSingleBooks(),
            'availableBundles'      => $this->bundleModel->getAvailableBundles(),
            'availableBundleGroups' => $this->bundleModel->getAvailableBundleGroups(),
            'sales'                 => array_slice($this->salesModel->getAllSales(), 0, 20),
        ]);
    }

    /**
     * บันทึกการแก้ไข (POST เท่านั้น)
     * แก้ไขได้เฉพาะ: ราคาขาย, ค่าธรรมเนียม, ช่องทาง, วันที่
     * หากต้องการเปลี่ยนสินค้า ให้ลบแล้วสร้างใหม่
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->flash('danger', 'Method not allowed');
            $this->redirect('sales/create');
        }

        try {
            $channel    = $this->input('channel', 'shopee');
            $feePercent = (float) $this->input('fee_percent', SHOPEE_FEE_PERCENT);
            $feeAmount  = (float) $this->input('fee_amount', 0);
            $salePrice  = (float) $this->input('sale_price', 0);
            $saleDate   = $this->input('sale_date', date('Y-m-d'));

            if ($salePrice <= 0) {
                throw new InvalidArgumentException('กรุณาระบุราคาขาย');
            }

            $this->salesModel->updateSale($id, $channel, $feePercent, $feeAmount, $salePrice, $saleDate);
            $this->flash('success', "แก้ไขการขาย #{$id} สำเร็จ");
        } catch (Throwable $e) {
            $this->flash('danger', 'แก้ไขไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('sales/create?edit=' . $id);
            return;
        }

        $this->redirect('sales/create');
    }

    /**
     * ลบการขาย (POST เท่านั้น) + revert สถานะสินค้ากลับเป็น available
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
            $info = $this->salesModel->deleteSale($id);
            echo json_encode([
                'success' => true,
                'message' => "ลบการขาย #{$id} และคืนสถานะสินค้า ({$info['ref_type']} #{$info['ref_id']}) กลับเป็นพร้อมขาย",
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * AJAX endpoint: คำนวณราคา/กำไรแบบ real-time (ไม่บันทึก DB)
     * รับ ref_type, ref_id, channel, fee_percent, fee_amount, mode, value
     */
    public function calculate(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $refType = $this->input('ref_type');
            $refId   = (int) $this->input('ref_id');
            $cost    = $this->getCostOf($refType, $refId);

            $channel    = $this->input('channel', 'shopee');
            $feePercent = (float) $this->input('fee_percent', SHOPEE_FEE_PERCENT);
            $feeAmount  = (float) $this->input('fee_amount', 0);
            $mode       = $this->input('mode', 'by_target_price'); // or by_target_profit
            $value      = (float) $this->input('value', 0);

            $result = $this->salesModel->calculate($cost, $channel, $feePercent, $feeAmount, $mode, $value);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function store(): void
    {
        try {
            $refType = $this->input('ref_type');
            $refId   = (int) $this->input('ref_id');
            $cost    = $this->getCostOf($refType, $refId);
            $titleSnapshot = $this->getTitleOf($refType, $refId);

            $channel    = $this->input('channel', 'shopee');
            $feePercent = (float) $this->input('fee_percent', SHOPEE_FEE_PERCENT);
            $feeAmount  = (float) $this->input('fee_amount', 0);
            $salePrice  = (float) $this->input('sale_price', 0);
            $saleDate   = $this->input('sale_date', date('Y-m-d'));

            if ($salePrice <= 0) {
                throw new InvalidArgumentException('กรุณาระบุราคาขาย');
            }

            $saleId = $this->salesModel->recordSale($refType, $refId, $titleSnapshot, $cost, $channel, $feePercent, $feeAmount, $salePrice, $saleDate);
            $this->flash('success', "บันทึกการขายสำเร็จ (เลขที่ #{$saleId})");
        } catch (Throwable $e) {
            $this->flash('danger', 'บันทึกการขายไม่สำเร็จ: ' . $e->getMessage());
        }

        $this->redirect('sales/create');
    }

    private function getCostOf(string $refType, int $refId): float
    {
        if ($refType === 'book') {
            $book = $this->inventoryModel->getBookById($refId);
            if (!$book) throw new RuntimeException('ไม่พบเล่มที่เลือก');
            return (float) $book['unit_cost'];
        }
        if ($refType === 'bundle') {
            $bundle = $this->bundleModel->getBundleById($refId);
            if (!$bundle) throw new RuntimeException('ไม่พบชุดที่เลือก');
            return (float) $bundle['total_cost'];
        }
        if ($refType === 'bundle_group') {
            $grp = $this->bundleModel->getBundleGroupById($refId);
            if (!$grp) throw new RuntimeException('ไม่พบกลุ่มที่เลือก');
            return (float) $grp['total_cost'];
        }
        throw new InvalidArgumentException('ประเภทสินค้าไม่ถูกต้อง');
    }

    private function getTitleOf(string $refType, int $refId): string
    {
        if ($refType === 'book') {
            $book = $this->inventoryModel->getBookById($refId);
            return $book ? "{$book['title']} เล่ม {$book['volume']}" : 'ไม่ทราบชื่อ';
        }
        if ($refType === 'bundle') {
            $bundle = $this->bundleModel->getBundleById($refId);
            return $bundle ? "{$bundle['title']} ชุดเล่ม {$bundle['volume_start']}-{$bundle['volume_end']}" : 'ไม่ทราบชื่อ';
        }
        if ($refType === 'bundle_group') {
            $bundles = $this->bundleModel->getBundlesByGroupId($refId);
            if (!empty($bundles)) {
                $titles = array_map(function ($b) {
                    $vol = ((int) $b['volume_start'] === (int) $b['volume_end'])
                        ? "เล่ม {$b['volume_start']}"
                        : "ชุดเล่ม {$b['volume_start']}-{$b['volume_end']}";
                    return "{$b['title']} {$vol}";
                }, $bundles);
                return implode(', ', $titles);
            }
            $grp = $this->bundleModel->getBundleGroupById($refId);
            return ($grp && !empty($grp['label'])) ? $grp['label'] : 'ไม่ทราบชื่อ';
        }
        return 'ไม่ทราบชื่อ';
    }
}
