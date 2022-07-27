<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SettingRepository::class)
 */
class Setting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $currencyName='USD';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $currencySide='right';

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $currencyDecimal='0';

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $currencyThousandSeparator=',';

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $dateSeparator='-';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $shortDate1='Y';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $shortDate2='m';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $mediumDate1='Y';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $mediumDate2='m';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $mediumDate3='d';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $longDate1='Y';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $longDate2='m';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $longDate3='d';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $longDate4='H';

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $longDate5='i';

    /**
     * @ORM\Column(type="integer")
     */
    private $productNew = 30;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $activationLink="http://localhost:8000/activation/getKey";

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withSubscription = true;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $accessLimit = 24;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeValiditySale = 24;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleReceiptHeight=210;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleReceiptWidth=80;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleFontSize=16;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleMarginLeft=1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleMarginRight=1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleMarginTop=5;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reportHeight=297;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reportWidth=210;

    /**
     * @ORM\Column(type="integer", length=5, nullable=true)
     */
    private $reportFontSize=14;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withExpiration=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $productWithImage=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $productWithDiscount=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withBarcode=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $generateBarcode=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withSubstitute=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withBatchPrice=true;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $daysBeforeExpiration=50;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withRawExpiration=false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $daysBeforeRawExpiration=50;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withPurchasePrice=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withDiscount=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withPartialPayment=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $printDirectly=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withGuiSale=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withSoftDelete=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withWholeSale=true;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $barcodeWidth=100;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $barcodeHeight=50;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withSettlement=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withAccounting=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withHrm=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withExpense=true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withProduction=false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $withClubPoint=false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxIntervalPeriod=30;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $randomCharacter=20;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $themeId;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withSaleReturn=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withStockReturn=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withPackaging=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withStockGenerateNumInvoice = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withGapProduction=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withProductReference=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withUserCategory=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withSaleNumInvoice=false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrencyName(): ?string
    {
        return $this->currencyName;
    }

    public function setCurrencyName(?string $currencyName): self
    {
        $this->currencyName = $currencyName;

        return $this;
    }

    public function getCurrencySide(): ?string
    {
        return $this->currencySide;
    }

    public function setCurrencySide(?string $currencySide): self
    {
        $this->currencySide = $currencySide;

        return $this;
    }

    public function getCurrencyDecimal(): ?string
    {
        return $this->currencyDecimal;
    }

    public function setCurrencyDecimal(?string $currencyDecimal): self
    {
        $this->currencyDecimal = $currencyDecimal;

        return $this;
    }

    public function getCurrencyThousandSeparator(): ?string
    {
        return $this->currencyThousandSeparator;
    }

    public function setCurrencyThousandSeparator(?string $currencyThousandSeparator): self
    {
        $this->currencyThousandSeparator = $currencyThousandSeparator;

        return $this;
    }

    public function getDateShort(): ?string
    {
        return $this->getShortDate1().$this->getDateSeparator()
            .$this->getShortDate2();
    }


    public function getDateMedium(): ?string
    {
        return $this->getMediumDate1()
            .$this->getDateSeparator()
            .$this->getMediumDate2()
            .$this->getDateSeparator()
            .$this->getMediumDate3();
    }

    public function getDateLong(): ?string
    {
        return $this->getLongDate1()
            .$this->getDateSeparator()
            .$this->getLongDate2()
            .$this->getDateSeparator()
            .$this->getLongDate3()
            .' '
            .$this->getLongDate4()
            .':'
            .$this->getLongDate5();
    }


    public function getDateSeparator(): ?string
    {
        return $this->dateSeparator;
    }

    public function setDateSeparator(?string $dateSeparator): self
    {
        $this->dateSeparator = $dateSeparator;

        return $this;
    }

    public function getDateMediumPicker(): ?string
    {
        return str_replace(array('Y', 'm','d'),array('yyyy', 'mm', 'dd'),$this->getDateMedium());
    }


    public function getDateLongPicker(): ?string
    {
        return str_replace(
            array('Y','m','d','H', 'i'),
            array('yyyy','mm', 'dd','hh', 'ii'),
            $this->getDateLong());
    }


    public function getLongDateJs(): ?string
    {
        return str_replace(
            array('Y','m','d','H', 'i'),
            array('YYYY','MM', 'DD','HH', 'mm'),
            $this->getDateLong());
    }

    public function getMediumDateJs(): ?string
    {
        return str_replace(
            array('Y','m','d'),
            array('YYYY','MM', 'DD'),
            $this->getDateMedium());
    }


    public function getShortDate1(): ?string
    {
        return $this->shortDate1;
    }

    public function setShortDate1(?string $shortDate1): self
    {
        $this->shortDate1 = $shortDate1;

        return $this;
    }

    public function getShortDate2(): ?string
    {
        return $this->shortDate2;
    }

    public function setShortDate2(?string $shortDate2): self
    {
        $this->shortDate2 = $shortDate2;

        return $this;
    }

    public function getMediumDate1(): ?string
    {
        return $this->mediumDate1;
    }

    public function setMediumDate1(?string $mediumDate1): self
    {
        $this->mediumDate1 = $mediumDate1;

        return $this;
    }

    public function getMediumDate2(): ?string
    {
        return $this->mediumDate2;
    }

    public function setMediumDate2(?string $mediumDate2): self
    {
        $this->mediumDate2 = $mediumDate2;

        return $this;
    }

    public function getMediumDate3(): ?string
    {
        return $this->mediumDate3;
    }

    public function setMediumDate3(?string $mediumDate3): self
    {
        $this->mediumDate3 = $mediumDate3;

        return $this;
    }

    public function getLongDate1(): ?string
    {
        return $this->longDate1;
    }

    public function setLongDate1(?string $longDate1): self
    {
        $this->longDate1 = $longDate1;

        return $this;
    }

    public function getLongDate2(): ?string
    {
        return $this->longDate2;
    }

    public function setLongDate2(?string $longDate2): self
    {
        $this->longDate2 = $longDate2;

        return $this;
    }

    public function getLongDate3(): ?string
    {
        return $this->longDate3;
    }

    public function setLongDate3(?string $longDate3): self
    {
        $this->longDate3 = $longDate3;

        return $this;
    }

    public function getLongDate4(): ?string
    {
        return $this->longDate4;
    }

    public function setLongDate4(?string $longDate4): self
    {
        $this->longDate4 = $longDate4;

        return $this;
    }

    public function getLongDate5(): ?string
    {
        return $this->longDate5;
    }

    public function setLongDate5(?string $longDate5): self
    {
        $this->longDate5 = $longDate5;

        return $this;
    }

    public function getProductNew(): ?int
    {
        return $this->productNew;
    }

    public function setProductNew(int $productNew): self
    {
        $this->productNew = $productNew;

        return $this;
    }

    public function getActivationLink(): ?string
    {
        return $this->activationLink;
    }

    public function setActivationLink(?string $activationLink): self
    {
        $this->activationLink = $activationLink;

        return $this;
    }

    public function getWithSubscription(): ?bool
    {
        return $this->withSubscription;
    }

    public function setWithSubscription(?bool $withSubscription): self
    {
        $this->withSubscription = $withSubscription;

        return $this;
    }

    public function getAccessLimit(): ?int
    {
        return $this->accessLimit;
    }

    public function setAccessLimit(?int $accessLimit): self
    {
        $this->accessLimit = $accessLimit;

        return $this;
    }

    public function getTimeValiditySale(): ?int
    {
        return $this->timeValiditySale;
    }

    public function setTimeValiditySale(?int $timeValiditySale): self
    {
        $this->timeValiditySale = $timeValiditySale;

        return $this;
    }

    /**
     * @return integer
     */
    public function getSaleReceiptHeight(): ?int
    {
        return $this->saleReceiptHeight;
    }

    /**
     * @param int $saleReceiptHeight
     * @return Setting
     */
    public function setSaleReceiptHeight(?int $saleReceiptHeight): self
    {
        $this->saleReceiptHeight = $saleReceiptHeight;
        return $this;
    }

    /**
     * @return int
     */
    public function getSaleReceiptWidth(): ?int
    {
        return $this->saleReceiptWidth;
    }

    /**
     * @param int $saleReceiptWidth
     * @return Setting
     */
    public function setSaleReceiptWidth(?int $saleReceiptWidth): self
    {
        $this->saleReceiptWidth = $saleReceiptWidth;
        return $this;
    }

    /**
     * @return int
     */
    public function getSaleFontSize():?int
    {
        return $this->saleFontSize;
    }

    /**
     * @param int $saleFontSize
     * @return Setting
     */
    public function setSaleFontSize(?int $saleFontSize):self
    {
        $this->saleFontSize = $saleFontSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getSaleMarginLeft():?int
    {
        return $this->saleMarginLeft;
    }

    /**
     * @param int $saleMarginLeft
     * @return Setting
     */
    public function setSaleMarginLeft(?int $saleMarginLeft):self
    {
        $this->saleMarginLeft = $saleMarginLeft;
        return $this;
    }

    /**
     * @return int
     */
    public function getSaleMarginRight():?int
    {
        return $this->saleMarginRight;
    }

    /**
     * @param int $saleMarginRight
     * @return Setting
     */
    public function setSaleMarginRight(?int $saleMarginRight):self
    {
        $this->saleMarginRight = $saleMarginRight;
        return $this;
    }

    /**
     * @return int
     */
    public function getSaleMarginTop():?int
    {
        return $this->saleMarginTop;
    }

    /**
     * @param int $saleMarginTop
     * @return Setting
     */
    public function setSaleMarginTop(?int $saleMarginTop):self
    {
        $this->saleMarginTop = $saleMarginTop;
        return $this;
    }

    /**
     * @return int
     */
    public function getReportFontSize():?int
    {
        return $this->reportFontSize;
    }

    /**
     * @param int $reportFontSize
     * @return Setting
     */
    public function setReportFontSize(?int $reportFontSize): self
    {
        $this->reportFontSize = $reportFontSize;
        return $this;
    }


    /**
     * @return int
     */
    public function getReportHeight():?int
    {
        return $this->reportHeight;
    }

    /**
     * @param int $reportHeight
     * @return Setting
     */
    public function setReportHeight(?int $reportHeight): self
    {
        $this->reportHeight = $reportHeight;
        return $this;
    }

    /**
     * @return int
     */
    public function getReportWidth():?int
    {
        return $this->reportWidth;
    }

    /**
     * @param int $reportWidth
     * @return Setting
     */
    public function setReportWidth(?int $reportWidth):self
    {
        $this->reportWidth = $reportWidth;
        return $this;
    }


    /**
     * @return bool
     */
    public function getWithExpiration():?bool
    {
        return $this->withExpiration;
    }

    /**
     * @param bool $withExpiration
     * @return Setting
     */
    public function setWithExpiration(?bool $withExpiration): self
    {
        $this->withExpiration = $withExpiration;
        return $this;
    }

    /**
     * @return bool
     */
    public function getProductWithImage():?bool
    {
        return $this->productWithImage;
    }

    /**
     * @param bool $productWithImage
     * @return Setting
     */
    public function setProductWithImage(?bool $productWithImage):self
    {
        $this->productWithImage = $productWithImage;
        return $this;
    }

    /**
     * @return bool
     */
    public function getProductWithDiscount():?bool
    {
        return $this->productWithDiscount;
    }

    /**
     * @param bool $productWithDiscount
     * @return Setting
     */
    public function setProductWithDiscount(?bool $productWithDiscount):self
    {
        $this->productWithDiscount = $productWithDiscount;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithBarcode():?bool
    {
        return $this->withBarcode;
    }

    /**
     * @param bool $withBarcode
     * @return Setting
     */
    public function setWithBarcode(?bool $withBarcode):self
    {
        $this->withBarcode = $withBarcode;
        return $this;
    }

    /**
     * @return bool
     */
    public function getGenerateBarcode():?bool
    {
        return $this->generateBarcode;
    }

    /**
     * @param bool $generateBarcode
     * @return Setting
     */
    public function setGenerateBarcode(?bool $generateBarcode):self
    {
        $this->generateBarcode = $generateBarcode;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithSubstitute():?bool
    {
        return $this->withSubstitute;
    }

    /**
     * @param bool $withSubstitute
     * @return Setting
     */
    public function setWithSubstitute(?bool $withSubstitute):self
    {
        $this->withSubstitute = $withSubstitute;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithBatchPrice():?bool
    {
        return $this->withBatchPrice;
    }

    /**
     * @param bool $withBatchPrice
     * @return Setting
     */
    public function setWithBatchPrice(?bool $withBatchPrice):self
    {
        $this->withBatchPrice = $withBatchPrice;
        return $this;
    }

    /**
     * @return int
     */
    public function getDaysBeforeExpiration():?int
    {
        return $this->daysBeforeExpiration;
    }

    /**
     * @param int $daysBeforeExpiration
     * @return Setting
     */
    public function setDaysBeforeExpiration(?int $daysBeforeExpiration):self
    {
        $this->daysBeforeExpiration = $daysBeforeExpiration;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithRawExpiration(): ?bool
    {
        return $this->withRawExpiration;
    }

    /**
     * @param bool $withRawExpiration
     * @return Setting
     */
    public function setWithRawExpiration(?bool $withRawExpiration):self
    {
        $this->withRawExpiration = $withRawExpiration;
        return $this;
    }

    /**
     * @return int
     */
    public function getDaysBeforeRawExpiration():?int
    {
        return $this->daysBeforeRawExpiration;
    }

    /**
     * @param int $daysBeforeRawExpiration
     * @return Setting
     */
    public function setDaysBeforeRawExpiration(?int $daysBeforeRawExpiration):self
    {
        $this->daysBeforeRawExpiration = $daysBeforeRawExpiration;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithPurchasePrice():?bool
    {
        return $this->withPurchasePrice;
    }

    /**
     * @param bool $withPurchasePrice
     * @return Setting
     */
    public function setWithPurchasePrice(?bool $withPurchasePrice):self
    {
        $this->withPurchasePrice = $withPurchasePrice;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithDiscount():?bool
    {
        return $this->withDiscount;
    }

    /**
     * @param bool $withDiscount
     * @return Setting
     */
    public function setWithDiscount(?bool $withDiscount):self
    {
        $this->withDiscount = $withDiscount;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithPartialPayment():?bool
    {
        return $this->withPartialPayment;
    }

    /**
     * @param bool $withPartialPayment
     * @return Setting
     */
    public function setWithPartialPayment(?bool $withPartialPayment):self
    {
        $this->withPartialPayment = $withPartialPayment;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPrintDirectly():?bool
    {
        return $this->printDirectly;
    }

    /**
     * @param bool $printDirectly
     * @return Setting
     */
    public function setPrintDirectly(?bool $printDirectly):self
    {
        $this->printDirectly = $printDirectly;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithGuiSale():?bool
    {
        return $this->withGuiSale;
    }

    /**
     * @param bool $withGuiSale
     * @return Setting
     */
    public function setWithGuiSale(?bool $withGuiSale):self
    {
        $this->withGuiSale = $withGuiSale;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithSoftDelete():?bool
    {
        return $this->withSoftDelete;
    }

    /**
     * @param bool $withSoftDelete
     * @return Setting
     */
    public function setWithSoftDelete(?bool $withSoftDelete):self
    {
        $this->withSoftDelete = $withSoftDelete;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithWholeSale():?bool
    {
        return $this->withWholeSale;
    }

    /**
     * @param bool $withWholeSale
     * @return Setting
     */
    public function setWithWholeSale(?bool $withWholeSale):self
    {
        $this->withWholeSale = $withWholeSale;
        return $this;
    }

    /**
     * @return int
     */
    public function getBarcodeWidth():?int
    {
        return $this->barcodeWidth;
    }

    /**
     * @param int $barcodeWidth
     * @return Setting
     */
    public function setBarcodeWidth(?int $barcodeWidth):self
    {
        $this->barcodeWidth = $barcodeWidth;
        return $this;
    }

    /**
     * @return int
     */
    public function getBarcodeHeight():?int
    {
        return $this->barcodeHeight;
    }

    /**
     * @param int $barcodeHeight
     * @return Setting
     */
    public function setBarcodeHeight(?int $barcodeHeight):self
    {
        $this->barcodeHeight = $barcodeHeight;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithSettlement():?bool
    {
        return $this->withSettlement;
    }

    /**
     * @param bool $withSettlement
     * @return Setting
     */
    public function setWithSettlement(?bool $withSettlement):self
    {
        $this->withSettlement = $withSettlement;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithAccounting():?bool
    {
        return $this->withAccounting;
    }

    /**
     * @param bool $withAccounting
     * @return Setting
     */
    public function setWithAccounting(?bool $withAccounting):self
    {
        $this->withAccounting = $withAccounting;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithHrm():?bool
    {
        return $this->withHrm;
    }

    /**
     * @param bool $withHrm
     * @return Setting
     */
    public function setWithHrm(?bool $withHrm):self
    {
        $this->withHrm = $withHrm;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithExpense():?bool
    {
        return $this->withExpense;
    }

    /**
     * @param bool $withExpense
     * @return Setting
     */
    public function setWithExpense(?bool $withExpense):self
    {
        $this->withExpense = $withExpense;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithProduction():?bool
    {
        return $this->withProduction;
    }

    /**
     * @param bool $withProduction
     * @return Setting
     */
    public function setWithProduction(?bool $withProduction):self
    {
        $this->withProduction = $withProduction;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWithClubPoint():?bool
    {
        return $this->withClubPoint;
    }

    /**
     * @param bool $withClubPoint
     * @return Setting
     */
    public function setWithClubPoint(?bool $withClubPoint):self
    {
        $this->withClubPoint = $withClubPoint;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxIntervalPeriod():?int
    {
        return $this->maxIntervalPeriod;
    }

    /**
     * @param integer $maxIntervalPeriod
     * @return Setting
     */
    public function setMaxIntervalPeriod($maxIntervalPeriod): self
    {
        $this->maxIntervalPeriod = $maxIntervalPeriod;
        return $this;
    }

    /**
     * @return integer
     */
    public function getRandomCharacter(): ?int
    {
        return $this->randomCharacter;
    }

    /**
     * @param integer $randomCharacter
     * @return Setting
     */
    public function setRandomCharacter(?int $randomCharacter): self
    {
        $this->randomCharacter = $randomCharacter;
        return $this;
    }

    public function getThemeId(): ?int
    {
        return $this->themeId;
    }

    public function setThemeId(?int $themeId): self
    {
        $this->themeId = $themeId;

        return $this;
    }

    public function getWithSaleReturn(): ?bool
    {
        return $this->withSaleReturn;
    }

    public function setWithSaleReturn(bool $withSaleReturn): self
    {
        $this->withSaleReturn = $withSaleReturn;

        return $this;
    }

    public function getWithStockReturn(): ?bool
    {
        return $this->withStockReturn;
    }

    public function setWithStockReturn(bool $withStockReturn): self
    {
        $this->withStockReturn = $withStockReturn;

        return $this;
    }

    public function getWithPackaging(): ?bool
    {
        return $this->withPackaging;
    }

    public function setWithPackaging(bool $withPackaging): self
    {
        $this->withPackaging = $withPackaging;

        return $this;
    }

    public function getWithStockGenerateNumInvoice(): ?bool
    {
        return $this->withStockGenerateNumInvoice;
    }

    public function setWithStockGenerateNumInvoice(bool $withStockGenerateNumInvoice): self
    {
        $this->withStockGenerateNumInvoice = $withStockGenerateNumInvoice;

        return $this;
    }

    public function getWithGapProduction(): ?bool
    {
        return $this->withGapProduction;
    }

    public function setWithGapProduction(bool $withGapProduction): self
    {
        $this->withGapProduction = $withGapProduction;

        return $this;
    }

    public function getWithProductReference(): ?bool
    {
        return $this->withProductReference;
    }

    public function setWithProductReference(bool $withProductReference): self
    {
        $this->withProductReference = $withProductReference;

        return $this;
    }

    public function getWithUserCategory(): ?bool
    {
        return $this->withUserCategory;
    }

    public function setWithUserCategory(bool $withUserCategory): self
    {
        $this->withUserCategory = $withUserCategory;

        return $this;
    }

    public function getWithSaleNumInvoice(): ?bool
    {
        return $this->withSaleNumInvoice;
    }

    public function setWithSaleNumInvoice(bool $withSaleNumInvoice): self
    {
        $this->withSaleNumInvoice = $withSaleNumInvoice;

        return $this;
    }
}
