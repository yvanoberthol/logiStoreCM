<?php


namespace App\Service;


use App\Entity\Customer;
use App\Entity\Supplier;
use App\Repository\StoreRepository;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMailer
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var string|null
     */
    private $senderMail;

    /**
     * @var string|null
     */
    private $name;


    /**
     * SendMailer constructor.
     * @param MailerInterface $mailer
     * @param StoreRepository $storeRepository
     */
    public function __construct(MailerInterface $mailer,
                                StoreRepository $storeRepository)
    {
        $this->mailer = $mailer;
        $this->senderMail = getenv('MAILER_EMAIL');
        $this->name = 'UYIELA';
        if (!empty($storeRepository->get())){
            $store = $storeRepository->get();
            $this->name  = $store->getName();
        }
    }



    // send an order to the supplier
    public function sendOrder(Supplier $supplier, $order): bool
    {
        $validator = new EmailValidator();
        if ($validator->isValid($supplier->getEmail(),new RFCValidation())) {
            $message = (new TemplatedEmail())
                ->from(new Address($this->senderMail,$this->name))
                ->to($supplier->getEmail())
                ->subject('Product order');

            $fileName = 'order-'.time().'.pdf';
            $message->attach($order,$fileName,'application/pdf');

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                return false;
            }
        }
        return true;
    }


    // send a invoice to the customer
    public function sendInvoice(Customer $customer, $invoice): bool
    {
        $validator = new EmailValidator();
        if ($validator->isValid($customer->getEmail(),new RFCValidation())) {
            $message = (new TemplatedEmail())
                ->from(new Address($this->senderMail,$this->name))
                ->to($customer->getEmail())
                ->subject('Invoice');

            $fileName = 'invoice-'.time().'.pdf';
            $message->attach($invoice,$fileName,'application/pdf');

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                return false;
            }
        }
        return true;
    }

    // permet d'envoyer les identifiants de connexion à l'utilisateur
    public function sendIdentifier($login,$password): bool
    {
        $validator = new EmailValidator();
        $body = "Your login : $login and your password: $password";

        if ($validator->isValid($login,new RFCValidation())) {

            $message = (new TemplatedEmail())
                ->from(new Address($this->senderMail,$this->name))
                ->to($login)
                ->subject('Your credentials')
                /*->htmlTemplate('email/welcome.html.twig')
                ->context(['login' => $login,'password' => $password])*/
                ->text($body);

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                return false;
            }
        }
        return true;
    }
}
