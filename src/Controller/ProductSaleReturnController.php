<?php


namespace App\Controller;


use App\Entity\ProductSaleReturn;
use App\Entity\Setting;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductStockSaleRepository;
use App\Service\ErrorService;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductSaleReturnController extends AbstractController
{

    /**
     * @var Setting
     */
    private $setting;

    /**
     * ExpenseController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->setting = $requestStack->getSession()->get('setting');
    }

    /**
     * @Route("/productSale/return/delete/{id}", name="sale_product_return_delete")
     * @param ProductSaleReturn $productSaleReturn
     * @param ErrorService $errorService
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ProductSaleReturn $productSaleReturn,
                           ErrorService $errorService,
                           EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithSaleReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($errorService->denyAccessUnlessGranted(
            'SALE_PRODUCT_RETURN_DELETE',
            $productSaleReturn)){

            return $this->redirect($_SERVER['HTTP_REFERER']);
        }

        $entityManager->remove($productSaleReturn);
        $entityManager->flush();
        $this->addFlash('success',"controller.productSaleReturn.delete.flash.success");

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @Route("/productSale/return/add", name="sale_product_return_add", methods={"POST"})
     * @param Request $request
     * @param ProductSaleRepository $productSaleRepository
     * @param ProductStockSaleRepository $productStockSaleRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request,
                        ProductSaleRepository $productSaleRepository,
                        ProductStockSaleRepository $productStockSaleRepository,
                        EntityManagerInterface $entityManager): Response
    {

        if (!$this->setting->getWithSaleReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

       $productSale = $productSaleRepository
           ->find((int) $request->get('productSale'));

       $qtyReturn = (int) $request->get('qtyReturn');

       if ($productSale !== null &&
           ($qtyReturn === 0 || $qtyReturn > $productSale->getQtyRemaining())){
           $this->addFlash('danger',"controller.productReturn.add.flash.error_qty");
           return $this->redirect($_SERVER['HTTP_REFERER']);
       }

       if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
           $this->addFlash('danger',"controller.productReturn.add.flash.danger2");
           return $this->redirect($_SERVER['HTTP_REFERER']);
       }

       foreach ($request->get('ps') as $i=>$ps){
           $qty = $request->get('qty')[$i];
           if ($qty > 0){
               $productStockSale =
                   $productStockSaleRepository->find((int) $ps);

               $return = new ProductSaleReturn();
               $return
                   ->setDate(new DateTime($request->get('date')))
                   ->setQty($request->get('qty')[$i])
                   ->setProductStockSale($productStockSale)
                   ->setReason($request->get('reason'))
                   ->setRepay($request->get('repay')??false)
                   ->setStockable($request->get('stockable')??false)
                   ->setRecorder($this->getUser());

               $entityManager->persist($return);
           }
       }

       $entityManager->flush();
       $this->addFlash('success',"controller.productReturn.add.flash.success");

       return $this->redirect($_SERVER['HTTP_REFERER']);
    }
}
