<?php


namespace App\Interfaces;


use App\Entity\Subscription;

interface ActivationInterface
{

    public function activate(Subscription $subscription, $result);

}
