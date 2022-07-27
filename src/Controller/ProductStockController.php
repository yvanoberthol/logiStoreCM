<?php

namespace App\Controller;


use App\Entity\Product;
use App\Entity\Loss;
use App\Entity\ProductStock;
use App\Entity\LossType;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use App\Repository\LossTypeRepository;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductStockController extends AbstractController
{
    /**
     * @Route("/productStock/delete/{id}", name="product_stock_delete")
     * @param ProductStock $productStock
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ProductStock $productStock,
                           EntityManagerInterface $entityManager): RedirectResponse
    {

        $this->denyAccessUnlessGranted('PRODUCT_STOCK_DELETE',$productStock);

        $stock= $productStock->getStock();

        if ($stock !== null && count($productStock->getProductStockSales()) === 0){
            $entityManager->remove($productStock);
            $entityManager->flush();

            $stockLength = count($stock->getProductStocks());

            if ($stockLength === 0){
                $entityManager->remove($stock);
                $entityManager->flush();
                return $this->redirectToRoute('stock_index');
            }

            $totalAmount = 0;
            foreach ($stock->getProductStocks() as $productStockLigne){
                $totalAmount += $productStockLigne->getSubTotal();
            }
            $stock->setAmount($totalAmount);
            $entityManager->persist($stock);
            $entityManager->flush();


        }
        return $this->redirectToRoute('stock_detail',['id' => $stock->getId()]);
    }


    /**
     * @Route("/productStock/add", name="product_stock_add")
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function add(Request $request,
                        StockRepository $stockRepository,
                        ProductRepository $productRepository,
                        EntityManagerInterface $entityManager): RedirectResponse
    {
        $stockId = (int) $request->get('stockId');
        $stock = $stockRepository->find($stockId);

        $productId = (int) $request->get('productId');
        $product = $productRepository->find($productId);

        $containsProduct = $stock->getProductStocks()
            ->map(static function (ProductStock $productStock){
                return $productStock->getProduct()->getId();
            })->contains($productId);

        if (!$containsProduct){
            $productStock = new ProductStock();
            $productStock->setStock($stock);
            $productStock->setProduct($product);
            $productStock->setQty((int) $request->get('qty'));
            $productStock->setUnitPrice($product->getBuyPrice());

            $entityManager->persist($productStock);
            $entityManager->flush();
        }

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }

    /**
     * @Route("/productStock/withdraw/{id}", name="product_stock_withdraw")
     * @param ProductStock $productStock
     * @param LossTypeRepository $lossTypeRepository
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function withdraw(ProductStock $productStock,
                             LossTypeRepository $lossTypeRepository,
                             Request $request,
                             EntityManagerInterface $entityManager): RedirectResponse
    {

        $type = $lossTypeRepository->findOneBy(['name' => GlobalConstant::OUTOFDATE]);

        if ($type === null) {
            $type = new LossType();
            $type->setName(GlobalConstant::OUTOFDATE);
            $entityManager->persist($type);
        }

        $loss = new Loss();
        $loss->setAddDate(new DateTime());
        $loss->setQty((int)$request->get('qtyRemaining'));
        $loss->setRecorder($this->getUser());
        $loss->setProductStock($productStock);
        $loss->setType($type);
        $entityManager->persist($loss);

        $productStock->setWithdraw(true);
        $entityManager->persist($productStock);

        $entityManager->flush();

        $stockOutOfDate = $request->getSession()->get('stockOutOfDateCount');
        $request->getSession()->set('stockOutOfDateCount',$stockOutOfDate-1);
        return $this->redirectToRoute('product_out_of_date');

    }
}
