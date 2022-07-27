<?php


namespace App\Controller;


use App\Entity\ProductStockReturn;
use App\Entity\Setting;
use App\Repository\ProductStockRepository;
use App\Repository\ProductStockSaleRepository;
use App\Service\ErrorService;
use App\Service\ProductService;
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

class ProductStockReturnController extends AbstractController
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
     * @Route("/productStock/return/delete/{id}", name="stock_product_return_delete")
     * @param ProductStockReturn $productStockReturn
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ProductStockReturn $productStockReturn,
                           EntityManagerInterface $entityManager): RedirectResponse
    {

        if (!$this->setting->getWithStockReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $entityManager->remove($productStockReturn);
        $entityManager->flush();
        $this->addFlash('success', "controller.productStockReturn.delete.flash.success");

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @Route("/productStock/return/add", name="stock_product_return_add", methods={"POST"})
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param ProductService $productService
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request,
                        ProductStockRepository $productStockRepository,
                        ProductService $productService,
                        EntityManagerInterface $entityManager): Response
    {

        if (!$this->setting->getWithStockReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $productStock = $productService->countQtyRemaining($productStockRepository
            ->find((int)$request->get('productStock')));

        $qtyReturn = (int)$request->get('qtyReturn');

        if ($productStock !== null &&
            ($qtyReturn === 0 || $qtyReturn > $productStock->getQtyRemaining())) {
            $this->addFlash('danger', "controller.productReturn.add.flash.error_qty");
            return $this->redirect($_SERVER['HTTP_REFERER']);
        }

        if (!GlobalConstant::compareDate($request->get('date'), new DateTime())) {
            $this->addFlash('danger', "controller.productReturn.add.flash.danger2");
            return $this->redirect($_SERVER['HTTP_REFERER']);
        }

        $return = new ProductStockReturn();
        $return
            ->setDate(new DateTime($request->get('date')))
            ->setQty($qtyReturn)
            ->setProductStock($productStock)
            ->setReason($request->get('reason'))
            ->setRepay($request->get('repay') ?? false)
            ->setRecorder($this->getUser());

        $entityManager->persist($return);

        $entityManager->flush();
        $this->addFlash('success', "controller.productStockReturn.add.flash.success");

        return $this->redirect($_SERVER['HTTP_REFERER']);
    }
}
