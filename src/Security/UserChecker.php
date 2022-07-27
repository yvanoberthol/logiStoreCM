<?php


namespace App\Security;


use App\Entity\Connection;
use App\Entity\Setting;
use App\Entity\Theme;
use App\Entity\User as User;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProductService
     */
    private $productService;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * UserChecker constructor.
     * @param EntityManagerInterface $entityManager
     * @param ProductService $productService
     * @param RequestStack $requestStack
     */
    public function __construct(EntityManagerInterface $entityManager,
                                ProductService $productService,
                                RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->productService = $productService;
        $this->session = $requestStack->getSession();
    }


    /**
     * @inheritDoc
     */
    public function checkPreAuth(UserInterface $user)
    {
        // TODO: Implement checkPreAuth() method.
        if (!$user instanceof User){
            return;
        }

        if (!$user->getEnabled()){
            throw new CustomMessageSecurity('account is disabled');
        }

        if ($user->getRole()->getPermissions()->count() === 0){
            throw new PermissionMessageSecurity('no permission for this user');
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function checkPostAuth(UserInterface $user)
    {
        // TODO: Implement checkPreAuth() method.
        if (!$user instanceof User){
            return;
        }

        if (!$user->getEnabled()){
            throw new CustomMessageSecurity('account is disabled');
        }

        if ($user->getRole()->getPermissions()->count() === 0){
            throw new PermissionMessageSecurity('no permission for this user');
        }

        $user->setLastConnection(new DateTime());

        $connection = new Connection();
        $connection->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($connection);
        $this->entityManager->flush();

        $settingRepository = $this->entityManager->getRepository(Setting::class);
        $setting = $settingRepository->get();
        $this->session->set('setting',$setting);


        $themeRepository = $this->entityManager->getRepository(Theme::class);

        $themeDefault = $themeRepository->find((int) $setting->getThemeId());

        if ($themeDefault !== null){
            $this->session->set('theme',$themeDefault);
        }else{
            $themeDefault = new Theme();
            $themeDefault->setBackcolorSideMenu($_ENV['BACK_COLOR_SIDEMENU']);
            $themeDefault->setColorSideMenuLink($_ENV['BACK_COLOR_SIDEMENU_LINK']);
            $themeDefault->setGeneralColorDark($_ENV['GENERAL_COLOR_DARK']);
            $themeDefault->setGeneralColorLight($_ENV['GENERAL_COLOR_LIGHT']);
            $this->session->set('theme',$themeDefault);
        }


        if ($user->getRole()->getRank() > 1){
            $stockOutOfDateCount = count($this->productService
                ->getProductStockNearExpirationDate(0));

            $stockExpiryDateCount = count($this->productService
                ->getProductStockNearExpirationDate($setting->getDaysBeforeExpiration()));

            $this->session->set('stockOutOfDateCount',$stockOutOfDateCount);
            $this->session->set('stockExpiryDateCount',$stockExpiryDateCount);
        }

    }
}
