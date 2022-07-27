<?php

namespace App\Controller;



use App\Service\ProductService;
use App\Util\GlobalConstant;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InventoryController extends AbstractController
{
    /**
     * @Route("/inventory",name="inventory_index",methods={"GET","POST"})
     * @param Request $request
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function index(Request $request,
                          ProductService $productService): Response
    {

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }
        $model['inventory'] = $productService
            ->getInventory($model['start'],$model['end']);

        //breadcumb
        $model['entity'] = 'controller.inventory.entity';
        $model['page'] = 'controller.inventory.page';

        return $this->render('inventory/index.html.twig',$model);
    }

}
