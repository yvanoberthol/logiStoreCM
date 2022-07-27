<?php

namespace App\Controller;

use App\Entity\LossType;
use App\Entity\PageSize;
use App\Entity\Setting;
use App\Entity\Theme;
use App\Form\LossTypeType;
use App\Repository\AddonsRepository;
use App\Repository\PageSizeRepository;
use App\Repository\SettingRepository;
use App\Repository\ThemeRepository;
use App\Service\BackupService;
use App\Service\ProductService;
use App\Util\EnvUtil;
use App\Util\SystemUtil;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SettingController extends AbstractController
{

    /**
     * @Route("/setting", name="setting_index")
     * @param SettingRepository $settingRepository
     * @param AddonsRepository $addonsRepository
     * @param ThemeRepository $themeRepository
     * @param PageSizeRepository $pageSizeRepository
     * @return Response
     */
    public function index(SettingRepository $settingRepository,
                          AddonsRepository $addonsRepository,
                          ThemeRepository $themeRepository,
                          PageSizeRepository $pageSizeRepository): Response
    {

        $model['setting'] = $settingRepository->get();
        $model['pageSizes'] = $pageSizeRepository->findAll();
        $model['addons'] = $addonsRepository->findAll();
        $model['themes'] = $themeRepository->findAll();

        $model['pdfSaleFormat'] = $pageSizeRepository
            ->findOneBy(['height' => $model['setting']->getSaleReceiptHeight(),
                'width' => $model['setting']->getSaleReceiptWidth()]);

        $model['pdfReportFormat'] = $pageSizeRepository
            ->findOneBy(['height' => $model['setting']->getReportHeight(),
                'width' => $model['setting']->getReportWidth()]);

        //breadcumb
        $model['entity'] = 'controller.setting.index.entity';
        $model['page'] = 'controller.setting.index.page';

        return $this->render('setting/index.html.twig', $model);
    }

    /**
     * @Route("/setting/update", name="setting_update", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     * @param ProductService $productService
     * @param PageSizeRepository $pageSizeRepository
     * @param ThemeRepository $themeRepository
     * @param SettingRepository $settingRepository
     * @return Response
     * @throws \Exception
     */
    public function update(Request $request,
                           EntityManagerInterface $entityManager,
                           SessionInterface $session,
                           ProductService $productService,
                           PageSizeRepository $pageSizeRepository,
                           ThemeRepository $themeRepository,
                           SettingRepository $settingRepository): Response
    {

        $setting = $settingRepository->get();

        if ($setting === null)
            throw new RuntimeException('not default setting');

        $themeSelected = null;

        switch ($request->get('tab')){
            case 'general':
                $withAccounting = $request->get('withAccounting') !== null;
                $withExpense = $request->get('withExpense') !== null;
                $withHrm = $request->get('withHrm') !== null;
                $withProduction = $request->get('withProduction') !== null;
                $withPoint = $request->get('withPoint') !== null;
                $setting->setWithAccounting($withAccounting);
                $setting->setWithExpense($withExpense);
                $setting->setWithHrm($withHrm);
                $setting->setWithProduction($withProduction);
                $setting->setWithClubPoint($withPoint);

                break;
            case 'currency':
                $setting->setCurrencyName($request->get('currencyName'));
                $setting->setCurrencySide($request->get('currencySide'));
                $setting->setCurrencyDecimal($request->get('currencyDecimal'));
                $setting->setCurrencyThousandSeparator($request->get('currencyThousandSeparator'));
                break;
            case 'date':
                $setting->setShortDate1($request->get('shortDate1'));
                $setting->setShortDate2($request->get('shortDate2'));

                $setting->setMediumDate1($request->get('mediumDate1'));
                $setting->setMediumDate2($request->get('mediumDate2'));
                $setting->setMediumDate3($request->get('mediumDate3'));

                $setting->setLongDate1($request->get('longDate1'));
                $setting->setLongDate2($request->get('longDate2'));
                $setting->setLongDate3($request->get('longDate3'));
                $setting->setLongDate4($request->get('longDate4'));
                $setting->setLongDate5($request->get('longDate5'));

                $setting->setDateSeparator($request->get('dateSeparator'));
                break;
            case 'product':
                $setting->setProductNew((int) $request->get('productNew'));
                $productWithImage = $request->get('productWithImage') !== null;
                $productWithExpiration = $request->get('productWithExpiration') !== null;
                $productWithReference = $request->get('productWithReference') !== null;
                $productWithBarcode = $request->get('productWithBarcode') !== null;
                $productWithGenerateBarcode = $request->get('productWithGenerateBarcode') !== null;
                $productWithDiscount = $request->get('productWithDiscount') !== null;
                $productWithSubstitute = $request->get('productWithSubstitute') !== null;
                $productWithPackaging = $request->get('productWithPackaging') !== null;
                $productWithBatchPrice = $request->get('productWithBatchPrice') !== null;

                $setting->setProductWithImage($productWithImage);
                $setting->setWithExpiration($productWithExpiration);
                $setting->setWithProductReference($productWithReference);
                $setting->setWithBarcode($productWithBarcode);
                $setting->setGenerateBarcode($productWithGenerateBarcode);
                $setting->setProductWithDiscount($productWithDiscount);
                $setting->setWithSubstitute($productWithSubstitute);
                $setting->setWithPackaging($productWithPackaging);
                $setting->setWithBatchPrice($productWithBatchPrice);
                $setting->setDaysBeforeExpiration((int) $request->get('daybeforeexpiration'));

                $stockExpiryDateCount = count($productService
                    ->getProductStockNearExpirationDate((int) $request->get('daybeforeexpiration')));

                $session->set('stockExpiryDateCount',$stockExpiryDateCount);
                break;
            case 'production':
                $withGapProduction= $request->get('withGapProduction') !== null;
                $setting->setWithGapProduction($withGapProduction);

                break;
            case 'rawMaterial':
                $rawMaterialWithExpiration = $request->get('rawMaterialWithExpiration') !== null;

                $setting->setWithRawExpiration($rawMaterialWithExpiration);
                $setting->setDaysBeforeRawExpiration((int) $request->get('daybeforerawexpiration'));

                break;
            case 'sale':
                $saleWithPrintDirectly = $request->get('saleWithPrintDirectly') !== null;
                $saleWithPartialPayment = $request->get('saleWithPartialPayment') !== null;
                $productWithPurchasePrice = $request->get('productWithPurchasePrice') !== null;
                $saleWithDiscount = $request->get('saleWithDiscount') !== null;
                $withSaleNumInvoice = $request->get('withSaleNumInvoice') !== null;
                $saleWithSaleReturn = $request->get('saleWithSaleReturn') !== null;
                $saleWithUserCategory= $request->get('saleWithUserCategory') !== null;
                $productWithWholeSale = $request->get('productWithWholeSale') !== null;
                $withguisale = $request->get('withguisale') !== null;
                $withsoftdelete = $request->get('withsoftdelete') !== null;

                $setting->setPrintDirectly($saleWithPrintDirectly);
                $setting->setWithPartialPayment($saleWithPartialPayment);
                $setting->setWithPurchasePrice($productWithPurchasePrice);
                $setting->setWithDiscount($saleWithDiscount);
                $setting->setWithSaleNumInvoice($withSaleNumInvoice);
                $setting->setWithSaleReturn($saleWithSaleReturn);
                $setting->setWithUserCategory($saleWithUserCategory);
                $setting->setWithWholeSale($productWithWholeSale);
                $setting->setWithGuiSale($withguisale);
                $setting->setWithSoftDelete($withsoftdelete);

                break;
            case 'order':
                $orderWithSettlement = $request->get('orderWithSettlement') !== null;
                $stockWithStockReturn = $request->get('stockWithStockReturn') !== null;
                $withStockGenerateNumInvoice = $request->get('withStockGenerateNumInvoice') !== null;
                $setting->setWithSettlement($orderWithSettlement);
                $setting->setWithStockReturn($stockWithStockReturn);
                $setting->setWithStockGenerateNumInvoice($withStockGenerateNumInvoice);

                break;
            case 'pdf_sale':
                $pageSize =
                    $pageSizeRepository->find((int) $request->get('pdf_sale'));

                if ($pageSize !== null){
                    $setting->setSaleReceiptHeight($pageSize->getHeight());
                    $setting->setSaleReceiptWidth($pageSize->getWidth());
                }
                break;
            case 'pdf_report':
                $pageSize =
                    $pageSizeRepository->find((int) $request->get('pdf_report'));

                if ($pageSize !== null){
                    $setting->setReportHeight($pageSize->getHeight());
                    $setting->setReportWidth($pageSize->getWidth());
                }
                break;
            case 'security':
                if ($request->get('activationLink') !== null)
                    $setting->setActivationLink($request->get('activationLink'));

                $limit = ((int) $request->get('timeValiditySale') > 24)? 24: (int) $request->get('timeValiditySale');
                $setting->setAccessLimit((int) $request->get('accessLimit'));
                $setting->setTimeValiditySale($limit);
                break;
            case 'color':
                $themeId = (int)$request->get('themeId');
                $themeSelected = $themeRepository->find($themeId);
                if ($themeSelected !== null){
                    $setting->setThemeId((int) $request->get('themeId'));
                }

                break;
            case 'chartcolor_areaChart':
                EnvUtil::overWriteEnvFile('AREA_BACKGROUND_COLOR',
                    $request->get('area_background_color'));
                EnvUtil::overWriteEnvFile('AREA_BORDER_COLOR',
                    $request->get('area_border_color'));
                EnvUtil::overWriteEnvFile('AREA_POINT_COLOR',
                    $request->get('area_point_color'));
                EnvUtil::overWriteEnvFile('AREA_POINT_BORDER_COLOR',
                    $request->get('area_point_border_color'));
                EnvUtil::overWriteEnvFile('AREA_MAX_COLOR',
                    $request->get('area_max_color'));
                EnvUtil::overWriteEnvFile('AREA_MIN_COLOR',
                    $request->get('area_min_color'));
                $areaDisplayLegend = $request->get('area_display_legend') !== null;
                EnvUtil::overWriteEnvFile('AREA_DISPLAY_LEGEND',''.$areaDisplayLegend);
                break;
            case 'chartcolor_barChart':
                EnvUtil::overWriteEnvFile('BAR_ONE_COLOR',
                    $request->get('bar_one_color'));
                EnvUtil::overWriteEnvFile('BAR_TWO_COLOR',
                    $request->get('bar_two_color'));
                EnvUtil::overWriteEnvFile('BAR_THREE_COLOR',
                    $request->get('bar_three_color'));
                EnvUtil::overWriteEnvFile('BAR_FOUR_COLOR',
                    $request->get('bar_four_color'));
                EnvUtil::overWriteEnvFile('BAR_FIVE_COLOR',
                    $request->get('bar_five_color'));
                EnvUtil::overWriteEnvFile('BAR_SIX_COLOR',
                    $request->get('bar_six_color'));
                EnvUtil::overWriteEnvFile('BAR_SEVEN_COLOR',
                    $request->get('bar_seven_color'));
                EnvUtil::overWriteEnvFile('BAR_EIGHT_COLOR',
                    $request->get('bar_eight_color'));
                EnvUtil::overWriteEnvFile('BAR_NINE_COLOR',
                    $request->get('bar_nine_color'));
                EnvUtil::overWriteEnvFile('BAR_TEN_COLOR',
                    $request->get('bar_ten_color'));
                EnvUtil::overWriteEnvFile('BAR_ELEVEN_COLOR',
                    $request->get('bar_eleven_color'));
                EnvUtil::overWriteEnvFile('BAR_TWELVE_COLOR',
                    $request->get('bar_twelve_color'));
                EnvUtil::overWriteEnvFile('BAR_BORDER_COLOR',
                    $request->get('bar_border_color'));
                $barDisplayLegend = $request->get('bar_display_legend') !== null;
                EnvUtil::overWriteEnvFile('BAR_DISPLAY_LEGEND',''.$barDisplayLegend);
                break;
            case 'chartcolor_doughnutChart':
                EnvUtil::overWriteEnvFile('DOUGHNUT_ONE_COLOR',
                    $request->get('doughnut_one_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_TWO_COLOR',
                    $request->get('doughnut_two_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_THREE_COLOR',
                    $request->get('doughnut_three_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_FOUR_COLOR',
                    $request->get('doughnut_four_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_FIVE_COLOR',
                    $request->get('doughnut_five_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_SIX_COLOR',
                    $request->get('doughnut_six_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_SEVEN_COLOR',
                    $request->get('doughnut_seven_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_EIGHT_COLOR',
                    $request->get('doughnut_eight_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_NINE_COLOR',
                    $request->get('doughnut_nine_color'));
                EnvUtil::overWriteEnvFile('DOUGHNUT_TEN_COLOR',
                    $request->get('doughnut_ten_color'));
                $doughnutDisplayLegend = $request->get('doughnut_display_legend') !== null;
                EnvUtil::overWriteEnvFile('DOUGHNUT_DISPLAY_LEGEND',''.$doughnutDisplayLegend);
                break;
        }


        $entityManager->persist($setting);
        $entityManager->flush();


        $session->set('setting',$setting);

        if ($themeSelected !== null){
            $session->set('theme',$themeSelected);
        }


        $this->addFlash('success',"controller.setting.index.flash.success");
        return $this->redirectToRoute('setting_index');
    }

    /**
     * @Route("/setting/format/delete/{id}", name="setting_format_delete")
     * @param PageSize $pageSize
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function formatDelete(PageSize $pageSize, EntityManagerInterface $entityManager): Response
    {
        if ($pageSize->getDeletable()){
            $entityManager->remove($pageSize);
            $entityManager->flush();
        }

        return $this->redirectToRoute('setting_index');
    }


    /**
     * @Route("/setting/format/add", name="setting_format_add", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function formatAdd(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pageSize = new PageSize();
        $pageSize->setName($request->get('name'));
        $pageSize->setWidth($request->get('width'));
        $pageSize->setHeight($request->get('height'));

        $entityManager->persist($pageSize);
        $entityManager->flush();

        return $this->redirectToRoute('setting_index');
    }


    /**
     * @Route("/setting/theme/add", name="setting_theme_add")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $theme = new Theme();
        $theme->setGeneralColorLight($request->get('generalColorLight')??'#000000');
        $theme->setGeneralColorDark($request->get('generalColorDark')??'#000000');
        $theme->setColorSideMenuLink($request->get('colorSideMenuLink')??'#000000');
        $theme->setBackcolorSideMenu($request->get('backColorSideMenu')??'#000000');
        $theme->setDeletable(true);

        $entityManager->persist($theme);
        $entityManager->flush();

        return $this->redirectToRoute('setting_index');
    }


    /**
     * @Route("/setting/theme/delete/{id}", name="setting_theme_delete")
     * @param Theme $theme
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Theme $theme, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($theme->getDeletable()){
            $entityManager->remove($theme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('setting_index');
    }

}
