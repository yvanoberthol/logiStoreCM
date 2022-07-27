<?php


namespace App\Suscriber;


use App\Repository\ConnectionRepository;
use App\Util\GlobalConstant;
use DateTime;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConnectionAuthenticationSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var TokenStorageInterface
     */
    private $token;
    /**
     * @var ConnectionRepository
     */
    private $connectionRepository;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * SubscriptionSubscriber constructor.
     * @param RouterInterface $router
     * @param ConnectionRepository $connectionRepository
     * @param TokenStorageInterface $token
     * @param SessionInterface $session
     */
    public function __construct(RouterInterface $router,
                                ConnectionRepository $connectionRepository,
                                TokenStorageInterface $token,
                                SessionInterface $session)
    {
        $this->router = $router;
        $this->token = $token;
        $this->connectionRepository = $connectionRepository;
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processConnectionAuthentication', -25],
        ];
    }

    public function processConnectionAuthentication(RequestEvent $event): void
    {
        if($_ENV['APP_ENV'] === 'prod') {

            $allowedPaths = [
                'imageLogo',
                'titleStore',
                'linkStore',
                'notice',
                'nbProduct',
                'account_login',
                'account_logout',
                'home',
                'documentation',
                'installation',
            ];

            $path = $event->getRequest()->get('_route');
            $setting = $this->session->get('setting');

            if ($setting !== null &&
                !empty($setting->getAccessLimit()) &&
                !in_array($path, $allowedPaths, true) &&
                $this->token->getToken() !== null) {

                $user = $this->token->getToken()->getUser();
                $lastConnection = $this->connectionRepository->findLastConnection($user);

                if ($lastConnection !== null) {
                    try {
                        // default access limited to 24 h
                        if (GlobalConstant::limitPassed($lastConnection->getAddDate(),
                            $setting->getAccessLimit())) {
                            $url = $this->router->generate('account_logout');
                            $response = new RedirectResponse($url);
                            $event->setResponse($response);
                        }
                    } catch (Exception $e) {

                    }

                }
            }
        }
    }
}
