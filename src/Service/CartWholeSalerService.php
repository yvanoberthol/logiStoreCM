<?php


namespace App\Service;


use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartWholeSalerService
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
     * CartWholeSalerWholesalerService constructor.
     * @param RequestStack $requestStack
     * @param ProductRepository $productRepository
     * @param TaxRepository $taxRepository
     * @param ProductService $productService
     */
    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository,
                                TaxRepository $taxRepository,
                                ProductService $productService)
    {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->taxRepository = $taxRepository;
        $this->decimalSeparator = $session->get('setting')->getCurrencyDecimal();
    }

    /**
     * @param int $id
     * @param int $qty
     * @param float $price
     * @return bool
     * @throws Exception
     */
    public function add(int $id, int $qty=1,float $price=0): bool {
        $qty = abs($qty);
        $price = abs($price);

        $product = $this->productRepository->find($id);
        if ($product === null){
            return false;
        }

        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);
        $product = $this->productService->countStock($product);

        if ($product->getStock() <= 0){
            $this->remove($id);
            return false;
        }

        if (!empty($cartWholeSaler['products'][$id])){

            if ($product->getStock() < ($cartWholeSaler['products'][$id] + $qty)){
                return false;
            }

            $cartWholeSaler['products'][$id][0]+=$qty;
        }else{
            if ($product->getStock() < $qty){
                return false;
            }

            if ($qty <= 0){
                $qty = 1;
            }
            $cartWholeSaler['products'][$id][0] = $qty;
        }

        $cartWholeSaler['products'][$id][1] = $price;

        $this->session->set('cartWholeSaler',$cartWholeSaler);
        return true;
    }

    public function setTax(int $id): bool {
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);

        if (empty($cartWholeSaler['taxs'][$id])) {
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

        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);
        if (empty($cartWholeSaler['taxs'][$id])){
            $cartWholeSaler['taxs'][$id] = $tax->getId();
        }

        $this->session->set('cartWholeSaler',$cartWholeSaler);

        return true;
    }

    public function removeTax(int $id): bool {
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);
        if (!empty($cartWholeSaler['taxs'][$id])){
            unset($cartWholeSaler['taxs'][$id]);
        }
        $this->session->set('cartWholeSaler',$cartWholeSaler);
        return true;
    }

    /**
     * @param int $id
     * @param int $qty
     * @param float $price
     * @return bool
     * @throws Exception
     */
    public function changeQty(int $id, int $qty=1, float $price=0): bool {
        $qty = abs($qty);
        $price = abs($price);
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);

        $product = $this->productService
            ->countStock($this->productRepository->find($id));

        if ($product->getStock() <= 0){
            $this->remove($id);
            return false;
        }

        // if drug stock is small than quantity wanted set this with the value 1
        if ($product->getStock() < $qty){
            return false;
        }

        if (!empty($cartWholeSaler['products'][$id])){
            $cartWholeSaler['products'][$id][0] = $qty;
        }else{
            if ($qty <= 0){
                $qty = 1;
            }
            $cartWholeSaler['products'][$id][0] = $qty;
        }

        $cartWholeSaler['products'][$id][1] = $price;

        $this->session->set('cartWholeSaler',$cartWholeSaler);
        return true;
    }

    public function minus(int $id,int $qty=1): bool {
        $qty = abs($qty);
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);
        if (!empty($cartWholeSaler['products'][$id])){
            if ($cartWholeSaler['products'][$id] <= $qty){
                $this->remove($id);
            }else{
                if ($qty <= 0){
                    $qty = 1;
                }
                $cartWholeSaler['products'][$id] -= $qty;
                $this->session->set('cartWholeSaler',$cartWholeSaler);
            }
            return true;
        }

        return false;
    }

    public function remove(int $id): bool {
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);
        if (!empty($cartWholeSaler['products'][$id])){
            unset($cartWholeSaler['products'][$id]);
        }

        $this->session->set('cartWholeSaler',$cartWholeSaler);

        if (empty($cartWholeSaler['products']))
            $this->removeAll();

        return true;
    }

    public function removeAll(): void {
        $this->session->remove('cartWholeSaler');
    }

    public function selectWholeSaler(int $id): bool {
        $this->session->set('customer',$id);

        $this->session->remove('cartWholeSaler');

        return true;
    }

    public function getFullCartWholeSaler(): array {
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);

        $cartWholeSalerWithData = [];
        if (!empty($cartWholeSaler['products'])) {
            foreach ($cartWholeSaler['products'] as $id => $tab) {
                $product = $this->productRepository->find($id);

                $cartWholeSalerWithData[] = [
                    'product' => $product,
                    'qty' => $tab[0],
                    'unitPrice' => $tab[1],
                ];
            }
        }
        return $cartWholeSalerWithData;
    }

    public function getTaxs(): array {
        $cartWholeSaler = $this->session->get('cartWholeSaler',[]);

        $taxs= [];
        if (!empty($cartWholeSaler['taxs'])) {
            foreach ($cartWholeSaler['taxs'] as $id) {
                $taxs[] = $this->taxRepository->find($id);
            }
        }
        return $taxs;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCartWholeSaler() as $item){

            $totalItem = $item['unitPrice'] * $item['qty'];
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
        foreach ($this->getFullCartWholeSaler() as $item){

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
