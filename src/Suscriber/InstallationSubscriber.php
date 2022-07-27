<?php


namespace App\Suscriber;



use App\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class InstallationSubscriber implements EventSubscriberInterface
{


    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * InstallationSubscriber constructor.
     * @param RouterInterface $router
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(RouterInterface $router,
                                EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processInstallation', 25],
        ];
    }

    public function processInstallation(RequestEvent $event): void
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
            ];
            $path = $event->getRequest()->get('_route');
            $store = $this->entityManager
                ->getRepository(Store::class)->get();

            if ($store === null && !in_array($path, $allowedPaths, true))
            {
                $url = $this->router->generate('installation');
                $response = new RedirectResponse($url);
                $event->setResponse($response);
            }
       }
    }
}
