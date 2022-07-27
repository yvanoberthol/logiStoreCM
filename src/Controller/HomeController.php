<?php

namespace App\Controller;

use App\Dto\InstallationDto;
use App\Entity\Language;
use App\Entity\LossType;
use App\Entity\Package;
use App\Entity\PageSize;
use App\Entity\PaymentMethod;
use App\Entity\Permission;
use App\Entity\Store;
use App\Entity\Role;
use App\Entity\Setting;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\ProductionRepository;
use App\Repository\ProductRepository;
use App\Repository\LossRepository;
use App\Repository\RawMaterialRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\StockRepository;
use App\Repository\SaleRepository;
use App\Util\GlobalConstant;
use App\Util\PackageConstant;
use App\Util\RoleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="home", methods={"GET","POST"})
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param ProductRepository $productRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param LossRepository $lossRepository
     * @param StockRepository $stockRepository
     * @return Response
     */
    public function index(Request $request,SaleRepository $saleRepository,
                          ProductRepository $productRepository,
                          SubscriptionRepository $subscriptionRepository,
                          LossRepository $lossRepository,
                          StockRepository $stockRepository): Response
    {

        //breadcumb
        $model['entity'] = 'controller.home.index.entity';
        $model['page'] = 'controller.home.index.page';

        $user = $this->getUser();
        if(!$user){
            return $this->redirectToRoute('account_login');
        }


        $model['user'] = $user;

        $model = GlobalConstant::getMonthsAndYear($request,$model);

        if ($user->getRole()->getRank() === 1){
            $model['saleStats'] = $saleRepository->getSaleByYear($model['year'],$user);
            $model['sales'] = $saleRepository->countAll($user);

            return $this->render('home/homeCashier.html.twig',$model);
        }

        $model['saleStats'] = $saleRepository
            ->getSaleByYear($model['year']);

        $model['sales'] = $saleRepository->countAll();
        $model['products'] = $productRepository->countAll();
        $model['losses'] = $lossRepository->countAll();
        $model['orders'] = $stockRepository->countAll();

        $model['subscription'] = $subscriptionRepository->get();

        $model['saleByProducts'] = $productRepository
            ->saleByProduct($model['monthNow'],$model['year']);

        return $this->render('home/home.html.twig',$model);
    }

    /**
     * @Route("/documentation", name="documentation")
     */
    public function documentation(): Response
    {
        return $this->render('docs/documentation.html.twig');
    }

    /**
     * @Route("/installation", name="installation")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param UserPasswordHasherInterface $passwordEncoder
     * @return Response
     * @throws Exception
     */
    public function installation(Request $request,
                                 EntityManagerInterface $entityManager,
                                 RouterInterface $router,
                                 UserPasswordHasherInterface $passwordEncoder): Response
    {

        $store = $entityManager->getRepository(Store::class)->get();
        if ($store !== null){
            $route = ($this->getUser() !== null)? 'home': 'account_login';
            return $this->redirectToRoute($route);
        }

        $installation = new InstallationDto();

        if ($request->isMethod('POST')){
            $installation->storeName = $request->get('store_name');
            $installation->storeEmail = $request->get('store_email');
            $installation->storePhoneNumber = $request->get('store_phoneNumber');
            $installation->userName = $request->get('user_name');
            $installation->userEmail = $request->get('user_email');
            $installation->userPassword = $request->get('user_password');
            $installation->userConfirmPassword = $request->get('user_confirmPassword');
            $installation->parameterCurrencyName = $request->get('parameter_currencyName');
            $installation->parameterCurrencyDecimal = $request->get('parameter_currencyDecimal');
            $installation->parameterCurrencySide = $request->get('parameter_currencySide');
            $installation->parameterCurrencyThousandSeparator = $request->get('parameter_currencyThousandSeparator');

            if ($installation->userPassword !== $installation->userConfirmPassword ){
                $this->addFlash('danger','controller.installation.index.flash.danger.password');
            }else{

                $this->init($entityManager,$installation,$router,$passwordEncoder);
                $this->addFlash('success','controller.installation.index.flash.success');

                return $this->redirectToRoute('installation');

            }
        }


        $model['installation'] = $installation;
        return $this->render('installation/index.html.twig',$model);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param InstallationDto $installation
     * @param RouterInterface $router
     * @param UserPasswordHasherInterface $passwordEncoder
     * @throws Exception * initialize configuration and insertion in database
     */
    private function init(EntityManagerInterface $entityManager,
                          InstallationDto $installation,
                          RouterInterface $router,
                          UserPasswordHasherInterface $passwordEncoder): void
    {

        //packages
        foreach (PackageConstant::PACKAGES as $key=>$value){
            $package = new Package();
            $package->setName($key)->setNbDays($value);
            $entityManager->persist($package);

            if ($key===PackageConstant::MENSUAL){
                //subscription
                $subscription = new Subscription();
                $subscription->setPackage($package)
                    ->setDate(new DateTime())->setCreatedAt(new DateTime())
                    ->setEnabled(true);
                $entityManager->persist($subscription);
            }
        }

        //payment Method
        $cash = new PaymentMethod();
        $cash->setName('CASH');
        $entityManager->persist($cash);

        /*$om = new PaymentMethod();
        $om->setName('Orange Money');
        $entityManager->persist($om);

        $momo = new PaymentMethod();
        $momo->setName('MTN Mobile Money');
        $entityManager->persist($momo);*/

        //roles
        foreach (RoleConstant::ROLES as $key=>$value){
            $role = new Role();
            $role->setName($key)
                ->setRank($value)->setUpdatable(false);
            if ($key === RoleConstant::ROLE_ADMIN){
                $permissionProfit = new Permission();
                $permissionProfit->setCode(strtoupper('SALE_PROFIT'));
                $permissionProfit->setAddDate(new DateTime());
                $entityManager->persist($permissionProfit);
                $role->addPermission($permissionProfit);

                $permissionDeleteAll = new Permission();
                $permissionDeleteAll->setCode(strtoupper('SALE_DELETE_ALL'));
                $permissionDeleteAll->setAddDate(new DateTime());
                $entityManager->persist($permissionDeleteAll);
                $role->addPermission($permissionDeleteAll);

                $permissionWithDiscount = new Permission();
                $permissionWithDiscount->setCode(strtoupper('SALE_WITH_DISCOUNT'));
                $permissionWithDiscount->setAddDate(new DateTime());
                $entityManager->persist($permissionWithDiscount);
                $role->addPermission($permissionWithDiscount);

                $permissionWithPartialPayment = new Permission();
                $permissionWithPartialPayment->setCode(strtoupper('SALE_WITH_PARTIAL_PAYMENT'));
                $permissionWithPartialPayment->setAddDate(new DateTime());
                $entityManager->persist($permissionWithPartialPayment);
                $role->addPermission($permissionWithPartialPayment);

                $permissionWithWholePartialPayment = new Permission();
                $permissionWithWholePartialPayment->setCode(strtoupper('SALE_WHOLE_WITH_PARTIAL_PAYMENT'));
                $permissionWithWholePartialPayment->setAddDate(new DateTime());
                $entityManager->persist($permissionWithWholePartialPayment);
                $role->addPermission($permissionWithWholePartialPayment);

                $permissionWithSaleEmployee = new Permission();
                $permissionWithSaleEmployee->setCode(strtoupper('SALE_FOR_EMPLOYEE'));
                $permissionWithSaleEmployee->setAddDate(new DateTime());
                $entityManager->persist($permissionWithSaleEmployee);
                $role->addPermission($permissionWithSaleEmployee);

                $permissionProductionWithGap = new Permission();
                $permissionProductionWithGap->setCode(strtoupper('PRODUCTION_WITH_GAP'));
                $permissionProductionWithGap->setAddDate(new DateTime());
                $entityManager->persist($permissionProductionWithGap);
                $role->addPermission($permissionProductionWithGap);

                foreach ($this->getPermissions($router) as $permissionName){
                    $permission = new Permission();
                    $permission->setCode(strtoupper($permissionName));
                    $permission->setAddDate(new DateTime());
                    $entityManager->persist($permission);
                    $role->addPermission($permission);
                }
            }
            $entityManager->persist($role);

            if ($key === RoleConstant::ROLE_ADMIN){
                //user
                $user = new User();
                $user->setName($installation->userName);
                $user->setEmail($installation->userEmail);
                $user->setPlainPassword($installation->userPassword);
                $newpassword =
                    $passwordEncoder->hashPassword($user, $installation->userPassword);
                $user->setPassword($newpassword);
                $user->setRole($role);
                $entityManager->persist($user);
            }
        }

        //loss type
        $lossTypeOutOfDate = new LossType();
        $lossTypeOutOfDate->setName(GlobalConstant::OUTOFDATE)
            ->setUpdatable(false);
        $entityManager->persist($lossTypeOutOfDate);

        //language
        $languageEn = new Language();
        $languageEn->setCode('en');
        $languageEn->setName('en');
        $languageEn->setDeletable(false);
        $entityManager->persist($languageEn);

        $languageFr = new Language();
        $languageFr->setCode('fr');
        $languageFr->setName('fr');
        $languageFr->setDeletable(false);
        $entityManager->persist($languageFr);


        //format pDF
        $formatA4 = new PageSize();
        $formatA4->setName('A4');
        $formatA4->setWidth(210);
        $formatA4->setHeight(297);
        $formatA4->setDeletable(false);
        $entityManager->persist($formatA4);

        $formatA5 = new PageSize();
        $formatA5->setName('A5');
        $formatA5->setWidth(148);
        $formatA5->setHeight(210);
        $formatA5->setDeletable(false);
        $entityManager->persist($formatA5);

        $formatA6 = new PageSize();
        $formatA6->setName('A6');
        $formatA6->setWidth(105);
        $formatA6->setHeight(148);
        $formatA6->setDeletable(false);
        $entityManager->persist($formatA6);

        $formatPos80 = new PageSize();
        $formatPos80->setName('Giga 360');
        $formatPos80->setWidth(80);
        $formatPos80->setHeight(210);
        $formatPos80->setDeletable(true);
        $entityManager->persist($formatPos80);



        // setting
        $setting = $entityManager->getRepository(Setting::class)->get();
        if ($setting === null) $setting = new Setting();
        if ($_ENV['SUBSCRIPTION'] === 'null' || $_ENV['SUBSCRIPTION'] !== '1' || $_ENV['SUBSCRIPTION'] !== 'true'){
            $setting->setWithSubscription(false);
        }

        $setting->setCurrencyName($installation->parameterCurrencyName);
        $setting->setCurrencySide($installation->parameterCurrencySide);
        $setting->setCurrencyDecimal($installation->parameterCurrencyDecimal);
        $setting->setCurrencyThousandSeparator($installation->parameterCurrencyThousandSeparator);
        $entityManager->persist($setting);

        //store
        $store = $entityManager->getRepository(Store::class)->get();
        if ($store === null) $store = new Store();

        $store->setName($installation->storeName);
        $store->setEmail($installation->storeEmail);
        $store->setPhoneNumber($installation->storePhoneNumber);
        $entityManager->persist($store);

        $entityManager->flush();

    }

    private function getPermissions(RouterInterface $router): array
    {
        $subPaths = [
            'imageLogo',
            'titleStore',
            'linkStore',
            'notice',
            'nbProduct',
            'account_login',
            'documentation',
            'installation',
        ];

        $allRoutes = $router->getRouteCollection()->all();

        $routeNames = [];
        foreach ($allRoutes as $key=>$value){
            if (!str_contains($key,'profiler') && !str_starts_with($key,'rest')
                && !str_starts_with($key,'test') && !str_starts_with($key,'_') && !in_array($key,$subPaths,true))
                $routeNames[] = $key;
        }

        return $routeNames;
    }
}
