<?php


namespace App\Suscriber;



use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PermissionSubscriber implements EventSubscriberInterface
{

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(RouterInterface $router,
                                AuthorizationCheckerInterface $authorizationChecker,
                                FlashBagInterface $flashBag)
    {
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processVerifyPermission', -20],
        ];
    }

    public function processVerifyPermission(RequestEvent $event): void
    {
        if($_ENV['APP_ENV'] === 'prod') {
            $allowedPaths = [
                'imageLogo',
                'titleStore',
                'notice',
                'linkStore',
                'nbProduct',
                'account_login',
                'account_logout',
                'home',
                'documentation',
                'installation',
            ];
                // use substring to remove lang on the url
            $path = $event->getRequest()->get('_route');

            if (!in_array($path, $allowedPaths, true) &&
                !str_contains($path, 'rest_') &&
                !str_contains($path, 'test_') &&
                !$this->authorizationChecker
                    ->isGranted('PERMISSION_VERIFY', strtoupper($path))) {

                    $this->flashBag->clear();
                    $this->flashBag->add('danger','home.permission.denied');

                    $url = $this->router->generate('home');
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
        }
    }
}
