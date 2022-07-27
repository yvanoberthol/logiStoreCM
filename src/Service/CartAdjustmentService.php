<?php


namespace App\Service;


use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartAdjustmentService
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

    private $decimalSeparator;

    /**
     * CartAdjustmentAdjustmentService constructor.
     * @param RequestStack $requestStack
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     */
    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository,
                                ProductService $productService)
    {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->decimalSeparator = $this->session->get('setting')->getCurrencyDecimal();
    }

    /**
     * @param int $id
     * @param int $qty
     * @return bool
     * @throws Exception
     */
    public function changeQty(int $id, int $qty=1): bool {
        $qty = abs($qty);
        $cartAdjustment = $this->session->get('cartAdjustment',[]);

        $product = $this->productService
            ->countStock($this->productRepository->find($id));

        if ($product->getStock() <= 0 ){
            $this->remove($id);
            return false;
        }

        if ($product->getStock() < $qty){
            return false;
        }

        $qtyStock = $product->getStock();
        $qtyAdjust = $product->getStock() - $qty;

        if (!empty($cartAdjustment['products'][$id])){
            $cartAdjustment['products'][$id][0] = $qtyAdjust;
            $cartAdjustment['products'][$id][1] = $qty;
            $cartAdjustment['products'][$id][2] = $qtyStock;
        }else{
            if ($qtyAdjust <= 0){
                $qtyAdjust = 1;
            }
            $cartAdjustment['products'][$id][0] = $qtyAdjust;
            $cartAdjustment['products'][$id][1] = $qty;
            $cartAdjustment['products'][$id][2] = $qtyStock;
        }

        $this->session->set('cartAdjustment',$cartAdjustment);
        return true;
    }

    public function minus(int $id,int $qty=1): bool {
        $qty = abs($qty);
        $cartAdjustment = $this->session->get('cartAdjustment',[]);
        if (!empty($cartAdjustment['products'][$id])){
            if ($cartAdjustment['products'][$id] <= $qty){
                $this->remove($id);
            }else{
                if ($qty <= 0){
                    $qty = 1;
                }
                $cartAdjustment['products'][$id] -= $qty;
                $this->session->set('cartAdjustment',$cartAdjustment);
            }
            return true;
        }

        return false;
    }

    public function remove(int $id): bool {
        $cartAdjustment = $this->session->get('cartAdjustment',[]);
        if (!empty($cartAdjustment['products'][$id])){
            unset($cartAdjustment['products'][$id]);
        }

        $this->session->set('cartAdjustment',$cartAdjustment);

        if (empty($cartAdjustment['products']))
            $this->removeAll();

        return true;
    }

    public function removeAll(): void {
        $this->session->remove('cartAdjustment');
    }

    public function getFullCartAdjustment(): array {
        $cartAdjustment = $this->session->get('cartAdjustment',[]);

        $cartAdjustmentWithData = [];
        if (!empty($cartAdjustment['products'])) {
            foreach ($cartAdjustment['products'] as $id => $tab) {
                $product = $this->productRepository->find($id);

                $cartAdjustmentWithData[] = [
                    'product' => $product,
                    'qtyAdjust' => $tab[0],
                    'qty' => $tab[1],
                    'qtyStock' => $tab[2],
                ];
            }
        }
        return $cartAdjustmentWithData;
    }

    /**
     * @param $item
     * @return array
     * @throws Exception
     */
    public function getProductStocks($item): array
    {
        return $this->productService
            ->getProductStocksByAdjustment($item['product'],$item['qtyAdjust']);
    }

}
