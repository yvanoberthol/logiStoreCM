<?php


namespace App\Controller;


use App\Entity\ProductPackaging;
use App\Entity\Setting;
use App\Extension\AppExtension;
use App\Form\ProductPackagingType;
use App\Repository\ProductPackagingRepository;
use App\Util\ModuleConstant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductPackagingController extends AbstractController
{
    /**
     * @var Setting
     */
    private $setting;

    /**
     * BankController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->setting = $requestStack->getSession()->get('setting');
    }

    /**
     * @Route("/productPackaging", name="product_packaging_index")
     * @param ProductPackagingRepository $productPackagingRepository
     * @return Response
     */
    public function index(ProductPackagingRepository $productPackagingRepository)
    {
        if (!$this->setting->getWithPackaging()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $model['productPackagings'] = $productPackagingRepository->findBy([],['name' => 'DESC']);
        //breadcumb
        $model['entity'] = 'controller.productPackaging.index.entity';
        $model['page'] = 'controller.productPackaging.index.page';
        return $this->render('productPackaging/index.html.twig', $model);
    }

    /**
     * @Route("/productPackaging/new", name="product_packaging_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithPackaging()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $productPackaging = new ProductPackaging();
        $form = $this->createForm(ProductPackagingType::class,$productPackaging);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($productPackaging);
            $entityManager->flush();
            return $this->redirectToRoute('product_packaging_index');
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.productPackaging.new.entity';
        $model['page'] = 'controller.productPackaging.new.page';
        return $this->render('productPackaging/new.html.twig',$model);
    }


    /**
     * @Route("/productPackaging/edit/{id}", name="product_packaging_edit")
     * @param ProductPackaging $productPackaging
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(ProductPackaging $productPackaging, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithPackaging()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $form = $this->createForm(ProductPackagingType::class,$productPackaging);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($productPackaging);
                $entityManager->flush();
                return $this->redirectToRoute('product_packaging_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.productPackaging.edit.entity';
        $model['page'] = 'controller.productPackaging.edit.page';
        return $this->render('productPackaging/edit.html.twig',$model);
    }

    /**
     * @Route("/productPackaging/delete/{id}", name="product_packaging_delete")
     * @param ProductPackaging $productPackaging
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ProductPackaging $productPackaging, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithPackaging()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $entityManager->remove($productPackaging);
        $entityManager->flush();
        return $this->redirectToRoute('product_packaging_index');
    }

}
