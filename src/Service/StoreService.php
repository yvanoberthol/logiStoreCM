<?php


namespace App\Service;


use App\Entity\Product;
use App\Entity\ProductStock;
use App\Entity\Setting;
use App\Entity\Stock;
use App\Repository\ProductRepository;
use App\Repository\ProductStockRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StoreService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var Setting
     */
    private $setting;


    /**
     * OrderService constructor.
     * @param ProductService $productService
     * @param RequestStack $requestStack
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductService $productService,
                                RequestStack $requestStack,
                                ProductRepository $productRepository)
    {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    private function getLineProductState(Product $product): array
    {
        $producStocks =
            $this->productService->getProductStockDispoByProduct($product);

        $unitPrice = round(array_sum(array_map(static function(ProductStock $productStock){
            return $productStock->getUnitPrice();
        },$producStocks)) / count($producStocks));

        $sellPrice = $product->getSellPrice();
        if ($this->setting->getProductWithDiscount()){
            $sellPrice = $product->getSellPriceWithDiscount();
        }

        $salePrice = $product->getStock() * $sellPrice;
        $purchasePrice = $product->getStock() * $unitPrice;

        $profit = $product->getStock() * ($sellPrice - $unitPrice);

        $percentProfit =((float)$salePrice === 0.0) ?0
            :round(($profit * 100)/$salePrice,2);
        $unitProfit =((int)$product->getStock() === 0) ?0
            :round($profit/$product->getStock());
        return [
            'id' => $product->getId(),
            'barcode' => $product->getQrCode(),
            'name' => $product->getNameWithCategory(),
            'sellPrice' => $product->getSellPrice(),
            'buyPrice' => $unitPrice,
            'qty' => $product->getStock(),
            'salePrice' => $salePrice,
            'purchasePrice' => $purchasePrice,
            'profit' => $profit,
            'unitProfit' => $unitProfit,
            'percentProfit' => $percentProfit
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductStoreValue(): array
    {
        $products =
            $this->productService->getProductByStockNotFinish($this->productRepository->findAll());

        $lines = [];
        foreach ($products as $product){
            $lines[] = $this->getLineProductState($product);
        }

        return $lines;
    }

}
