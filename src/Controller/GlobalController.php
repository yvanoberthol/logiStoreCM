<?php


namespace App\Controller;


use App\Repository\NoticeBoardEmployeeRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class GlobalController extends AbstractController
{

    /**
     * @Route("/imageLogo", name="imageLogo")
     * @param Request $request
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function imageLogo(Request $request, StoreRepository $storeRepository)
    {
        $model['height'] = $request->get('height') ?? 100;
        $model['width'] = $request->get('width') ?? 100;
        $model['store'] = (!empty($storeRepository->findAll()))? $storeRepository->get() : null;
        return $this->render('partials/logo.html.twig',$model);
    }


    /**
     * @Route("/notice", name="notice")
     * @param NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository
     * @return Response
     */
    public function notice(NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository)
    {
        $model['noticeEmployees'] = $noticeBoardEmployeeRepository
            ->findByUser($this->getUser(),false);
        return $this->render('partials/notice.html.twig',$model);
    }


    /**
     * @Route("/titleStore", name="titleStore")
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function titleStore(StoreRepository $storeRepository)
    {

        $model['store'] = (!empty($storeRepository->findAll()))?
            $storeRepository->get() : null;
        return $this->render('partials/titleStore.html.twig',$model);
    }

    /**
     * @Route("/linkStore", name="linkStore")
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function linkStore(StoreRepository $storeRepository)
    {
        $model['store'] = (!empty($storeRepository->findAll()))?
            $storeRepository->get() : null;
        return $this->render('partials/linklogo.html.twig',$model);
    }

    /**
     * @Route("/nbProduct", name="nbProduct")
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function nbProduct(ProductRepository $productRepository)
    {
        $model['nbProducts'] = $productRepository->count([]);
        return new Response($model['nbProducts']);
    }
}
