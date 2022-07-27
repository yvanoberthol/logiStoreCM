<?php


namespace App\Service;


use Amp\Http\Client\Request;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderService
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
     * @var TaxRepository
     */
    private $taxRepository;

    private $decimalSeparator;


    /**
     * OrderService constructor.
     * @param RequestStack $requestStack
     * @param TaxRepository $taxRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(RequestStack $requestStack,
                                TaxRepository $taxRepository,
                                ProductRepository $productRepository)
    {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
        $this->decimalSeparator = $this->session->get('setting')->getCurrencyDecimal();
    }

    public function add(int $id,$price,$qty=1): bool {
        $qty = abs($qty);
        $price = abs($price);

        $product = $this->productRepository->find($id);

        if ($product->getSellPrice() <= $price){
            return false;
        }

        $order = $this->session->get('order',[]);
        if (!empty($order['products'][$id])){
            $order['products'][$id]['qty']+=$qty;
        }else{
            $order['products'][$id]['qty'] = $qty;
        }

        $order['products'][$id]['price'] = $price;

        $this->session->set('order',$order);

        return true;
    }

    public function changeQty(int $id,int $qty=1): bool {
        $qty = abs($qty);

        $order = $this->session->get('order',[]);

        if (!empty($order['products'][$id])){
            $order['products'][$id]['qty'] = $qty;
        }else{
            if ($qty <= 0){
                $qty = 1;
            }
            $order['products'][$id]['qty'] = $qty;
        }

        $this->session->set('order',$order);
        return true;
    }

    public function minus(int $id,$qty=1): void {
        $qty = abs($qty);
        $order = $this->session->get('order',[]);
        if (!empty($order['products'][$id])){
            if ($order['products'][$id]['qty'] <= $qty){
                $this->remove($id);
            }else{
                $order['products'][$id]['qty'] -= $qty;
                $this->session->set('order',$order);
            }

        }
    }

    public function setTax(int $id): bool {
        $order = $this->session->get('order',[]);

        if (empty($order['taxs'][$id])) {
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

        $order = $this->session->get('order',[]);
        if (empty($order['taxs'][$id])){
            $order['taxs'][$id] = $tax->getId();
        }

        $this->session->set('order',$order);

        return true;
    }

    private function removeTax(int $id): bool {
        $order = $this->session->get('order',[]);
        if (!empty($order['taxs'][$id])){
            unset($order['taxs'][$id]);
        }
        $this->session->set('order',$order);
        return true;
    }


    public function remove(int $id): bool {
        $order = $this->session->get('order',[]);
        if (!empty($order['products'][$id])){
            unset($order['products'][$id]);
        }
        $this->session->set('order',$order);

        if (empty($order['products']))
            $this->removeAll();

        return true;
    }

    public function removeAll(): bool {
       $this->session->remove('order');

       return true;
    }

    public function getFullOrder(): array {
        $order = $this->session->get('order',[]);

        $orderWithData = [];
        if (!empty($order['products'])){
            foreach ($order['products'] as $id=>$item){
                $product = $this->productRepository->find($id);
                $price = $item['price']??$product->getBuyPrice();
                $orderWithData[] = [
                    'product' => $product,
                    'price' => $price,
                    'qty' => $item['qty']
                ];
            }
        }

        return $orderWithData;
    }

    public function getRestFullOrder(): array {
        $order = $this->session->get('order',[]);

        $orderWithData = [];
        if (!empty($order['products'])) {
            foreach ($order['products'] as $id => $item) {
                $product = $this->productRepository->find($id);
                $orderWithData[] = [
                    'name' => $product->getName(),
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'qty' => $item['qty']
                ];
            }
        }
        return $orderWithData;
    }

    public function getTaxs(): array {
        $order = $this->session->get('order',[]);

        $taxs= [];
        if (!empty($order['taxs'])) {
            foreach ($order['taxs'] as $id) {
                $taxs[] = $this->taxRepository->find($id);
            }
        }
        return $taxs;
    }


    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullOrder() as $item){
            $totalItem = $item['price'] * $item['qty'];
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

}
