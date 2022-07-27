<?php


namespace App\Service;


use App\Entity\Subscription;
use App\Interfaces\ActivationInterface;
use App\Repository\PackageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ActivationService implements ActivationInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var PackageRepository
     */
    private $packageRepository;

    public function __construct(EntityManagerInterface $entityManager,
                                PackageRepository $packageRepository)
    {
        $this->entityManager = $entityManager;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @param Subscription $subscription
     * @param $result
     * @return bool
     * @throws Exception
     */
    public function activate(Subscription $subscription, $result): ?bool
    {
        if ($subscription !== null){

            if ($result->msg === 'INVALID'){
                return false;
            }

            if ($result->msg === 'OK'){
                if ($result->value === 0){
                    $subscription->setEnabled(false);
                }else{
                    $package = $this->packageRepository
                        ->findOneBy(['nbDays' => (int) $result->value]);

                    if ($package !== null){
                        // recover the update date
                        $dateUpdate = ($subscription->getExpiration() > new DateTime())? $subscription->getExpiration() : new DateTime();
                        $subscription->setDate($dateUpdate);
                        $subscription->setPackage($package);
                    }
                }


                // persist user and subscription
                $this->entityManager->persist($subscription);
                $this->entityManager->flush();

                return true;

            }
        }

        return false;
    }
}

