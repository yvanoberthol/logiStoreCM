<?php


namespace App\Controller;


use App\Entity\Product;
use App\Entity\ProductPrice;
use App\Entity\CustomerProductPrice;
use App\Entity\Setting;
use App\Form\ProductType;
use App\Repository\CustomerRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductPriceRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductStockRepository;
use App\Repository\SaleRepository;
use App\Repository\SettingRepository;
use App\Repository\CustomerProductPriceRepository;
use App\Repository\SupplierRepository;
use App\Service\ProductService;
use App\Util\CustomerTypeConstant;
use App\Util\GlobalConstant;
use App\Util\RandomUtil;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
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
     * @Route("/product", name="product_index")
     * @param Request $request
     * @param ProductCategoryRepository $productCategoryRepository
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function index(Request $request,
                          ProductCategoryRepository $productCategoryRepository,
                          ProductRepository $productRepository,
                          ProductService $productService)
    {
        if (empty($request->get('category'))){
            $products = $productRepository
                ->findBy([],['addDate' => 'DESC']);
        }else{
            $category = $productCategoryRepository
                ->find((int) $request->get('category'));

            if ($category === null){
                $products = $productRepository
                    ->findBy([],['addDate' => 'DESC']);
            }else{
                $model['categorySearch'] = $category;
                $products = $productRepository
                    ->findBy(['category' => $category],['addDate' => 'DESC']);
            }
        }

        $model['categories'] = $productCategoryRepository->findAll();

        if ($request->get('status') === 'byStockAlert'){
            $model['search'] = 'byStockAlert';
            $model['products'] = $productService->getProductByStockAlert($products);
        }elseif ($request->get('status') === 'byNew'){
                $model['search'] = 'byNew';
                $model['products'] = array_filter($productService->countStocks($products),
                    static function (Product $product){
                    return $product->isNew();
                });
        }elseif ($request->get('status') === 'byOutOfStock'){
            $model['search'] = 'byOutOfStock';
            $model['products'] = $productService->getProductByOutOfStock($products);
        }else{
            $model['search'] = 'all';
            $model['products'] = $productService->countStocks($products);
        }

        //breadcumb
        $model['entity'] = 'controller.product.index.entity';
        $model['page'] = 'controller.product.index.page';
        return $this->render('product/index.html.twig', $model);
    }

    /**
     * @Route("/productPrice", name="product_price")
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function productPrice(ProductRepository $productRepository)
    {

        if (!$this->setting->getWithBatchPrice()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['products'] = $productRepository->findAll();

        //breadcumb
        $model['entity'] = 'controller.product.index.entity';
        $model['page'] = 'controller.product.index.page';
        return $this->render('product/productWithWholePrice.html.twig', $model);
    }


    /**
     * @Route("/product/expiryDate", name="product_expiry_date")
     * @param Request $request
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function expiryDate(Request $request,
                               ProductService $productService): Response
    {
        if (!$this->setting->getWithExpiration()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['dayBefore'] = $request->get('dayBefore') ?? $this->setting->getDaysBeforeExpiration();

        $model['productStocks'] = $productService
            ->getProductStockNearExpirationDate((int) $model['dayBefore']);

        //breadcumb
        $model['entity'] = 'controller.product.expiryDate.entity';
        $model['page'] = 'controller.product.expiryDate.page';
        return $this->render('product/expiryDate.html.twig', $model);
    }

    /**
     * @Route("/product/outOfDate", name="product_out_of_date")
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function outOfDate(Request $request,
                              ProductStockRepository $productStockRepository,
                              ProductService $productService): Response
    {

        if (!$this->setting->getWithExpiration()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($request->get('status') !== 'withdraw'){
            $model['search'] = 'current';
            $model['productStocks'] = $productService
                ->getProductStockOutdated(false);
        }else{
            $model['search'] = 'withdraw';
            $model['productStocks'] = $productStockRepository
                ->findBy(['withdraw' => true]);
        }

        //breadcumb
        $model['entity'] = 'controller.product.outOfDate.entity';
        $model['page'] = 'controller.product.outOfDate.page';
        return $this->render('product/outOfDate.html.twig', $model);
    }


    /**
     * @Route("/product/new", name="product_new")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, ProductRepository $productRepository,
                        EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class,$product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $productName = $productRepository
                ->getByNameAndCategory($product->getName(),$product->getCategory());

            if ($productName === null){
                if ($this->setting->getWithBarcode() && $this->setting->getGenerateBarcode()){
                    do{
                        $barcode = RandomUtil::randomNumber($this->setting->getRandomCharacter());
                        $productWithQrCode = $productRepository->findBy(['qrCode' => $barcode]);
                    } while ($productWithQrCode !== null);
                    $product->setQrCode($barcode);
                }

                $product->setBuyPrice(abs($product->getBuyPrice()));
                $product->setSellPrice(abs($product->getSellPrice()));
                $product->setWholePrice(abs($product->getWholePrice()));
                $product->setStockAlert(abs($product->getStockAlert()));

                $entityManager->persist($product);
                $entityManager->flush();
                $this->addFlash('success',"controller.product.new.flash.success");
            }else{
                $this->addFlash('success',"controller.product.new.flash.danger");
            }

        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.product.new.entity';
        $model['page'] = 'controller.product.new.page';
        return $this->render('product/new.html.twig',$model);
    }


    /**
     * @Route("/product/edit/{id}", name="product_edit")
     * @param Product $product
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function edit(Product $product, Request $request,
                         ProductRepository $productRepository,
                         EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(ProductType::class,$product);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){

                if ($this->setting->getWithBarcode() && $this->setting->getGenerateBarcode()
                    && $product->getQrCode() === null){

                    do{
                        $barcode = RandomUtil::randomNumber($this->setting->getRandomCharacter());
                        $productWithQrCode = $productRepository->findBy(['qrCode' => $barcode]);
                    } while ($productWithQrCode !== null
                    && $productWithQrCode->getId() !== $product->getId());

                    $product->setQrCode($barcode);
                }

                $product->setBuyPrice(abs($product->getBuyPrice()));
                $product->setSellPrice(abs($product->getSellPrice()));
                $product->setWholePrice(abs($product->getWholePrice()));
                $product->setStockAlert(abs($product->getStockAlert()));
                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success',"controller.product.edit.flash.success");

                return $this->redirectToRoute('product_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.product.edit.entity';
        $model['page'] = 'controller.product.edit.page';
        return $this->render('product/edit.html.twig',$model);

    }

    /**
     * @Route("/product/delete/{id}", name="product_delete")
     * @param Product $product
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Product $product, EntityManagerInterface $entityManager): RedirectResponse
    {

        $this->denyAccessUnlessGranted('PRODUCT_DELETE',$product);
        $entityManager->remove($product);
        $entityManager->flush();
        $this->addFlash('success',"controller.product.delete.flash.success");
        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/product/changeStatus/{id}", name="product_change_status")
     * @param Product $product
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function changeStatus(Product $product, EntityManagerInterface $entityManager): RedirectResponse
    {
        $product->setEnabled(!$product->getEnabled());
        $entityManager->persist($product);
        $entityManager->flush();

        return $this->redirectToRoute('product_index');
    }



    /**
     * @Route("/product/detail/{id}", name="product_detail")
     * @param Product $product
     * @param Request $request
     * @param ProductService $productService
     * @param ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function detail(Product $product,
                           Request $request,
                           ProductService $productService,
                           ProductRepository $productRepository,
                           CustomerRepository $customerRepository,
                           SaleRepository $saleRepository): Response
    {

        $model = GlobalConstant::getMonthsAndYear($request);

        if ($request->get('productSearch')){
            $product = $productRepository->find((int) $request->get('productSearch'));
        }

        $model['product'] = $productService
            ->countStock($product);

        $model['products'] = $productRepository->findAll();

        $model['customers'] = $customerRepository
            ->findBy(['type' => CustomerTypeConstant::TYPEKEYS['Reseller']]);

        $model['saleStats'] = $saleRepository
            ->groupByDateProduct($model['product']);

        $model['salesYear'] = $saleRepository
            ->groupByYearProduct($model['product']);

        $model['productStockDispo'] = $productService
            ->getProductStockDispoByProduct($model['product']);

        $model['customerPrices'] = array_unique(array_map(static function(CustomerProductPrice $customerProductPrice){
            return $customerProductPrice->getPrice();
        },$model['product']->getCustomerProductPrices()->toArray()));

        //breadcumb
        $model['entity'] = 'controller.product.detail.entity';
        $model['page'] = 'controller.product.detail.page';

        return $this->render('product/detailProduct.html.twig',$model);
    }

    /**
     * @Route("/product/addSubstitute", name="product_add_substitute")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addSubstitute(Request $request,
                                 ProductRepository $productRepository,
                                 EntityManagerInterface $entityManager): Response
    {

        $product = $productRepository->find($request->get('product'));
        $substitute = $productRepository->find($request->get('substitute'));
        if ($product !== null && $substitute !== null){
            $product->addSubstitute($substitute);
            $substitute->addSubstitute($product);

            $entityManager->persist($product);
            $entityManager->persist($substitute);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_detail',['id' => $product->getId()]);
    }

    /**
     * @Route("/product/removeSubstitute", name="product_remove_substitute")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function removeSubstitute(Request $request,
                                 ProductRepository $productRepository,
                                 EntityManagerInterface $entityManager): Response
    {

        $product = $productRepository->find($request->get('product'));
        $substitute = $productRepository->find($request->get('substitute'));
        if ($product !== null && $substitute !== null){
            $product->removeSubstitute($substitute);
            $substitute->removeSubstitute($product);
            $entityManager->persist($product);
            $entityManager->persist($substitute);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_detail',['id' => $product->getId()]);
    }

    /**
     * @Route("/product/addPrice", name="product_add_price")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param ProductPriceRepository $productPriceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addPrice(Request $request,
                                  ProductRepository $productRepository,
                                  ProductPriceRepository $productPriceRepository,
                                  EntityManagerInterface $entityManager): Response
    {

        $product = $productRepository->find((int) $request->get('product'));
        $productPrice = $productPriceRepository
            ->findOneBy(['qty'=>(int) $request->get('qty'), 'product' => $product]);

        if ($product !== null){
            if ($productPrice === null){
                $productPrice = new ProductPrice();
                $productPrice->setQty((int) $request->get('qty'));
                $productPrice->setUnitPrice((int) $request->get('unitPrice'));
                $productPrice->setProduct($product);
            }else{
                $productPrice->setUnitPrice((int) $request->get('unitPrice'));
            }

            $entityManager->persist($productPrice);
            $entityManager->flush();

            return $this->redirectToRoute('product_detail',['id' => $product->getId()]);
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/product/removePrice", name="product_remove_price")
     * @param Request $request
     * @param ProductPriceRepository $productPriceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function removePrice(Request $request,
                                     ProductPriceRepository $productPriceRepository,
                                     EntityManagerInterface $entityManager): Response
    {

        $productPrice = $productPriceRepository->find((int) $request->get('id'));
        if ($productPrice !== null){
            $productId = $productPrice->getProduct()->getId();
            $entityManager->remove($productPrice);
            $entityManager->flush();

            return $this->redirectToRoute('product_detail',['id' => $productId]);
        }

        return $this->redirectToRoute('product_index');
    }


    /**
     * @Route("/product/addCustomerPrice", name="product_add_customer_price")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param CustomerProductPriceRepository $customerProductPriceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addCustomerPrice(Request $request,
                                     ProductRepository $productRepository,
                                     CustomerRepository $customerRepository,
                                     CustomerProductPriceRepository $customerProductPriceRepository,
                                     EntityManagerInterface $entityManager): Response
    {

        $product = $productRepository->find((int) $request->get('product'));

        $customers = [];
        foreach ($request->get('customer') as $customerId) {
            $customer = $customerRepository->find((int) $customerId);
            if ($customer !== null) {
                $customers[] = $customer;
            }
        }

        if ($product !== null && !empty($customers)){
            foreach ($customers as $customer){
                $customerProductPrice = $customerProductPriceRepository
                    ->findOneBy(['product' => $product,'customer' => $customer]);

                if ($customerProductPrice === null){
                    $customerProductPrice = new CustomerProductPrice();
                    $customerProductPrice->setPrice((float) $request->get('price'));
                    $customerProductPrice->setProduct($product);
                    $customerProductPrice->setCustomer($customer);
                }else{
                    $customerProductPrice->setPrice((float) $request->get('price'));
                }

                $entityManager->persist($customerProductPrice);
                $entityManager->flush();
            }

            return $this->redirectToRoute('product_detail',['id' => $product->getId()]);
        }


        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/product/removeCustomerPrice", name="product_remove_customer_price")
     * @param Request $request
     * @param CustomerProductPriceRepository $customerProductPriceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function removeCustomerPrice(Request $request,
                                        CustomerProductPriceRepository $customerProductPriceRepository,
                                        EntityManagerInterface $entityManager): Response
    {

        $customerProductPrice =
            $customerProductPriceRepository->find((int) $request->get('id'));
        if ($customerProductPrice !== null){
            $productId = $customerProductPrice->getProduct()->getId();
            $entityManager->remove($customerProductPrice);
            $entityManager->flush();

            return $this->redirectToRoute('product_detail',['id' => $productId]);
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/product/removeCustomerPrice/all", name="product_remove_customer_price_all")
     * @param Request $request
     * @param CustomerProductPriceRepository $customerProductPriceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function removeAllCustomerPrice(Request $request,
                                           CustomerProductPriceRepository $customerProductPriceRepository,
                                           EntityManagerInterface $entityManager): Response
    {

        $customerProductPrices =
            $customerProductPriceRepository->findBy(['price' =>(int) $request->get('price')]);


        $productId = empty($customerProductPrices)? null
            : $customerProductPrices[0]->getProduct()->getId();

        foreach ($customerProductPrices as $customerProductPrice){
            if ($customerProductPrice !== null){
                $entityManager->remove($customerProductPrice);
                $entityManager->flush();
            }
        }

        return ($productId !== null)? $this->redirectToRoute('product_detail',['id' => $productId])
            :$this->redirectToRoute('product_index');
    }


    /**
     * @Route("/product/printLabel", name="product_print_label")
     * @param Request $request
     * @param Pdf $pdf
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function printLabel(Request $request,
                               Pdf $pdf,
                               ProductRepository $productRepository): Response
    {

        if (!$this->setting->getWithBarcode()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['products'] = $productRepository->getWithBarCode();
        $model['size'] = 5;

        //breadcumb
        $model['entity'] = 'controller.product.printLabel.entity';
        $model['page'] = 'controller.product.printLabel.page';



        if ($request->getMethod() === 'POST'){
            $model['product'] = $productRepository
                ->find((int) $request->get('product'));

            $model['size'] = (int) $request->get('size');
            if ($request->get('display')){
                return $this->render('product/printLabel.html.twig',$model);
            }

            $html = $this->renderView('pdf/printLabel.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', 297);
            $pdf->setOption('page-width', 210);
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        return $this->render('product/printLabel.html.twig',$model);

    }

}
