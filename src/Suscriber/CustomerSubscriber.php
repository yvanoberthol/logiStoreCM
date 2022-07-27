<?php


namespace App\Suscriber;



use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CustomerSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User){
            return ;
        }

        if ($entity->getRole() !== null &&
            strtoupper($entity->getRole()->getTitle()) === 'ADMIN') {
            return ;
        }

        $entityManager = $args->getObjectManager();

        $customer = $this->createOrUpdateCustomer($entity);

        if ($entity->getCustomer() === null){
            $entity->setCustomer($customer);
            $entityManager->persist($entity);
        }

        $entityManager->flush();


    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User){
            return ;
        }

        if ($entity->getRole() !== null &&
            strtoupper($entity->getRole()->getTitle()) === 'ADMIN') {
            return ;
        }

        $entityManager = $args->getObjectManager();

        $customer = $this->createOrUpdateCustomer($entity);

        $entity->setCustomer($customer);
        $entityManager->persist($entity);

        $entityManager->flush();

    }


    private function createOrUpdateCustomer(User $user){
        $customer = $user->getCustomer()?? new Customer();
        $customer->setEmail($user->getEmail());
        $customer->setName($user->getAllName());
        $customer->setAddress($user->getDistrict());
        $customer->setPhoneNumber($user->getPhone());
        $customer->setGender($user->getGender());
        $customer->setType('EMPLOYEE');

        $customer->setEnabled($user->getCanCustomer());

        return $customer;
    }
}
