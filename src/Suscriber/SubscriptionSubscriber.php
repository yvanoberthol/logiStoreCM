<?php


namespace App\Suscriber;


use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class SubscriptionSubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * SubscriptionSubscriber constructor.
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param FlashBagInterface $flashBag
     * @param SessionInterface $session
     */
    public function __construct(EntityManagerInterface $entityManager,
                                RouterInterface $router,
                                FlashBagInterface $flashBag,
                                SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processExpirationSubscription', -22],
        ];
    }

    /**
     * @param RequestEvent $event
     * @throws Exception
     */
    public function processExpirationSubscription(RequestEvent $event): void
    {
        if($_ENV['APP_ENV'] === 'prod') {
            $allowedPaths = [
                'imageLogo',
                'titleStore',
                'linkStore',
                'notice',
                'nbProduct',
                'documentation',
                'installation',
                'account_logout',
                'account_login',
                'activation',
                'home',
            ];

            // use substring to remove lang on the url
            $path = $event->getRequest()->get('_route');

            $setting = $this->session->get('setting');

            if ($setting !== null &&
                $setting->getWithSubscription() &&
                !in_array($path, $allowedPaths, true) &&
                !str_contains($path, 'rest_')&&
                !str_contains($path, 'test_')) {


                $subscriptionRepository =
                    $this->entityManager->getRepository(Subscription::class);

                /** @var Subscription $subscription */
                $subscription = $subscriptionRepository->get();

                if ($subscription !== null &&
                    $subscription->getNbDayRemaining() === 0 &&
                    $subscription->getEnabled()) {

                    $this->flashBag->clear();
                    $this->flashBag->add('warning', 'home.home.expiration_plan');
                    $url = $this->router->generate('home');
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }

            }
        }
    }
}
