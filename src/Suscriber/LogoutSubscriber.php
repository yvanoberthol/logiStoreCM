<?php


namespace App\Suscriber;


use App\Repository\ConnectionRepository;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * SubscriptionSubscriber constructor.
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     */
    public function __construct(EntityManagerInterface $entityManager,
                                TokenStorageInterface $tokenStorage,
                                RouterInterface $router)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['processLogout', 20],
        ];
    }

    public function processLogout(LogoutEvent $event): void
    {
        if ($this->tokenStorage->getToken() !== null){

            $user = $this->tokenStorage->getToken()->getUser();
            if ($user !== null) {
                $user->setLastDeconnection(new DateTime());
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }

        $route =  $this->router->generate('account_login');
        $response = new RedirectResponse($route);
        $event->setResponse($response);
    }
}
