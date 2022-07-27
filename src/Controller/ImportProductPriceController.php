<?php

namespace App\Controller;


use App\Entity\ProductCategory;
use App\Entity\Product;
use App\Entity\ProductImport;
use App\Entity\ProductPrice;
use App\Entity\ProductPriceImport;
use App\Entity\ProductStockImport;
use App\Entity\RawMaterial;
use App\Entity\RawMaterialImport;
use App\Entity\Supply;
use App\Entity\SupplyRaw;
use App\Entity\SupplyRawImport;
use App\Entity\Stock;
use App\Entity\ProductStock;
use App\Extension\AppExtension;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductPriceRepository;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImportProductPriceController extends AbstractController
{
    /**
     * @Route("/product/price/import",name="product_price_import",methods={"GET","POST"})
     * @param Request $request
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param ProductPriceRepository $productPriceRepository
     * @return Response
     * @throws Exception
     */
    public function importProductPrice(Request $request,
                                  SessionInterface $session,
                                  EntityManagerInterface $entityManager,
                                  LoggerInterface $logger,
                                  ProductRepository $productRepository,
                                  ProductPriceRepository $productPriceRepository): Response
    {

        $model['keys'] = array_keys(get_class_vars(ProductPriceImport::class));
        if ($request->getMethod() === 'POST') {
            if ($request->get('display')) {
                if (!isset($_FILES['file'])) {
                    $this->addFlash('danger', "controller.import.flash.danger.fileNotExist");
                    return $this->redirectToRoute('product_price_import');
                }

                $fields = array_filter($request->get('fields'), static function ($field) {
                    return $field !== 'empty';
                });
                if (empty($fields)) {
                    $this->addFlash('danger', "controller.import.flash.danger.columnNotSelected");
                    return $this->redirectToRoute('product_price_import');
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
                    return $this->redirectToRoute('product_price_import');
                }

                $file = $request->files->get('file');
                $extensionAvailables = ['csv', 'xls', 'xlsx'];
                if (!in_array(GlobalConstant::getExtension($file->getClientOriginalName()), $extensionAvailables, true)) {
                    $this->addFlash('danger', "controller.import.flash.danger.typeFileIncorrect");
                    return $this->redirectToRoute('product_price_import');
                }

                $selectedfields = array_values($fields);
                foreach ($selectedfields as $field) {
                    $model[$field . 'Exist'] = array_search($field, $model['keys'], true);
                }

                $model['productPrices'] = $this->getProductPriceOfImportFile($request,'product', $logger);

                if (empty($model['productPrices']) || $model['productPrices'] instanceof RedirectResponse) {
                    $this->addFlash('danger', "controller.import.flash.danger.errorFile");
                    return $this->redirectToRoute('product_price_import');
                }

                $session->set('productPrices', $model['productPrices']);
            }

            if ($request->get('validImport')) {
                $productPrices = $session->get('productPrices', []);

                $this->transferInDatabase($entityManager,$productRepository,$productPriceRepository, $productPrices);

                $this->addFlash('success', "controller.import.flash.success.transfer");
            }

            if ($request->get('cancel')) {
                $session->remove('productPrices');
            }
        }


        //breadcumb
        $model['entity'] = 'controller.importPrice.entity';
        $model['page'] = 'controller.importPrice.page';

        return $this->render('product/importPrice.html.twig', $model);
    }

    /**
     * @param Request $request
     * @param $typeFile
     * @param LoggerInterface $logger
     * @return array|RedirectResponse
     * @throws Exception
     */
    private function getProductPriceOfImportFile(Request $request,
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
            return $this->redirectToRoute('product_price_import');
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


        $productPrices = [];
        foreach ($sheetData as $data) {
            $productPrice = new ProductPriceImport();

            $i = 0;
            foreach ($fields as $field) {
                if ($field !== 'empty') {
                    $type = gettype($productPrice->$field);
                    if (empty($data[$letters[$i]]) || $data[$letters[$i]] === null) {
                        $data[$letters[$i]] = GlobalConstant::getValueIfEmpty($type);
                    } else {
                        $data[$letters[$i]] = GlobalConstant::getValueByType($data[$letters[$i]], $type);
                    }

                    $productPrice->$field = $data[$letters[$i]];
                }

                $i++;
            }

            $productPrices[] = $productPrice;
        }

        return $productPrices;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param ProductPriceRepository $productPriceRepository
     * @param array $products
     */
    private function transferInDatabase(EntityManagerInterface $entityManager,
                                        ProductRepository $productRepository,
                                        ProductPriceRepository $productPriceRepository,
                                        array $products): void
    {

        /**
         * @var ProductPriceImport[] $products
         */
        foreach ($products as $productPriceImport) {

            if (!empty($productPriceImport->barcode)){
                $productSearch = $productRepository
                    ->findOneBy(['qrCode' => $productPriceImport->barcode]);
            }else{
                $productSearch = $productRepository
                    ->findOneBy(['name' => $productPriceImport->name]);
            }

            if (($productSearch !== null) && !empty($productPriceImport->qtys)
                && !empty($productPriceImport->prices)) {

                if (count($productSearch->getProductPrices()->toArray()) > 0){
                    foreach ($productSearch->getProductPrices() as $price){
                        $entityManager->remove($price);
                    }
                    $entityManager->flush();
                }

                $qtys = explode('_',$productPriceImport->qtys);
                $prices = explode('_',$productPriceImport->prices);

                if (count($qtys) === count($prices)){
                    for ($i = 0, $iMax = count($qtys); $i < $iMax; $i++){
                        $qty = (int) $qtys[$i];
                        if ($qty > 1){
                            $productPrice = $productPriceRepository
                                ->findOneBy(['qty'=>$qty,
                                    'product' => $productSearch]);

                            if ($productPrice === null) {
                                $productPrice = new ProductPrice();
                                $productPrice->setQty($qty);
                                $productPrice->setUnitPrice((float)$prices[$i]);
                                $productPrice->setProduct($productSearch);

                                $entityManager->persist($productPrice);
                            }
                        }
                    }
                }
            }
        }

        $entityManager->flush();
    }
}
