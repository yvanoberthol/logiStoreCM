<?php


namespace App\Service;


use App\Entity\Setting;
use App\Repository\ProductRepository;
use App\Repository\ProductStockRepository;
use App\Repository\CustomerProductPriceRepository;
use App\Repository\SupplierRepository;
use App\Repository\TaxRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartWholeSalerGuiService
{
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var TaxRepository
     */
    private $taxRepository;

    private $decimalSeparator;
    /**
     * @var ProductStockRepository
     */
    private $productStockRepository;

    /**
     * CartWholesalerService constructor.
     * @param SessionInterface $session
     * @param ProductRepository $productRepository
     * @param ProductStockRepository $productStockRepository
     * @param TaxRepository $taxRepository
     * @param ProductService $productService
     */
    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository,
                                ProductStockRepository $productStockRepository,
                                TaxRepository $taxRepository,
                                ProductService $productService)
    {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
        $this->productStockRepository = $productStockRepository;
        $this->productService = $productService;
        $this->taxRepository = $taxRepository;
        $this->decimalSeparator = $this->session->get('setting')->getCurrencyDecimal();
    }

    public function setTax(int $id): bool {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);

        if (empty($cartWholesaler['taxs'][$id])) {
            $result = $this->addTax($id);
        }
        else {
            $result = $this->removeTax($id);
        }

        return $result;
    }

    private function addTax(int $id): bool {

        $tax = $this->taxRepository->find($id);
        if ($tax === null){
            return false;
        }

        $cartWholesaler = $this->session->get('cartWholesaler',[]);
        if (empty($cartWholesaler['taxs'][$id])){
            $cartWholesaler['taxs'][$id] = $tax->getId();
        }

        $this->session->set('cartWholesaler',$cartWholesaler);

        return true;
    }

    public function removeTax(int $id): bool {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);
        if (!empty($cartWholesaler['taxs'][$id])){
            unset($cartWholesaler['taxs'][$id]);
        }
        $this->session->set('cartWholesaler',$cartWholesaler);
        return true;
    }

    /**
     * @param int $id
     * @param array $productStocks
     * @return bool
     */
    public function changeQty(int $id, array $productStocks=[]): bool {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);

        $cartWholesaler['productStocks'][$id] = $productStocks;

        $this->session->set('cartWholesaler',$cartWholesaler);
        return true;
    }

    public function remove(int $id): bool {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);
        if (!empty($cartWholesaler['productStocks'][$id])){
            unset($cartWholesaler['productStocks'][$id]);
        }

        $this->session->set('cartWholesaler',$cartWholesaler);

        if (empty($cartWholesaler['productStocks']))
            $this->removeAll();

        return true;
    }

    public function selectWholeSaler(int $id): bool {
        $this->session->set('customer',$id);

        $this->session->remove('cartWholesaler');

        return true;
    }

    public function removeAll(): void {
       $this->session->remove('cartWholesaler');
    }

    public function getFullCartWholesaler(): array {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);

        $cartWholesalerWithData = [];
        if (!empty($cartWholesaler['productStocks'])) {
            foreach ($cartWholesaler['productStocks'] as $id => $productStocks) {
                $productStockLines = [];
                $product = $this->productRepository->find($id);
                $unitPrice = $product->getWholePrice();
                foreach ($productStocks as $productStockLine){
                    $line = explode(',',$productStockLine);
                    $productStock = $this->productService
                        ->countQtyRemaining($this->productStockRepository
                            ->find((int)$line[0]));
                    $productStockLines[] = [$productStock,$line[1]];
                    $unitPrice = $line[2];
                }

                $product = $this->productRepository->find($id);

                $cartWholesalerWithData[] = [
                    'product' => $product,
                    'qty' => array_sum(array_map(static function($line){return $line[1];},$productStockLines)),
                    'unitPrice' => $unitPrice,
                    'productStocks' => $productStockLines
                ];
            }
        }
        return $cartWholesalerWithData;
    }

    public function getTaxs(): array {
        $cartWholesaler = $this->session->get('cartWholesaler',[]);

        $taxs= [];
        if (!empty($cartWholesaler['taxs'])) {
            foreach ($cartWholesaler['taxs'] as $id) {
                $taxs[] = $this->taxRepository->find($id);
            }
        }
        return $taxs;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCartWholesaler() as $item){
            foreach ($item['productStocks'] as $productStock){
                $total += (float) $item['unitPrice'] * $productStock[1];
            }

        }
        return round($total,$this->decimalSeparator);
    }

    public function getTotalTax(): float
    {
        $total = 0;
        foreach ($this->getTaxs() as $tax){
            $total += $this->getTotal() * $tax->getRate() / 100;
        }
        return round($total,$this->decimalSeparator);
    }

    public function getTotalWithTax(): float
    {
        return  round(($this->getTotal() +  $this->getTotalTax()),$this->decimalSeparator);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getItemProfit(): array
    {

        $profitTotal = 0;
        $itemProfit = [];
        foreach ($this->getFullCartWholesaler() as $item){

            $totalItem = $this->productService
                ->getProfitWholeSaleByProduct($item['unitPrice'],$item['productStocks']);
            $itemProfit[$item['product']->getId()] = $totalItem;

            $profitTotal += $totalItem;

        }
        $itemProfit['total'] = round(($profitTotal + $this->getTotalTax()),
            $this->decimalSeparator);

        return $itemProfit;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getProductStocks($item): array
    {
        return $this->productService
            ->getProductGuiStocks($item['productStocks']);
    }

}
