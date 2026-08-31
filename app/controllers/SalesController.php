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

        $this->view('sales/create', [
            'availableBooks'      => $this->inventoryModel->getAvailableSingleBooks(),
            'availableBundles'    => $this->bundleModel->getAvailableBundles(),
            'availableBundleGroups' => $this->bundleModel->getAvailableBundleGroups(),
            'sales'               => array_slice($this->salesModel->getAllSales(), 0, 20),
            'shopeeFee'           => SHOPEE_FEE_PERCENT,
            'facebookFee'         => FACEBOOK_FEE_PERCENT,
        ]);
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
            $grp = $this->bundleModel->getBundleGroupById($refId);
            return $grp ? "[กลุ่มรวม] " . ($grp['label'] ?? 'กลุ่มขายรวม') . " ({$grp['bundle_count']} เรื่อง)" : 'ไม่ทราบชื่อ';
        }
        return 'ไม่ทราบชื่อ';
    }
}
