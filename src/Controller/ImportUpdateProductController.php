<?php

namespace App\Controller;


use App\Entity\Product;

use App\Entity\ProductUpdateImport;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Util\GlobalConstant;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImportUpdateProductController extends AbstractController
{
    /**
     * @Route("/product/update/import",name="product_update_import",methods={"GET","POST"})
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

        $model['keys'] = array_keys(get_class_vars(ProductUpdateImport::class));
        if ($request->getMethod() === 'POST') {
            if ($request->get('display')) {
                if (!isset($_FILES['file'])) {
                    $this->addFlash('danger', "controller.import.flash.danger.fileNotExist");
                    return $this->redirectToRoute('product_update_import');
                }

                $fields = array_filter($request->get('fields'), static function ($field) {
                    return $field !== 'empty';
                });
                if (empty($fields)) {
                    $this->addFlash('danger', "controller.import.flash.danger.columnNotSelected");
                    return $this->redirectToRoute('product_update_import');
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
                    return $this->redirectToRoute('product_update_import');
                }

                $file = $request->files->get('file');
                $extensionAvailables = ['csv', 'xls', 'xlsx'];
                if (!in_array(GlobalConstant::getExtension($file->getClientOriginalName()), $extensionAvailables, true)) {
                    $this->addFlash('danger', "controller.import.flash.danger.typeFileIncorrect");
                    return $this->redirectToRoute('product_update_import');
                }

                $selectedfields = array_values($fields);
                foreach ($selectedfields as $field) {
                    $model[$field . 'Exist'] = array_search($field, $model['keys'], true);
                }

                $model['products'] = $this->getProductOfImportFile($request,'product', $logger);

                if (empty($model['products']) || $model['products'] instanceof RedirectResponse) {
                    $this->addFlash('danger', "controller.import.flash.danger.errorFile");
                    return $this->redirectToRoute('product_update_import');
                }

                $model['categories'] = $productCategoryRepository->findBy([], ['name' => 'DESC']);

                $session->set('products', $model['products']);
            }

            if ($request->get('validImport')) {
                $products = $session->get('products', []);

                $this->transferInDatabase($entityManager,$productRepository, $products);

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
            return $this->redirectToRoute('product_update_import');
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
            $product = new ProductUpdateImport();

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

    private function transferInDatabase(EntityManagerInterface $entityManager,
                                        ProductRepository $productRepository,
                                        array $products): void
    {

        /**
         * @var ProductUpdateImport[] $products
         */
        foreach ($products as $productImport) {
            if (!empty($productImport->barcode)){
                $productSearch = $productRepository
                    ->findOneBy(['qrCode' => $productImport->barcode]);
            }else{
                $productSearch = $productRepository
                    ->findOneBy(['name' => $productImport->name]);
            }

            if ($productSearch === null) {
                $productSearch = new Product();
                $productSearch->setName($productImport->name);
                $productSearch->setStockAlert($productImport->stockAlert);
                $productSearch->setBuyPrice($productImport->buyPrice);
                $productSearch->setSellPrice($productImport->sellPrice);

                if ($productImport->barcode !== ''){
                    $productSearch->setQrCode($productImport->barcode);
                }


            }else{
                $productSearch->setName($productImport->name);
                $productSearch->setStockAlert($productImport->stockAlert);
                $productSearch->setBuyPrice($productImport->buyPrice);
                $productSearch->setSellPrice($productImport->sellPrice);

                if ($productImport->barcode !== ''){
                    $productSearch->setQrCode($productImport->barcode);
                }
            }

            $entityManager->persist($productSearch);
        }

        $entityManager->flush();

    }


}
