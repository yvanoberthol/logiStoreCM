<?php


namespace App\Dto;


class PaymentMethodDto
{
    private $name;
    private $nbSales;
    private $amountPerceived;
    private $amountSettled;

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PaymentMethodDto
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getNbSales(): ?int
    {
        return $this->nbSales;
    }

    /**
     * @param int $nbSales
     * @return PaymentMethodDto
     */
    public function setNbSales(int $nbSales): self
    {
        $this->nbSales = $nbSales;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountPerceived():?float
    {
        return $this->amountPerceived;
    }

    /**
     * @param float $amountPerceived
     * @return PaymentMethodDto
     */
    public function setAmountPerceived(float $amountPerceived): self
    {
        $this->amountPerceived = $amountPerceived;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountSettled(): ?float
    {
        return $this->amountSettled;
    }

    /**
     * @param float $amountSettled
     * @return PaymentMethodDto
     */
    public function setAmountSettled(float $amountSettled): self
    {
        $this->amountSettled = $amountSettled;
        return $this;
    }


}
