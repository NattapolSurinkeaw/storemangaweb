<?php
/**
 * DashboardController
 * หน้าสรุปภาพรวม: ยอดซื้อ, ยอดขาย, ค่าธรรมเนียม, กำไรสุทธิ, สต็อกคงเหลือ
 * รองรับการ render view เปล่าทันที และโหลดข้อมูลผ่าน AJAX jQuery ตามหลัง
 */
class DashboardController extends Controller
{
    private PurchaseModel $purchaseModel;
    private SalesModel $salesModel;
    private InventoryModel $inventoryModel;
    private BundleModel $bundleModel;

    public function __construct()
    {
        $this->purchaseModel  = new PurchaseModel();
        $this->salesModel     = new SalesModel();
        $this->inventoryModel = new InventoryModel();
        $this->bundleModel    = new BundleModel();
    }

    /**
     * เรนเดอร์หน้า View เปล่าให้แสดงผลออกไปทันที
     */
    public function index(): void
    {
        if ($this->isAjaxRequest()) {
            $this->data();
            return;
        }

        $this->view('dashboard/index');
    }

    /**
     * Endpoint สำหรับดึงข้อมูลสรุปภาพรวมแบบ JSON (เรียกผ่าน AJAX)
     */
    public function data(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $salesTotals   = $this->salesModel->getTotals();
        $channelTotals = $this->salesModel->getTotalsByChannel();

        $data = [
            'success'           => true,
            'salesTotals'       => $salesTotals,
            'channelTotals'     => $channelTotals,
            'totalPurchase'     => (float) $this->purchaseModel->getTotalPurchaseAmount(),
            'availableBooks'    => (int) $this->inventoryModel->countByStatus('available'),
            'inBundleBooks'     => (int) $this->inventoryModel->countByStatus('in_bundle'),
            'soldBooks'         => (int) $this->inventoryModel->countByStatus('sold'),
            'availableBundles'  => (int) $this->bundleModel->countByStatus('available'),
            'soldBundles'       => (int) $this->bundleModel->countByStatus('sold'),
            'stockSummary'      => $this->inventoryModel->getStockSummaryByTitle(),
            'recentSales'       => array_slice($this->salesModel->getAllSales(), 0, 10),
        ];

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function isAjaxRequest(): bool
    {
        return (
            (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || (($_GET['ajax'] ?? '') === '1')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );
    }
}
