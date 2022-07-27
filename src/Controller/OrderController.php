<?php

namespace App\Controller;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{


    /**
     * @Route("/order/add", name="order_add",methods={"POST"})
     * @param Request $request
     * @param OrderService $orderService
     * @return Response
     */
    public function add(Request $request, OrderService $orderService): Response
    {
        if ($request->get('add') !== null){
            $orderService->add($request->get('id'),$request->get('price'),$request->get('qty'));
        }else{
            $orderService->minus($request->get('id'),$request->get('qty'));
        }
        return $this->redirectToRoute('stock_new');
    }

    /**
     * @Route("/order/remove/{id}", name="order_remove")
     * @param $id
     * @param OrderService $orderService
     * @return Response
     */
    public function remove($id, OrderService $orderService): Response
    {
        $orderService->remove($id);
        return $this->redirectToRoute('stock_new');
    }

    /**
     * @Route("/order/removeAll", name="order_remove_all")
     * @param OrderService $orderService
     * @return Response
     */
    public function removeAll(OrderService $orderService): Response
    {
        $orderService->removeAll();
        return $this->redirectToRoute('stock_new');
    }
}
