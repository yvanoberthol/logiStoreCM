<?php


namespace App\Service;


use App\Entity\Setting;
use App\Repository\ProductPriceRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductStockRepository;
use App\Repository\TaxRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartGuiService
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
     * @var ProductPriceRepository
     */
    private $productPriceRepository;

    /**
     * @var Setting
     */
    private $setting;


    /**
     * CartGuiService constructor.
     * @param SessionInterface $session
     * @param ProductRepository $productRepository
     * @param ProductPriceRepository $productPriceRepository
     * @param ProductStockRepository $productStockRepository
     * @param TaxRepository $taxRepository
     * @param ProductService $productService
     */
    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository,
                                ProductPriceRepository $productPriceRepository,
                                ProductStockRepository $productStockRepository,
                                TaxRepository $taxRepository,
                                ProductService $productService)
    {
        $this->session = $requestStack->getSession();
        $this->setting = $this->session->get('setting');
        $this->productRepository = $productRepository;
        $this->productStockRepository = $productStockRepository;
        $this->productService = $productService;
        $this->taxRepository = $taxRepository;
        $this->decimalSeparator = $this->session->get('setting')->getCurrencyDecimal();
        $this->productPriceRepository = $productPriceRepository;
    }

    public function setTax(int $id): bool {
        $cartGui = $this->session->get('cartGui',[]);

        if (empty($cartGui['taxs'][$id])) {
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

        $cartGui = $this->session->get('cartGui',[]);
        if (empty($cartGui['taxs'][$id])){
            $cartGui['taxs'][$id] = $tax->getId();
        }

        $this->session->set('cartGui',$cartGui);

        return true;
    }

    public function removeTax(int $id): bool {
        $cartGui = $this->session->get('cartGui',[]);
        if (!empty($cartGui['taxs'][$id])){
            unset($cartGui['taxs'][$id]);
        }
        $this->session->set('cartGui',$cartGui);
        return true;
    }

    /**
     * @param int $id
     * @param array $productStocks
     * @return bool
     */
    public function changeQty(int $id, array $productStocks=[]): bool {
        $cartGui = $this->session->get('cartGui',[]);

        $cartGui['productStocks'][$id] = $productStocks;

        $this->session->set('cartGui',$cartGui);
        return true;
    }

    public function remove(int $id): bool {
        $cartGui = $this->session->get('cartGui',[]);
        if (!empty($cartGui['productStocks'][$id])){
            unset($cartGui['productStocks'][$id]);
        }

        $this->session->set('cartGui',$cartGui);

        if (empty($cartGui['productStocks']))
            $this->removeAll();

        return true;
    }

    public function removeAll(): void {
       $this->session->remove('cartGui');
    }

    public function getFullCartGui(): array {
        $cartGui = $this->session->get('cartGui',[]);

        $cartGuiWithData = [];
        if (!empty($cartGui['productStocks'])) {
            foreach ($cartGui['productStocks'] as $id => $productStocks) {
                $productStockLines = [];
                foreach ($productStocks as $productStockLine){
                    $line = explode(',',$productStockLine);
                    $productStock = $this->productStockRepository->find((int)$line[0]);
                    $productStockLines[] = [$productStock,$line[1]];
                }

                $qty = array_sum(array_map(static function($line){return $line[1];},$productStockLines));

                $product = $this->productRepository->find($id);
                $sellPrice = $product->getSellPrice();

                if ($this->setting->getWithBatchPrice() && count($product->getProductPrices()) > 0){
                    $productPrice = $this->productPriceRepository
                        ->findOneByProductAndQty($product,$qty);
                    if ($productPrice !== null){
                        $sellPrice = $productPrice->getUnitPrice();
                    }
                }

                $cartGuiWithData[] = [
                    'product' => $this->productRepository->find($id),
                    'qty' => $qty,
                    'sellPrice' => $sellPrice,
                    'productStocks' => $productStockLines
                ];
            }
        }
        return $cartGuiWithData;
    }

    public function getTaxs(): array {
        $cartGui = $this->session->get('cartGui',[]);

        $taxs= [];
        if (!empty($cartGui['taxs'])) {
            foreach ($cartGui['taxs'] as $id) {
                $taxs[] = $this->taxRepository->find($id);
            }
        }
        return $taxs;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCartGui() as $item){
            $sellPrice = $item['product']->getSellPrice();
            if ($this->setting->getWithBatchPrice() && count($item['product']->getProductPrices()) > 0){
                $productPrice = $this->productPriceRepository
                    ->findOneByProductAndQty($item['product'],$item['qty']);
                if ($productPrice !== null){
                    $sellPrice = $productPrice->getUnitPrice();
                }
            }

            foreach ($item['productStocks'] as $productStock){
                $total += $sellPrice * $productStock[1];
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
        foreach ($this->getFullCartGui() as $item){

            $totalItem = $this->productService
                ->getProfitGuiByProduct($item['product'],$item['productStocks']);
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
