<?php


namespace App\Controller;


use App\Entity\ProductCategory;
use App\Form\ProductCategoryType;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductCategoryController extends AbstractController
{

    /**
     * @Route("/productCategory", name="product_category_index")
     * @param ProductCategoryRepository $productCategoryRepository
     * @return Response
     */
    public function index(ProductCategoryRepository $productCategoryRepository)
    {
        $model['productCategories'] = $productCategoryRepository->findBy([],['name' => 'DESC']);
        //breadcumb
        $model['entity'] = 'controller.productCategory.index.entity';
        $model['page'] = 'controller.productCategory.index.page';
        return $this->render('productCategory/index.html.twig', $model);
    }

    /**
     * @Route("/productCategory/new", name="product_category_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $productCategory = new ProductCategory();
        $form = $this->createForm(ProductCategoryType::class,$productCategory);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($productCategory);
            $entityManager->flush();
            return $this->redirectToRoute('product_category_index');
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.productCategory.new.entity';
        $model['page'] = 'controller.productCategory.new.page';
        return $this->render('productCategory/new.html.twig',$model);
    }


    /**
     * @Route("/productCategory/edit/{id}", name="product_category_edit")
     * @param ProductCategory $productCategory
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(ProductCategory $productCategory, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductCategoryType::class,$productCategory);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($productCategory);
                $entityManager->flush();
                return $this->redirectToRoute('product_category_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.productCategory.edit.entity';
        $model['page'] = 'controller.productCategory.edit.page';
        return $this->render('productCategory/edit.html.twig',$model);
    }

    /**
     * @Route("/productCategory/delete/{id}", name="product_category_delete")
     * @param ProductCategory $productCategory
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ProductCategory $productCategory, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($productCategory);
        $entityManager->flush();
        return $this->redirectToRoute('product_category_index');
    }

}
