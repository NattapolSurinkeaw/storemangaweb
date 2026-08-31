<?php
/**
 * DashboardController
 * หน้าสรุปภาพรวม: ยอดซื้อ, ยอดขาย, ค่าธรรมเนียม, กำไรสุทธิ, สต็อกคงเหลือ
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

    public function index(): void
    {
        $salesTotals   = $this->salesModel->getTotals();
        $channelTotals = $this->salesModel->getTotalsByChannel();

        $data = [
            'salesTotals'       => $salesTotals,
            'channelTotals'     => $channelTotals,
            'totalPurchase'     => $this->purchaseModel->getTotalPurchaseAmount(),
            'availableBooks'    => $this->inventoryModel->countByStatus('available'),
            'inBundleBooks'     => $this->inventoryModel->countByStatus('in_bundle'),
            'soldBooks'         => $this->inventoryModel->countByStatus('sold'),
            'availableBundles'  => $this->bundleModel->countByStatus('available'),
            'soldBundles'       => $this->bundleModel->countByStatus('sold'),
            'stockSummary'      => $this->inventoryModel->getStockSummaryByTitle(),
            'recentSales'       => array_slice($this->salesModel->getAllSales(), 0, 10),
        ];

        $this->view('dashboard/index', $data);
    }
}
