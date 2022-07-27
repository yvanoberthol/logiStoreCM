<?php


namespace App\Suscriber;



use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class ModeSubscriber implements EventSubscriberInterface
{
    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag,
                                FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processVerifyPermission', -15],
        ];
    }

    public function processVerifyPermission(RequestEvent $event): void
    {
        if($_ENV['APP_ENV'] === 'prod') {
            $path = $event->getRequest()->get('_route');
            $mode = $this->parameterBag->get('app.mode');
            if ($mode === 'demo' && !str_starts_with($path, 'rest')
                && ((str_contains($path, 'delete') ||
                str_contains($path, 'edit') ||
                str_contains($path, 'installation') ||
                str_contains($path, 'update') ||
                str_contains($path, 'remove'))))
            {
                    $this->flashBag->clear();
                    $this->flashBag->add('danger','home.demo.denied');

                    //$url = $this->router->generate('home');
                    $response = new RedirectResponse($_SERVER['HTTP_REFERER']);
                    $event->setResponse($response);
            }
       }
    }
}
