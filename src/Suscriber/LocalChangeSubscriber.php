<?php


namespace App\Suscriber;



use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class LocalChangeSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;

    /**
     * LocalChangeSubscriber constructor.
     * @param $defaultLocale
     */
    public function __construct($defaultLocale)
    {

        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['processLocalChange', 10],
        ];
    }

    public function processLocalChange(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($locale = $request->attributes->get('_locale')){
            $request->getSession()->set('_locale',$locale);
        }else if ($request->getSession()->get('_locale')){
            $request->setLocale($request->getSession()->get('_locale'));
        }else{
            $request->setLocale($this->defaultLocale);
        }
    }
}
