<?php


namespace App\Service;


use App\Entity\Product;
use App\Entity\Setting;
use App\Repository\ProductRepository;
use App\Repository\ProductSaleReturnRepository;
use App\Repository\ProductSaleRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EncashmentService
{
    /**
     * @var ProductSaleRepository
     */
    private $productSaleRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Setting
     */
    private $setting;
    /**
     * @var ProductSaleReturnRepository
     */
    private $productSaleReturnRepository;

    /**
     * ProductService constructor.
     * @param ProductSaleRepository $productSaleRepository
     * @param ProductSaleReturnRepository $productSaleReturnRepository
     * @param ProductRepository $productRepository
     * @param RequestStack $requestStack
     */
    public function __construct(ProductSaleRepository $productSaleRepository,
                                ProductSaleReturnRepository $productSaleReturnRepository,
                                ProductRepository $productRepository,
                                RequestStack $requestStack)
    {
        $this->productSaleRepository = $productSaleRepository;
        $this->productRepository = $productRepository;
        $this->setting = $requestStack->getSession()->get('setting');
        $this->productSaleReturnRepository = $productSaleReturnRepository;
    }
    public function getInventory($start, $end, $employee = null,$enable=true): array {


        if ($employee !== null && $this->setting->getWithUserCategory()){
            $products = [];
            foreach ($employee->getCategories() as $category){
                $productSelected = array_filter($category->getProducts()->toArray(),
                    static function(Product $product) use($enable){
                        return $product->getEnabled() === $enable;
                    });

                foreach ($productSelected as $product){
                    $products[] = $product;
                }
            }
        }else{
            $products = $this->productRepository->findBy(['enabled'  => $enable]);
        }

        $qtySolds = $this->productSaleRepository
            ->nbByProductAndPeriodDate($start,$end,null,$employee);

        $qtySaleReturns = $this->productSaleReturnRepository
            ->nbByProductAndPeriodDate($start,$end,null,$employee);

        $inventory = [];
        foreach ($products as $product){
            $inventory[] = $this->getInventoryLine($qtySolds,$qtySaleReturns, $product);
        }

        return $inventory;
    }

    private function getInventoryLine($qtySolds, $qtySaleReturns, Product $product): array {


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

        $nbSaleReturn = 0;
        foreach ($qtySaleReturns as $qtySaleReturn){
            if ((int) $qtySaleReturn['id'] === $product->getId()){
                $nbSaleReturn = (int) $qtySaleReturn['qty'];
                break;
            }
        }

        $nbSold -= $nbSaleReturn;
        $amountSold -= ($nbSaleReturn * $unitPrice);

        return [
            'id' => $product->getId(),
            'product' => $product->getName(),
            'packagingQty' => $product->getPackagingQty(),
            'unitPrice' => $unitPrice,
            'amountSold' => $amountSold,
            'qtySold' => $nbSold,
            'qtySaleReturn' => $nbSaleReturn
        ];
    }

}
