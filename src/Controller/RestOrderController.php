<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Entity\Setting;
use App\Entity\Stock;
use App\Entity\ProductStock;
use App\Repository\ProductRepository;
use App\Repository\ProductStockRepository;
use App\Repository\SupplierRepository;
use App\Repository\TaxRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class RestOrderController extends AbstractController
{
    /**
     * @var SupplierRepository
     */
    private $supplierRepository;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var TaxRepository
     */
    private $taxRepository;

    /**
     * @var Setting
     */
    private $setting;

    /**
     * RestOrderController constructor.
     * @param SupplierRepository $supplierRepository
     * @param OrderService $orderService
     * @param RequestStack $requestStack
     * @param TaxRepository $taxRepository
     * @param UserRepository $userRepository
     */
    public function __construct(SupplierRepository $supplierRepository,
                                OrderService $orderService,
                                RequestStack $requestStack,
                                TaxRepository $taxRepository,
                                UserRepository $userRepository)
    {
        $this->supplierRepository = $supplierRepository;
        $this->orderService = $orderService;
        $this->userRepository = $userRepository;
        $this->taxRepository = $taxRepository;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/order/add", name="rest_order_add",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function addToOrder(Request $request): Response
    {
        if ($request->get('price') === 'null'){
            $result = $this->orderService->changeQty((int) $request->get('id'),
                (int) $request->get('qty'));
        }else{
            $result = $this->orderService->add((int) $request->get('id'),(int) $request->get('price'),
                (int) $request->get('qty'));
        }

        if (!$result){
            return $this->json(null,200);
        }

        $data = $this->getInfoOrder();
        return $this->json($data,200);
    }

    /**
     * @Route("/order/get", name="rest_order_get")
     * @return Response
     */
    public function getOrder(): Response
    {
        $data = $this->getInfoOrder();
        return $this->json($data,200);
    }


    /**
     * @Route("/order/remove", name="rest_order_remove",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function removeToOrder(Request $request): Response
    {
        $this->orderService->remove((int) $request->get('id'));

        $data = $this->getInfoOrder();
        return $this->json($data,200);
    }

    /**
     * @Route("/order/removeAll", name="rest_order_removeAll",methods={"GET","POST"})
     * @return Response
     */
    public function removeToOrderAll(): Response
    {
        $this->orderService->removeAll();

        $data = $this->getInfoOrder();
        return $this->json($data,200);
    }

    /**
     * @Route("/order/tax/set", name="rest_order_tax_set",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function setTax(Request $request): Response
    {
        $result =  $this->orderService->setTax((int) $request->get('id'));

        if (!$result){
            return $this->json(null,200);
        }

        return $this->json($result,200);
    }


    /**
     * @Route("/order/validate", name="rest_order_validate",methods={"GET","POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function validateOrder(Request $request,
                                  EntityManagerInterface $entityManager): Response
    {

        $dateStock = new DateTime($request->get('stockDate')) ?? new DateTime();

        $amountSended = (!$this->setting->getWithSettlement() && $request->get('amountSended') === null)?$this->orderService->getTotalWithTax()
            : (float) $request->get('amountSended');

        $supplier = $this->supplierRepository
            ->find((int) $request->get('supplier'));

        $user = $this->userRepository
            ->find((int) $request->get('userId'));


        $stock = new Stock();
        $stock->setAmount($this->orderService->getTotalWithTax());
        $stock->setAmountSended($amountSended);
        $stock->setAddDate($dateStock);
        $stock->setTaxAmount($this->orderService->getTotalTax());
        $stock->setSupplier($supplier);
        $stock->setRecorder($user);

        foreach ($this->orderService->getTaxs() as $tax){
            $stock->addTax($tax);
        }

        $entityManager->persist($stock);

        $orderProducts = $this->orderService->getFullOrder();

        foreach ($orderProducts as $item){
            $productStock = new ProductStock();
            $productStock->setProduct($item['product']);
            $productStock->setQty($item['qty']);
            $productStock->setStock($stock);
            $productStock->setUnitPrice($item['price']);
            $entityManager->persist($productStock);
        }
        $entityManager->flush();


        // clear a order
        $this->orderService->removeAll();

        $data = $this->getInfoOrder();
        return $this->json($data,200);

    }

    private function getInfoOrder(): Response
    {
        $model['items'] = $this->orderService->getFullOrder();
        $model['total'] = $this->orderService->getTotal();
        $model['totalWithTax'] = $this->orderService->getTotalWithTax();
        $model['taxOrder'] = $this->orderService->getTaxs();
        $model['suppliers'] = $this->supplierRepository->findAll();
        $model['taxs'] = $this->taxRepository->findBy(['status' => true]);

        return $this->render('partials/order.html.twig',$model);
    }

    /**
     * @Route("/stock/product", name="rest_stock_product",methods={"POST"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function getProduct(Request $request,
                                ProductRepository $productRepository): Response
    {

        $model['product'] = (new ProductDto())::createFromEntity($productRepository
            ->find((int) $request->get('id')));

        return $this->json($model,200);
    }
}
