<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Interfaces\ActivationInterface;
use App\Repository\SubscriptionRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ActivationController extends AbstractController
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * ActivationController constructor.
     * @param HttpClientInterface $httpClient
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct( HttpClientInterface $httpClient,
                                 RequestStack $requestStack,
                                 LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->session = $requestStack->getSession();
    }


    /**
     * @Route("/activate", name="activation", methods={"POST","GET"})
     * @param Request $request
     * @param ActivationInterface $activation
     * @param SubscriptionRepository $subscriptionRepository
     * @return Response
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function activation(Request $request,
                               ActivationInterface $activation,
                               SubscriptionRepository $subscriptionRepository): Response
    {

        $model['subscription'] = $subscriptionRepository->get();
        if (!empty($model['subscription']) && $request->getMethod() === 'POST') {
            $key = $request->get('key');
            $result = $this->sendActivaton($model['subscription'],$key);

            if (isset($result->msg)){
                $valid = $activation->activate($model['subscription'],$result);

                if ($valid === false){
                    $this->addFlash('danger','controller.activation.activate.flash.codeInvalid');
                }else{
                    $this->addFlash('success','controller.activation.activate.flash.codeValid');
                }

            }else{
                $this->addFlash('danger','controller.activation.activate.flash.internet');
            }
        }

        //breadcumb
        $model['entity'] = 'controller.activation.activate.entity';
        $model['page'] = 'controller.activation.activate.page';

        return $this->render('activation/activate.html.twig', $model);
    }

    /**
     * @param Subscription $subscription
     * @param $code
     * @return bool|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function sendActivaton(Subscription $subscription, $code){
        $url = $this->session->get('setting')->getActivationLink();
        $sendCode = $this->getParameter('app.code_send');
        try {
            $request = $this->httpClient->request('POST', $url, [
                'body' => [
                    'codeId' => $sendCode,
                    'code' => $code,
                    'dayRemaining' => $subscription->getNbDayRemaining()
                ]
            ]);

            return json_decode($request->getContent());
        } catch (TransportExceptionInterface $e) {
            $this->logger->error($e->getFile().':'.$e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->logger->error($e->getFile().':'.$e->getMessage());
            return false;
        }
    }

}
