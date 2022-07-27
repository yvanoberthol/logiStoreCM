<?php


namespace App\Service;


use App\Entity\Setting;
use App\Repository\ProductPriceRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
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
     * @var ProductPriceRepository
     */
    private $productPriceRepository;

    /**
     * @var Setting
     */
    private $setting;

    /**
     * CartService constructor.
     * @param RequestStack $requestStack
     * @param ProductRepository $productRepository
     * @param ProductPriceRepository $productPriceRepository
     * @param TaxRepository $taxRepository
     * @param ProductService $productService
     */
    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository,
                                ProductPriceRepository $productPriceRepository,
                                TaxRepository $taxRepository,
                                ProductService $productService)
    {
        $this->session = $requestStack->getSession();
        $this->setting = $this->session->get('setting');
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->taxRepository = $taxRepository;
        $this->decimalSeparator = $this->session->get('setting')->getCurrencyDecimal();
        $this->productPriceRepository = $productPriceRepository;
    }

    /**
     * @param int $id
     * @param int $qty
     * @return bool
     * @throws Exception
     */
    public function add(int $id, int $qty=1): bool {
        $qty = abs($qty);

        $product = $this->productRepository->find($id);
        if ($product === null){
            return false;
        }

        $cart = $this->session->get('cart',[]);
        $product = $this->productService->countStock($product);

        if ($product->getStock() <= 0){
            $this->remove($id);
            return false;
        }

        if (!empty($cart['products'][$id])){

            if ($product->getStock() < ($cart['products'][$id] + $qty)){
                return false;
            }

            $cart['products'][$id]+=$qty;
        }else{
            if ($product->getStock() < $qty){
                return false;
            }

            if ($qty <= 0){
                $qty = 1;
            }
            $cart['products'][$id] = $qty;
        }

        $this->session->set('cart',$cart);
        return true;
    }

    public function setTax(int $id): bool {
        $cart = $this->session->get('cart',[]);

        if (empty($cart['taxs'][$id])) {
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

        $cart = $this->session->get('cart',[]);
        if (empty($cart['taxs'][$id])){
            $cart['taxs'][$id] = $tax->getId();
        }

        $this->session->set('cart',$cart);

        return true;
    }

    public function removeTax(int $id): bool {
        $cart = $this->session->get('cart',[]);
        if (!empty($cart['taxs'][$id])){
            unset($cart['taxs'][$id]);
        }
        $this->session->set('cart',$cart);
        return true;
    }

    /**
     * @param int $id
     * @param int $qty
     * @return bool
     * @throws Exception
     */
    public function changeQty(int $id, int $qty=1): bool {
        $qty = abs($qty);
        $cart = $this->session->get('cart',[]);

        $product = $this->productService
            ->countStock($this->productRepository->find($id));

        if ($product->getStock() <= 0){
            $this->remove($id);
            return false;
        }

        // if drug stock is small than quantity wanted set this with the value 1
        if ($product->getStock() < $qty){
            $cart['products'][$id] = 1;

            $this->session->set('cart',$cart);
            return false;
        }

        if (!empty($cart['products'][$id])){
            $cart['products'][$id] = $qty;
        }else{
            if ($qty <= 0){
                $qty = 1;
            }
            $cart['products'][$id] = $qty;
        }

        $this->session->set('cart',$cart);
        return true;
    }

    public function minus(int $id,int $qty=1): bool {
        $qty = abs($qty);
        $cart = $this->session->get('cart',[]);
        if (!empty($cart['products'][$id])){
            if ($cart['products'][$id] <= $qty){
                $this->remove($id);
            }else{
                if ($qty <= 0){
                    $qty = 1;
                }
                $cart['products'][$id] -= $qty;
                $this->session->set('cart',$cart);
            }
            return true;
        }

        return false;
    }

    public function remove(int $id): bool {
        $cart = $this->session->get('cart',[]);
        if (!empty($cart['products'][$id])){
            unset($cart['products'][$id]);
        }

        $this->session->set('cart',$cart);

        if (empty($cart['products']))
            $this->removeAll();

        return true;
    }

    public function removeAll(): void {
       $this->session->remove('cart');
    }

    public function getFullCart(): array {
        $cart = $this->session->get('cart',[]);

        $cartWithData = [];
        if (!empty($cart['products'])) {
            foreach ($cart['products'] as $id => $qty) {
                $product = $this->productRepository->find($id);

                $sellPrice = $product->getSellPrice();

                if ($this->setting->getWithBatchPrice()
                    && count($product->getProductPrices()) > 0){
                    $productPrice = $this->productPriceRepository
                        ->findOneByProductAndQty($product,$qty);
                    if ($productPrice !== null){
                        $sellPrice = $productPrice->getUnitPrice();
                    }
                }

                $cartWithData[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'sellPrice' => $sellPrice,
                ];
            }
        }
        return $cartWithData;
    }

    public function getTaxs(): array {
        $cart = $this->session->get('cart',[]);

        $taxs= [];
        if (!empty($cart['taxs'])) {
            foreach ($cart['taxs'] as $id) {
                $taxs[] = $this->taxRepository->find($id);
            }
        }
        return $taxs;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCart() as $item){
            $sellPrice = $item['product']->getSellPrice();
            if ($this->setting->getWithBatchPrice() && count($item['product']->getProductPrices()) > 0){
                $productPrice = $this->productPriceRepository
                    ->findOneByProductAndQty($item['product'],$item['qty']);
                if ($productPrice !== null){
                    $sellPrice = $productPrice->getUnitPrice();
                }
            }

            $totalItem = $sellPrice * $item['qty'];
            $total += $totalItem;
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
        foreach ($this->getFullCart() as $item){

            $totalItem = $this->productService
                ->getProfitByProduct($item['product'],$item['qty']);
            $itemProfit[$item['product']->getId()] = $totalItem;

            $profitTotal += $totalItem;

        }
        $itemProfit['total'] = round(($profitTotal + $this->getTotalTax()),
            $this->decimalSeparator);

        return $itemProfit;
    }

    /**
     * @param $item
     * @return array
     * @throws Exception
     */
    public function getProductStocks($item): array
    {
        return $this->productService
            ->getProductStocks($item['product'],$item['qty']);
    }

}
