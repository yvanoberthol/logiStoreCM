<?php


namespace App\Service;


use App\Entity\Product;
use App\Entity\ProductAdjust;
use App\Entity\ProductAdjustStock;
use App\Entity\ProductSale;
use App\Entity\ProductStock;
use App\Entity\ProductStockReturn;
use App\Entity\ProductStockSale;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\LineReserveOutRepository;
use App\Repository\ProductAdjustRepository;
use App\Repository\ProductAdjustStockRepository;
use App\Repository\ProductPriceRepository;
use App\Repository\ProductRepository;
use App\Repository\LossRepository;
use App\Repository\ProductSaleReturnRepository;
use App\Repository\ProductStockRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductStockReserveRepository;
use App\Repository\ProductStockReturnRepository;
use App\Repository\ProductStockSaleRepository;
use App\Util\GlobalConstant;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProductService
{
    /**
     * @var ProductStockRepository
     */
    private $productStockRepository;
    /**
     * @var ProductSaleRepository
     */
    private $productSaleRepository;
    /**
     * @var LossRepository
     */
    private $lossRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var ProductPriceRepository
     */
    private $productPriceRepository;
    /**
     * @var ProductStockSaleRepository
     */
    private $productStockSaleRepository;
    /**
     * @var Setting
     */
    private $setting;
    /**
     * @var ProductSaleReturnRepository
     */
    private $productSaleReturnRepository;
    /**
     * @var ProductStockReturnRepository
     */
    private $productStockReturnRepository;
    /**
     * @var ProductAdjustRepository
     */
    private $productAdjustRepository;
    /**
     * @var ProductAdjustStockRepository
     */
    private $productAdjustStockRepository;


    /**
     * ProductService constructor.
     * @param ProductStockRepository $productStockRepository
     * @param ProductPriceRepository $productPriceRepository
     * @param ProductSaleRepository $productSaleRepository
     * @param ProductAdjustRepository $productAdjustRepository
     * @param ProductAdjustStockRepository $productAdjustStockRepository
     * @param ProductStockSaleRepository $productStockSaleRepository
     * @param ProductSaleReturnRepository $productSaleReturnRepository
     * @param ProductStockReturnRepository $productStockReturnRepository
     * @param ProductRepository $productRepository
     * @param LossRepository $lossRepository
     * @param RequestStack $requestStack
     */
    public function __construct(ProductStockRepository $productStockRepository,
                                ProductPriceRepository $productPriceRepository,
                                ProductSaleRepository $productSaleRepository,
                                ProductAdjustRepository $productAdjustRepository,
                                ProductAdjustStockRepository $productAdjustStockRepository,
                                ProductStockSaleRepository $productStockSaleRepository,
                                ProductSaleReturnRepository $productSaleReturnRepository,
                                ProductStockReturnRepository $productStockReturnRepository,
                                ProductRepository $productRepository,
                                LossRepository $lossRepository,
                                RequestStack $requestStack)
    {
        $this->productStockRepository = $productStockRepository;
        $this->productSaleRepository = $productSaleRepository;
        $this->lossRepository = $lossRepository;
        $this->productRepository = $productRepository;
        $this->session = $requestStack->getSession();
        $this->setting = $this->session->get('setting');
        $this->productPriceRepository = $productPriceRepository;
        $this->productStockSaleRepository = $productStockSaleRepository;
        $this->productSaleReturnRepository = $productSaleReturnRepository;
        $this->productStockReturnRepository = $productStockReturnRepository;
        $this->productAdjustRepository = $productAdjustRepository;
        $this->productAdjustStockRepository = $productAdjustStockRepository;
    }

    /**
     * @param Product $product
     * @return Product
     * @throws Exception
     */
    public function countStock(Product $product): Product {
        $productStockCount = (int) $this->productStockRepository
            ->countByProduct($product);
        $productSaleCount = (int) $this->productSaleRepository
            ->countByProduct($product);

        $productAdjustCount = (int) $this->productAdjustRepository
            ->countByProduct($product);

        $productSaleReturnCount = (int) $this->productSaleReturnRepository
            ->countByProduct($product);

        $productStockReturnCount = (int) $this->productStockReturnRepository
            ->countByProduct($product);

        $lossCount = (int) $this->lossRepository
            ->countByProduct($product);

        $stock = $productStockCount + $productSaleReturnCount - $productStockReturnCount
            - $productSaleCount - $lossCount - $productAdjustCount;

        if ($productStockCount > 0){
            $product->setDeletable(false);
        }

        $product->setStock($stock);

        $product->setNew($this->isNew($product->getAddDate()));

        return $product;
    }


    /**
     * @param ProductStock $productStock
     * @return ProductStock
     */
    public function countQtyRemaining(ProductStock $productStock): ProductStock {
        $lossCount = (int) $this->lossRepository
            ->countByProductStock($productStock);

        $productStockSaleCount = (int) $this->productStockSaleRepository
            ->countByProductStock($productStock);
        $productAdjustStockCount = (int) $this->productAdjustStockRepository
            ->countByProductStock($productStock);

        $productSaleReturnCount = (int) $this->productSaleReturnRepository
            ->countByProductStock($productStock);

        $productStockReturnCount= (int) $this->productStockReturnRepository
            ->countByProductStock($productStock);

        $qtyRemaining = $productStock->getQty() + $productSaleReturnCount
            - $productStockReturnCount - $productStockSaleCount - $lossCount - $productAdjustStockCount;

        $productStock->setQtyRemaining($qtyRemaining);
        $productStock->setQtyLost($lossCount);
        $productStock->setQtySold($productStockSaleCount);
        $productStock->setQtySoldReturn($productSaleReturnCount);
        $productStock->setQtyStockReturn($productStockReturnCount);

        return $productStock;
    }

    /**
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function getProductStockNearExpirationDate(int $days = 10): array
    {
        $productStocks = $this->productStockRepository
            ->getAllNotWithDraw(abs($days));

        return $this->getProductStockWithStock($productStocks);
    }

    /**
     * @param bool $withdraw
     * @return array
     */
    public function getProductStockOutdated(bool $withdraw): array
    {
        $productStocks = $this->productStockRepository
            ->getOutdated($withdraw);

        return $this->getProductStockWithStock($productStocks);
    }

    public function getProductStockDispoByProduct(Product $product,
                                                  $initialStock=10): array
    {
        $productStocks = array_filter($this->productStockRepository
            ->getByProduct($product,$initialStock),
            function (ProductStock $productStock){
                return $this->countQtyRemaining($productStock)
                        ->getQtyRemaining() > 0;
            });

        sort($productStocks);

       return $productStocks;

    }

    public function getProductSaleDispoByProduct(Product $product,
                                                  $initialStock=10): array
    {
        $productSales = $this->productSaleRepository
            ->getByProduct($product,$initialStock);

        sort($productSales);

        return $productSales;

    }

    public function getProductSaleDispoByProductStock(ProductStock $productStock,
                                                 $initialStock=10): array
    {
        $productSales = $this->productSaleRepository
            ->getByproductStock($productStock,$initialStock);

        sort($productSales);

        return $productSales;

    }

    public function getProductStockWithStock($productStocks): array
    {
        return array_filter($productStocks,
            function (ProductStock $productStock){
                return $this->countQtyRemaining($productStock)
                        ->getQtyRemaining() > 0;
            });

    }

    private function getInitialStock($stockCounts, $saleCounts, $lossCounts,
                                     $saleReturnCounts,$stockReturnCounts, Product $product,$adjustCounts=null): int{

        $initialStock = 0;

        foreach ($stockCounts as $stockCount){
            if ((int) $stockCount['id'] === $product->getId()){
                $initialStock += (int) $stockCount['qty'];
                break;
            }
        }

        foreach ($stockReturnCounts as $stockReturnCount){
            if ((int) $stockReturnCount['id'] === $product->getId()){
                $initialStock -= (int) $stockReturnCount['qty'];
                break;
            }
        }

        foreach ($saleCounts as $saleCount){
            if ((int) $saleCount['id'] === $product->getId()){
                $initialStock -= (int) $saleCount['qty'];
                break;
            }
        }

        foreach ($adjustCounts as $adjustCount){
            if ((int) $adjustCount['id'] === $product->getId()){
                $initialStock -= (int) $adjustCount['qty'];
                break;
            }
        }

        foreach ($saleReturnCounts as $saleReturnCount){
            if ((int) $saleReturnCount['id'] === $product->getId()){
                $initialStock += (int) $saleReturnCount['qty'];
                break;
            }
        }

        foreach ($lossCounts as $lossCount){
            if ((int) $lossCount['id'] === $product->getId()){
                $initialStock -= (int) $lossCount['qty'];
                break;
            }
        }


        return ($initialStock < 0)?0:$initialStock;
    }

    public function getInventory($start, $end, $byProduct=false,$employee = null,$enable=true): array {


        if ($employee !== null && $this->setting->getWithUserCategory()){
            $products = [];
            foreach ($employee->getCategories() as $category){
                $productSelected = array_filter($category->getProducts()->toArray(),
                    static function(Product $product) use($byProduct,$enable){
                        return (!$byProduct)?true:($product->getByProduct() ===$byProduct)
                            && ($product->getEnabled() === $enable);
                    });

                foreach ($productSelected as $product){
                    $products[] = $product;
                }
            }
        }else{
            $products = $this->productRepository->findBy(['enabled'  => $enable]);
        }

        $stockCounts = $this->productStockRepository
            ->nbByProductAndBeforePeriodDate($start,null);

        $adjustCounts = $this->productAdjustRepository
            ->nbByProductAndBeforePeriodDate($start,null);

        $stockReturnCounts = $this->productStockReturnRepository
            ->nbByProductAndBeforePeriodDate($start,null);

        $saleCounts = $this->productSaleRepository
            ->nbByProductAndBeforePeriodDate($start,null);

        $saleReturnCounts = $this->productSaleReturnRepository
            ->nbByProductAndBeforePeriodDate($start,null,false);

        $lossCounts = $this->lossRepository
            ->nbByProductAndBeforePeriodDate($start,null);

        $qtyOrdered = $this->productStockRepository
            ->nbByProductAndPeriodDate($start,$end,null);
        $qtyStockReturns = $this->productStockReturnRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $qtySolds = $this->productSaleRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $qtyAdjusts = $this->productAdjustRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $qtySoldAdjusteds = $this->productSaleRepository
            ->nbAdjustedByproductAndPeriodDate($start,$end,null);

        $qtySaleReturns = $this->productSaleReturnRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $qtySaleReturnStockables = $this->productSaleReturnRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $qtyLosses = $this->lossRepository
            ->nbByProductAndPeriodDate($start,$end,null);

        $inventory = [];
        foreach ($products as $product){
            $initialStock = $this->getInitialStock($stockCounts,$saleCounts,$lossCounts,
                $saleReturnCounts,$stockReturnCounts,$product,$adjustCounts);
            $inventory[] = $this->getInventoryLine($initialStock, $qtyOrdered, $qtySolds,
                $qtyLosses,$qtyStockReturns,$qtySaleReturns,$qtySaleReturnStockables, $product,
                $qtySoldAdjusteds,$qtyAdjusts);
        }

        return $inventory;
    }

    private function getInventoryLine($initialStock, $qtyOrdered, $qtySolds,
                                      $qtyLosses,$qtyStockReturns,
                                      $qtySaleReturns,$qtySaleReturnStockables, Product $product,
                                      $qtySoldAdjusteds = null,$qtyAdjusts=null): array {

        $nbOrdered = 0;
        foreach ($qtyOrdered as $qtyOrder){
            if ((int) $qtyOrder['id'] === $product->getId()){
                $nbOrdered = (int) $qtyOrder['qty'];
                break;
            }
        }

        $nbStockReturn = 0;
        foreach ($qtyStockReturns as $qtyStockReturn){
            if ((int) $qtyStockReturn['id'] === $product->getId()){
                $nbStockReturn = (int) $qtyStockReturn['qty'];
                break;
            }
        }

        $nbSold = 0;
        $amountSold = 0;
        $unitPrice = 0.0;
        foreach ($qtySolds as $qtySold){
            if ((int) $qtySold['id'] === $product->getId()){
                $nbSold = (int) $qtySold['qty'];
                $amountSold = (float) $qtySold['subtotal'];
                $unitPrice = (float) $qtySold['unitPrice'];
                break;
            }
        }

        $unitPrice = ($unitPrice === 0.0)?$product->getSellPrice():$unitPrice;

        $nbAdjusted= 0;
        $adjusted= false;
        foreach ($qtySoldAdjusteds as $qtySoldAdjusted){
            if ((int) $qtySoldAdjusted['id'] === $product->getId()){
                $nbAdjusted = (int) $qtySoldAdjusted['qty'];
                $adjusted = ($nbAdjusted > 0);
                break;
            }
        }

        $nbAdjust= 0;
        foreach ($qtyAdjusts as $qtyAdjust){
            if ((int) $qtyAdjust['id'] === $product->getId()){
                $nbAdjust = (int) $qtyAdjust['qty'];
                break;
            }
        }

        $nbSaleReturn = 0;
        foreach ($qtySaleReturns as $qtySaleReturn){
            if ((int) $qtySaleReturn['id'] === $product->getId()){
                $nbSaleReturn = (int) $qtySaleReturn['qty'];
                break;
            }
        }

        $nbSaleReturnStockable = 0;
        foreach ($qtySaleReturnStockables as $qtySaleReturnStockable){
            if ((int) $qtySaleReturnStockable['id'] === $product->getId()){
                $nbSaleReturnStockable = (int) $qtySaleReturnStockable['qty'];
                break;
            }
        }

        $nbLoss= 0;
        foreach ($qtyLosses as $qtyLoss){
            if ((int) $qtyLoss['id'] === $product->getId()){
                $nbLoss = (int) $qtyLoss['qty'];
                break;
            }
        }


        $stockRemaining = ($initialStock+$nbOrdered)
            -$nbSold - $nbAdjust -$nbLoss -$nbStockReturn -$nbSaleReturn + $nbSaleReturnStockable;

        $stockRemainingEdit = $stockRemaining + $nbAdjusted;

        return [
            'id' => $product->getId(),
            'product' => $product->getName(),
            'packagingQty' => $product->getPackagingQty(),
            'unitPrice' => $unitPrice,
            'amountSold' => $amountSold,
            'initialStock' => $initialStock,
            'qtyOrdered' => $nbOrdered,
            'qtyAdjust' => $nbAdjust,
            'qtyAdjusted' => $nbAdjusted,
            'adjusted' => $adjusted,
            'qtyStockReturn' => $nbStockReturn,
            'qtySold' => $nbSold,
            'qtySaleReturn' => $nbSaleReturn,
            'qtyLoss' => $nbLoss,
            'stockRemaining' => $stockRemaining,
            'stockRemainingEdit' => $stockRemainingEdit
        ];
    }


    /**
     * @param Product[] $products
     * @return array
     * @throws Exception
     */
    public function countStocks($products): array {

        $productStockCounts = $this->productStockRepository->countByProducts();
        $productSaleCounts = $this->productSaleRepository->countByProducts();
        $productAdjustCounts = $this->productAdjustRepository->countByProducts();
        $productSaleReturnCounts = $this->productSaleReturnRepository->countByProducts();
        $lossCounts = $this->lossRepository->countByProducts();

        $productStockReturnCounts = $this->productStockReturnRepository->countByProducts();

        $productTabs = [];
        foreach ($products as $product){
            $stock = 0;
            foreach ($productStockCounts as $stockCount){
                if ((int) $stockCount['id'] === $product->getId()){
                    $stock += (int) $stockCount['qty'];
                    break;
                }
            }

            if ($stock > 0 && count($productStockCounts) > 0 ){
                $product->setDeletable(false);
            }

            foreach ($productSaleReturnCounts as $saleReturnCount){
                if ((int) $saleReturnCount['id'] === $product->getId()){
                    $stock += (int) $saleReturnCount['qty'];
                    break;
                }
            }

            foreach ($productStockReturnCounts as $stockReturnCount){
                if ((int) $stockReturnCount['id'] === $product->getId()){
                    $stock -= (int) $stockReturnCount['qty'];
                    break;
                }
            }

            foreach ($productSaleCounts as $saleCount){
                if ((int) $saleCount['id'] === $product->getId()){
                    $stock -= (int) $saleCount['qty'];
                    break;
                }
            }

            foreach ($productAdjustCounts as $adjustCount){
                if ((int) $adjustCount['id'] === $product->getId()){
                    $stock -= (int) $adjustCount['qty'];
                    break;
                }
            }

            foreach ($lossCounts as $lossCount){
                if ((int) $lossCount['id'] === $product->getId()){
                    $stock -= (int) $lossCount['qty'];
                    break;
                }

            }
            $stock = ($stock < 0)?0:$stock;
            $product->setStock($stock);

            $product->setNew($this->isNew($product->getAddDate()));

            $productTabs[] = $product;
        }


        return $productTabs;
    }

    /**
     * @param Product[] $products
     * @return array
     * @throws Exception
     */
    public function getProductByStockAlert($products): ?array {

        $productFilter = array_filter($this->countStocks($products), static function(Product $product){
            return $product->getStock() <= $product->getStockAlert();
        });
        sort($productFilter);
        return $productFilter;
    }

    /**
     * @param Product[] $products
     * @return array
     * @throws Exception
     */
    public function getProductByStockNotFinish($products): ?array {

        $productFilter =  array_filter($this->countStocks($products),
            static function(Product $product){
            return $product->getStock() > 0;
        });

        sort($productFilter);
        return $productFilter;
    }

    /**
     * @param Product[] $products
     * @return array
     * @throws Exception
     */
    public function getProductByOutOfStock($products): ?array {

        $productFilter =  array_filter($this->countStocks($products), static function(Product $product){
            return $product->getStock() === 0;
        });

        sort($productFilter);
        return $productFilter;
    }

    /**
     * @param Product $product
     * @param int $qty
     * @return float
     * @throws Exception
     */
    public function getProfitByProduct(Product $product, int $qty): float {

        $productStocks =
            $this->getProductStockDispoByProduct($product);


        $sellPrice = $product->getSellPrice();
        if ($this->setting->getWithBatchPrice() && count($product->getProductPrices()) > 0){
            $productPrice = $this->productPriceRepository
                ->findOneByProductAndQty($product,$qty);
            if ($productPrice !== null){
                $sellPrice = $productPrice->getUnitPrice();
            }
        }

        $productDiscount = 0;
        if ($this->setting->getProductWithDiscount()){
            $productDiscount = $product->getDiscount();
        }

        $total = 0;
        $qtyToWithDrawInNextStock = $qty;
        $qtyWithdraw=0;
        $nbStocks = 10;
        for ($i=0;$i<$nbStocks;$i++){
            $sumQuantityStock = $this->countQtyRemaining($productStocks[$i])->getQtyRemaining();

            $profit = $sellPrice - $productStocks[$i]->getUnitPrice()
                - $productDiscount;

            $total += $profit;

            $qtyWithdraw += ($qtyToWithDrawInNextStock >= $sumQuantityStock)
                ?$sumQuantityStock:$qtyToWithDrawInNextStock;

            $qtyToWithDrawInNextStock = $qty - $qtyWithdraw;


            if ($qtyToWithDrawInNextStock === 0){
                break;
            }
        }


        return ($total * $qty);
    }


    /**
     * @param Product $product
     * @param array $productStocks
     * @return int
     */
    public function getProfitGuiByProduct(Product $product, array $productStocks=[]): int{

        $totalItem = 0;

        $qty = array_sum(array_map(static function($line){return $line[1];},$productStocks));

        $sellPrice = $product->getSellPrice();
        if ($this->setting->getWithBatchPrice() && count($product->getProductPrices()) > 0){
            $productPrice = $this->productPriceRepository
                ->findOneByProductAndQty($product,$qty);
            if ($productPrice !== null){
                $sellPrice = $productPrice->getUnitPrice();
            }
        }

        foreach ($productStocks as $productStock){
            $totalItem += ($sellPrice - $productStock[0]->getUnitPrice()) * $productStock[1];
        }

        return $totalItem;
    }

    /**
     * @param $unitPrice
     * @param array $productStocks
     * @return int
     */
    public function getProfitWholeSaleByProduct($unitPrice, array $productStocks=[]): int{

        $totalItem = 0;
        foreach ($productStocks as $productStock){
            $totalItem += ((float) $unitPrice - $productStock[0]->getUnitPrice()) * $productStock[1];
        }

        return $totalItem;
    }

    /**
     * @param Product $product
     * @param int $qty
     * @return array
     * @throws Exception
     */
    public function getProductStocks(Product $product, int $qty): array {

        $nbStocks = 10;
        $productStocks =
            $this->getProductStockDispoByProduct($product);

        $productStockResult = [];
        //if the qty before the last stock is small than quantity asked
        // i take the qty before the last stock and a few in the last stock
        $qtyToWithDrawInNextStock = $qty;
        $qtyWithdraw=0;

        for ($i=0;$i<$nbStocks;$i++){
            $sumQuantityStock = $this->countQtyRemaining($productStocks[$i])->getQtyRemaining();

            $productStockSale = new ProductStockSale();
            $productStockSale->setProductStock($productStocks[$i]);

            if ($qtyToWithDrawInNextStock >= $sumQuantityStock){
                $productStockSale->setQty($sumQuantityStock);
                $qtyWithdraw += $sumQuantityStock;
            }else{
                $productStockSale->setQty($qtyToWithDrawInNextStock);
                $qtyWithdraw += $qtyToWithDrawInNextStock;
            }

            $productStockResult[] = $productStockSale;

            $qtyToWithDrawInNextStock = $qty - $qtyWithdraw;

            if ($qtyToWithDrawInNextStock === 0){
                break;
            }
        }


        return $productStockResult;
    }

    /**
     * @param Product $product
     * @param int $qty
     * @return array
     * @throws Exception
     */
    public function getProductStocksByAdjustment(Product $product, int $qty): array {

        $nbStocks = 10;
        $productStocks =
            $this->getProductStockDispoByProduct($product);

        $productStockResult = [];
        //if the qty before the last stock is small than quantity asked
        // i take the qty before the last stock and a few in the last stock
        $qtyToWithDrawInNextStock = $qty;
        $qtyWithdraw=0;

        for ($i=0;$i<$nbStocks;$i++){
            $sumQuantityStock = $this->countQtyRemaining($productStocks[$i])->getQtyRemaining();

            $productAdjustStock = new ProductAdjustStock();
            $productAdjustStock->setProductStock($productStocks[$i]);

            if ($qtyToWithDrawInNextStock >= $sumQuantityStock){
                $productAdjustStock->setQty($sumQuantityStock);
                $productAdjustStock->setUnitPrice($productStocks[$i]->getUnitPrice());
                $qtyWithdraw += $sumQuantityStock;
            }else{
                $productAdjustStock->setQty($qtyToWithDrawInNextStock);
                $productAdjustStock->setUnitPrice($productStocks[$i]->getUnitPrice());
                $qtyWithdraw += $qtyToWithDrawInNextStock;
            }

            $productStockResult[] = $productAdjustStock;

            $qtyToWithDrawInNextStock = $qty - $qtyWithdraw;

            if ($qtyToWithDrawInNextStock === 0){
                break;
            }
        }


        return $productStockResult;
    }

    /**
     * @param Product $product
     * @param int $qty
     * @param DateTimeInterface $date
     * @param User $user
     * @return array
     */
    public function getProductStockReturns(Product $product, int $qty,
                                           DateTimeInterface $date, User $user): array {

        $nbStocks = 10;
        $productStocks =
            $this->getProductStockDispoByProduct($product);

        $productStockResult = [];
        //if the qty before the last stock is small than quantity asked
        // i take the qty before the last stock and a few in the last stock
        $qtyToWithDrawInNextStock = $qty;
        $qtyWithdraw=0;

        for ($i=0;$i<$nbStocks;$i++){
            $sumQuantityStock = $this->countQtyRemaining($productStocks[$i])->getQtyRemaining();

            $productStockReturn = new ProductStockReturn();
            $productStockReturn->setProductStock($productStocks[$i]);
            $productStockReturn->setRepay(false);
            $productStockReturn->setDate($date);
            $productStockReturn->setRecorder($user);

            if ($qtyToWithDrawInNextStock >= $sumQuantityStock){
                $productStockReturn->setQty($sumQuantityStock);
                $qtyWithdraw += $sumQuantityStock;
            }else{
                $productStockReturn->setQty($qtyToWithDrawInNextStock);
                $qtyWithdraw += $qtyToWithDrawInNextStock;
            }

            $productStockResult[] = $productStockReturn;

            $qtyToWithDrawInNextStock = $qty - $qtyWithdraw;

            if ($qtyToWithDrawInNextStock === 0){
                break;
            }
        }


        return $productStockResult;
    }

    /**
     * @param array $productStocks
     * @return array
     */
    public function getProductGuiStocks(array $productStocks=[]): array {

        $productStockResult = [];
        foreach ($productStocks as $productStock){
            $productStockSale = new ProductStockSale();
            $productStockSale->setProductStock($productStock[0]);
            $productStockSale->setQty($productStock[1]);
            $productStockResult[]=$productStockSale;
        }

        return $productStockResult;
    }

    /**
     * @param DateTime $date
     * @return bool|null
     * @throws Exception
     */
    public function isNew(DateTime $date): ?bool
    {
        if ($date === null)
            return false;

        $timestampDayExpiration = $this->session
                ->get('setting')->getProductNew() * 24 * 3600;
        $dateTimeStamp = $date->getTimeStamp();

        $newTimeStamp = $timestampDayExpiration + $dateTimeStamp;

        return new DateTime(date('Y-m-d', $newTimeStamp)) >= new DateTime();
    }


    /**
     * @param Product $product
     * @param int $qty
     * @return array
     */
    public function getProductSales(Product $product, int $qty): array {

        $nbSales = 10;
        $productSales =
            $this->getProductSaleDispoByProduct($product,$nbSales);

        $productSaleResult = [];
        $sumQuantitySale = 0;

        for ($i=0;$i<$nbSales;$i++){
            $sumQuantitySale += $productSales[$i]->getQtyRemaining();
            $productSaleResult[] = $productSales[$i];

            if ($sumQuantitySale >= $qty){
                break;
            }
        }


        return $productSaleResult;
    }

    /**
     * @param ProductStock $productStock
     * @param int $qty
     * @return array
     */
    public function findProductSales(ProductStock $productStock, int $qty): array {

        $nbSales = 10;
        $productSales =
            $this->getProductSaleDispoByProductStock($productStock,$nbSales);

        $productSaleResult = [];
        $sumQuantitySale = 0;

        for ($i=0;$i<$nbSales;$i++){
            $sumQuantitySale += $productSales[$i]->getQtyRemaining();
            $productSaleResult[] = $productSales[$i];

            if ($sumQuantitySale >= $qty){
                break;
            }
        }


        return $productSaleResult;
    }

    public function removeProductStockSales(EntityManagerInterface $entityManager,
                                            ProductSale $productSale): void
    {
        foreach ($productSale->getProductStockSales() as $productSaleStock){
            $entityManager->remove($productSaleStock);
        }
        $entityManager->remove($productSale);
    }

}
