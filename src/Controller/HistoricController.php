<?php

namespace App\Controller;


use App\Repository\ConnectionRepository;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HistoricController extends AbstractController
{

    /**
     * @Route("/connection",name="historic_index",methods={"GET","POST"})
     * @param Request $request
     * @param ConnectionRepository $connectionRepository
     * @return Response
     * @throws Exception
     */
    public function performance(Request $request,
                                ConnectionRepository $connectionRepository): Response
    {

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['connections'] = $connectionRepository
            ->findByPeriodAndUser($model['start'],$model['end']);

        //breadcumb
        $model['entity'] = 'controller.historic.entity';
        $model['page'] = 'controller.historic.page';

        return $this->render('historic/index.html.twig',$model);
    }

}
