<?php

namespace App\Controller;


use App\Entity\ProductCategory;
use App\Entity\Product;
use App\Entity\ProductImport;
use App\Entity\ProductPackaging;
use App\Entity\ProductStockImport;
use App\Entity\RawMaterial;
use App\Entity\RawMaterialImport;
use App\Entity\Setting;
use App\Entity\Stock;
use App\Entity\ProductStock;
use App\Entity\Supply;
use App\Entity\SupplyRaw;
use App\Entity\SupplyRawImport;
use App\Extension\AppExtension;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductPackagingRepository;
use App\Repository\ProductRepository;
use App\Repository\RawMaterialRepository;
use App\Repository\SupplierRepository;
use App\Util\GlobalConstant;
use App\Util\ModuleConstant;
use App\Util\RandomUtil;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{
    /**
     * @var AppExtension
     */
    private $appExtension;
    /**
     * @var Setting
     */
    private $setting;

    /**
     * ImportController constructor.
     * @param AppExtension $appExtension
     * @param RequestStack $requestStack
     */
    public function __construct(AppExtension $appExtension,
                                RequestStack $requestStack)
    {
        $this->appExtension = $appExtension;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/product/import",name="product_import",methods={"GET","POST"})
     * @param Request $request
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param ProductCategoryRepository $productCategoryRepository
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     * @throws \Exception
     */
    public function importProduct(Request $request,
                                  SessionInterface $session,
                                  EntityManagerInterface $entityManager,
                                  ProductCategoryRepository $productCategoryRepository,
                                  LoggerInterface $logger,
                                  ProductRepository $productRepository): Response
    {

        $model['keys'] = array_keys(get_class_vars(ProductImport::class));
        if ($request->getMethod() === 'POST') {
            if ($request->get('display')) {
                if (!isset($_FILES['file'])) {
                    $this->addFlash('danger', "controller.import.flash.danger.fileNotExist");
                    return $this->redirectToRoute('product_import');
                }

                $fields = array_filter($request->get('fields'), static function ($field) {
                    return $field !== 'empty';
                });
                if (empty($fields)) {
                    $this->addFlash('danger', "controller.import.flash.danger.columnNotSelected");
                    return $this->redirectToRoute('product_import');
                }

                $occurences = array_count_values($fields);
                $fieldError = false;
                foreach ($occurences as $occurence) {
                    if ($occurence >= 2) {
                        $fieldError = true;
                        break;
                    }
                }
                if ($fieldError) {
                    $this->addFlash('danger', "controller.import.flash.danger.fieldSelectManyTimes");
                    return $this->redirectToRoute('product_import');
                }

                $file = $request->files->get('file');
                $extensionAvailables = ['csv', 'xls', 'xlsx'];
                if (!in_array(GlobalConstant::getExtension($file->getClientOriginalName()), $extensionAvailables, true)) {
                    $this->addFlash('danger', "controller.import.flash.danger.typeFileIncorrect");
                    return $this->redirectToRoute('product_import');
                }

                $selectedfields = array_values($fields);
                foreach ($selectedfields as $field) {
                    $model[$field . 'Exist'] = array_search($field, $model['keys'], true);
                }

                $model['products'] = $this->getProductOfImportFile($request,'product', $logger);

                if (empty($model['products']) || $model['products'] instanceof RedirectResponse) {
                    $this->addFlash('danger', "controller.import.flash.danger.errorFile");
                    return $this->redirectToRoute('product_import');
                }

                $model['categories'] = $productCategoryRepository->findBy([], ['name' => 'DESC']);

                $session->set('products', $model['products']);
            }

            if ($request->get('validImport')) {
                $products = $session->get('products', []);
                $categoryId = $request->get('category');

                $this->transferInDatabase($entityManager, $productCategoryRepository,
                    $productRepository, $products, $categoryId);

                $this->addFlash('success', "controller.import.flash.success.transfer");
            }

            if ($request->get('cancel')) {
                $session->remove('products');
            }
        }


        //breadcumb
        $model['entity'] = 'controller.import.entity';
        $model['page'] = 'controller.import.page';

        return $this->render('product/import.html.twig', $model);
    }

    /**
     * @Route("/stock/import",name="stock_import",methods={"GET","POST"})
     * @param Request $request
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param SupplierRepository $supplierRepository
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     * @throws \Exception
     */
    public function importStock(Request $request,
                                SessionInterface $session,
                                EntityManagerInterface $entityManager,
                                SupplierRepository $supplierRepository,
                                LoggerInterface $logger,
                                ProductRepository $productRepository): Response
    {

        $model['keys'] = array_keys(get_class_vars(ProductStockImport::class));
        if ($request->getMethod() === 'POST') {
            if ($request->get('display')) {
                if (!isset($_FILES['file'])) {
                    $this->addFlash('danger', "controller.import.flash.danger.fileNotExist");
                    return $this->redirectToRoute('stock_import');
                }

                $fields = array_filter($request->get('fields'), static function ($field) {
                    return $field !== 'empty';
                });
                if (empty($fields)) {
                    $this->addFlash('danger', "controller.import.flash.danger.columnNotSelected");
                    return $this->redirectToRoute('stock_import');
                }

                $occurences = array_count_values($fields);
                $fieldError = false;
                foreach ($occurences as $occurence) {
                    if ($occurence >= 2) {
                        $fieldError = true;
                        break;
                    }
                }
                if ($fieldError) {
                    $this->addFlash('danger', "controller.import.flash.danger.fieldSelectManyTimes");
                    return $this->redirectToRoute('stock_import');
                }

                $file = $request->files->get('file');
                $extensionAvailables = ['csv', 'xls', 'xlsx'];
                if (!in_array(GlobalConstant::getExtension($file->getClientOriginalName()), $extensionAvailables, true)) {
                    $this->addFlash('danger', "controller.import.flash.danger.typeFileIncorrect");
                    return $this->redirectToRoute('stock_import');
                }

                $selectedfields = array_values($fields);
                foreach ($selectedfields as $field) {
                    $model[$field . 'Exist'] = array_search($field, $model['keys'], true);
                }

                $model['products'] = $this->getProductOfImportFile($request,'stock', $logger);

                if (empty($model['products']) || $model['products'] instanceof RedirectResponse) {
                    $this->addFlash('danger', "controller.import.flash.danger.errorFile");
                    return $this->redirectToRoute('stock_import');
                }

                $model['suppliers'] = $supplierRepository->findBy([], ['name' => 'DESC']);

                $session->set('stock_products', $model['products']);
            }

            if ($request->get('validImport')) {
                $products = $session->get('stock_products', []);

                $this->transferStockInDatabase($entityManager, $productRepository,
                    $supplierRepository, $products, $request);

                $this->addFlash('success', "controller.import.flash.success.transfer");
            }

            if ($request->get('cancel')) {
                $session->remove('products');
            }
        }


        //breadcumb
        $model['entity'] = 'controller.stock.import.entity';
        $model['page'] = 'controller.import.page';

        return $this->render('stock/import.html.twig', $model);
    }


    /**
     * @param Request $request
     * @param $typeFile
     * @param LoggerInterface $logger
     * @return array|RedirectResponse
     * @throws Exception
     */
    private function getProductOfImportFile(Request $request,
                                            $typeFile,
                                            LoggerInterface $logger)
    {

        $file = $request->files->get('file');
        $hasFirstRow = $request->get('hasFirstRow');
        $fields = $request->get('fields');

        $fileFolder = $this->getParameter('upload_dir');
        $filePathName = $typeFile.'xlsx';

        try {
            $file->move($fileFolder, $filePathName);
        } catch (FileException $e) {
            $logger->error($e->getFile() . ':' . $e->getMessage());
            $this->addFlash('danger', "controller.import.flash.danger.errorMoveFile");
            return $this->redirectToRoute('product_import');
        }
        $spreadsheet = IOFactory::load($fileFolder . $filePathName);
        $activeSheet = $spreadsheet->getActiveSheet();

        // remove first row
        if ($hasFirstRow) {
            $activeSheet->removeRow(1);
        }

        //recover data in a table
        $sheetData = $activeSheet
            ->toArray(null, true, true, true);

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];


        $products = [];
        foreach ($sheetData as $data) {
            $product = ($typeFile === 'product') ? new ProductImport()
                : new ProductStockImport();

            $i = 0;
            foreach ($fields as $field) {
                if ($field !== 'empty') {
                    $type = gettype($product->$field);
                    if (empty($data[$letters[$i]]) || $data[$letters[$i]] === null) {
                        $data[$letters[$i]] = GlobalConstant::getValueIfEmpty($type);
                    } else {
                        $data[$letters[$i]] = GlobalConstant::getValueByType($data[$letters[$i]], $type);
                    }

                    $product->$field = $data[$letters[$i]];
                }

                $i++;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProductCategoryRepository $productCategoryRepository
     * @param ProductRepository $productRepository
     * @param array $products
     * @param null $categoryId
     * @throws \Exception
     */
    private function transferInDatabase(EntityManagerInterface $entityManager,
                                        ProductCategoryRepository $productCategoryRepository,
                                        ProductRepository $productRepository,
                                        array $products, $categoryId = null): void
    {

        $order = new Stock();
        $order->setRecorder($this->getUser());
        $order->setStatus(true);
        $order->setAddDate(new DateTime());
        $order->setDeliveryDate(new DateTime());
        $order->setInitial(true);
        $entityManager->persist($order);

        $amount = 0;
        $nbProductStock = 0;

        $categories = [];
        $categoriesPersisted = [];

        /**
         * @var ProductImport[] $products
         */
        foreach ($products as $productImport) {
            if (!empty($productImport->name)) {
                if ($categoryId === null) {

                    if (!empty($productImport->category)) {
                        $category = $productCategoryRepository
                            ->findOneBy(['name' => strtolower($productImport->category)]);

                        if ($category !== null) {
                            $productSearch = $productRepository
                                ->getByNameAndCategory($productImport->name, $category);
                        } else {
                            $productSearch =
                                $productRepository->findOneBy(['name' => $productImport->name]);
                        }
                    } else {
                        $productSearch =
                            $productRepository->findOneBy(['name' => $productImport->name]);

                        if (($productSearch !== null) && $productSearch->getCategory() !== null) {
                            $productSearch = null;
                        }

                    }
                } else {
                    $category = $productCategoryRepository
                        ->find((int)$categoryId);
                    if ($category !== null) {
                        $productSearch = $productRepository
                            ->getByNameAndCategory($productImport->name, $category);
                    } else {
                        $productSearch =
                            $productRepository->findOneBy(['name' => $productImport->name]);

                        if (($productSearch !== null) && $productSearch->getCategory() !== null) {
                            $productSearch = null;
                        }
                    }
                }

                if ($productSearch === null) {
                    if (($categoryId === null) && !empty($productImport->category)) {
                        $productCategory = $productCategoryRepository
                            ->findOneBy(['name' => strtolower($productImport->category)]);

                        if ($productCategory === null) {
                            if (!in_array(strtolower($productImport->category), $categories, true)) {
                                $categories[] = strtolower($productImport->category);
                                $productCategory = new ProductCategory();
                                $productCategory->setName(strtolower($productImport->category));
                                $entityManager->persist($productCategory);
                                $categoriesPersisted[] = $productCategory;
                            } else {
                                foreach ($categoriesPersisted as $categoryPersisted) {
                                    if ($categoryPersisted->getName() === strtolower($productImport->category)) {
                                        $productCategory = $categoryPersisted;
                                    }
                                }
                            }
                        }
                    } else {
                        $productCategory = $productCategoryRepository
                            ->find((int)$categoryId);
                    }

                    $product = new Product();
                    $product->setName($productImport->name);
                    $product->setStockAlert($productImport->stockAlert);
                    $product->setBuyPrice($productImport->buyPrice);
                    $product->setSellPrice($productImport->sellPrice);

                    if ($this->setting->getWithBarcode() &&
                        $this->setting->getGenerateBarcode()) {
                        $randomNumber = RandomUtil::randomNumber($this->setting->getRandomCharacter());
                        $product->setQrCode($randomNumber);
                    }else if ($productImport->barcode !== ''){
                        $product->setQrCode($productImport->barcode);
                    }

                    if ($productImport->reference !== '' && $this->setting->getWithProductReference()) {
                        $product->setReference($productImport->reference);
                    }

                    /*if ($productImport->packaging !== '') {
                        $packagingRepository = $entityManager
                            ->getRepository(ProductPackaging::class);
                        $packaging = $packagingRepository
                            ->findOneBy(['name'=> $productImport->packaging]);

                        $product->setPackaging($packaging);
                    }*/

                    if (is_numeric((int)$productImport->packagingQty)) {
                        $product->setPackagingQty((int) $productImport->packagingQty);
                    }

                    if (!empty($productImport->category) || $categoryId !== null) {
                        $product->setCategory($productCategory);
                    }
                    $entityManager->persist($product);

                    if ($productImport->stock > 0) {
                        $nbProductStock++;
                        $amount += $productImport->buyPrice * $productImport->stock;
                        $productStock = new ProductStock();
                        $productStock->setQty($productImport->stock);
                        $productStock->setUnitPrice($productImport->buyPrice);
                        $productStock->setStock($order);
                        $productStock->setProduct($product);
                        $entityManager->persist($productStock);
                    }
                }
            }
        }

        if ($nbProductStock > 0) {
            $order->setAmount($amount);
            $entityManager->persist($order);
        } else {
            //$entityManager->clear(ProductStock::class);
            $entityManager->clear();
        }

        $entityManager->flush();

    }


    /**
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param SupplierRepository $supplierRepository
     * @param array $products
     * @param Request $request
     * @throws \Exception
     */
    private function transferStockInDatabase(EntityManagerInterface $entityManager,
                                               ProductRepository $productRepository,
                                               SupplierRepository $supplierRepository,
                                               array $products, Request $request): void
    {

        $order = new Stock();
        $order->setRecorder($this->getUser());
        $order->setNumInvoice($request->get('numInvoice'));
        $order->setNumBill($request->get('numBill'));
        $order->setStatus((int)$request->get('statut'));
        $order->setAddDate(new DateTime());
        $order->setDeliveryDate(new DateTime());

        $supplier = $supplierRepository->find((int)$request->get('supplier'));
        $order->setSupplier($supplier);
        $entityManager->persist($order);

        $amount = 0;
        $nbProductStock = 0;

        /**
         * @var ProductStockImport[] $products
         */
        foreach ($products as $productImport) {
            if (!empty($productImport->name)) {
                $productSearch = $productRepository
                    ->findOneBy(['name'=>$productImport->name]);

                if (($productSearch !== null) && $productImport->qty > 0) {
                    $nbProductStock++;
                    $amount += $productImport->buyPrice * $productImport->qty;
                    $productStock = new ProductStock();
                    $productStock->setQty($productImport->qty);
                    $productStock->setUnitPrice($productImport->buyPrice);
                    $productStock->setStock($order);
                    $productStock->setProduct($productSearch);
                    $entityManager->persist($productStock);
                }
            }
        }

        if ($nbProductStock > 0) {
            $order->setAmount($amount);
            $entityManager->persist($order);
        } else {
            //$entityManager->clear(ProductStock::class);
            $entityManager->clear();
        }

        $entityManager->flush();

    }
}
