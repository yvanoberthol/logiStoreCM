<?php


namespace App\Extension;


use App\Entity\Addons;
use App\Entity\Setting;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /**
     * @var Setting
     */
    private $setting;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * AppExtension constructor.
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(RequestStack $requestStack,
                                EntityManagerInterface $entityManager)
    {
        $this->setting = $requestStack->getSession()->get('setting');
        $this->entityManager = $entityManager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('customCurrency', [$this, 'formatPrice']),
            new TwigFilter('formated', [$this, 'formatNumber']),
            new TwigFilter('formatedInt', [$this, 'formatInteger']),
            new TwigFilter('unitSize', [$this, 'formatBytes']),
            new TwigFilter('doubleformated', [$this, 'doubleformatNumber']),
            new TwigFilter('strpad', [$this, 'strpad']),
            new TwigFilter('shortDate', [$this, 'shortDate']),
            new TwigFilter('mediumDate', [$this, 'mediumDate']),
            new TwigFilter('mediumDefaultDate', [$this, 'mediumDefaultDate']),
            new TwigFilter('longDate', [$this, 'longDate'])
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('moduleExists', [$this, 'moduleExists'])
        ];
    }

    public function formatPrice($number)
    {
        $price = number_format((int) $number,
            (int) $this->setting->getCurrencyDecimal(), '.',
            $this->setting->getCurrencyThousandSeparator());

        if ($this->setting->getCurrencySide() === "left"){
            $price = $this->setting->getCurrencyName().' '.$price;
        }else{
            $price .= ' '.$this->setting->getCurrencyName();
        }

        return $price;
    }

    public function formatNumber($number,$decimal=0)
    {
        $numberTab = explode('.',$number);
        $decimalPart = ( $decimal > 0 && count($numberTab) > 1)?(int) $numberTab[1]:0;

        $decimal = ($decimalPart === 0 || $decimal === 0)?(int) $this->setting->getCurrencyDecimal():$decimal;
        return  number_format($number,$decimal, '.', $this->setting->getCurrencyThousandSeparator());
    }



    public function formatInteger($number)
    {
        return  number_format((int) $number,0, '.', $this->setting->getCurrencyThousandSeparator());
    }

    public static function formatBytes($number, $precision = 2)
    {
        $base = log($number, 1024);
        $suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');
        return round(1024 ** ($base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public function doubleformatNumber($number)
    {
        return  number_format($number, 2, '.', $this->setting->getCurrencyThousandSeparator());
    }

    public function strpad($number,$pad_length, $pad_string){
        return str_pad($number,$pad_length,$pad_string,STR_PAD_LEFT);
    }

    /**
     * @param $date
     * @return false|string
     * @throws Exception
     */
    public function shortDate($date)
    {
        if (!$date instanceof DateTime)
            $date = new DateTime($date);

        return $date->format($this->setting->getDateShort());
    }

    /**
     * @param $date
     * @return false|string
     * @throws Exception
     */
    public function mediumDate($date)
    {
        if (!$date instanceof DateTime)
            $date = new DateTime($date);

        return $date->format($this->setting->getDateMedium());
    }

    /**
     * @param $date
     * @return string
     * @throws Exception
     */
    public function mediumDefaultDate($date): string
    {
        if (!$date instanceof DateTime)
            $date = new DateTime($date);

        return $date->format('Y-m-d');
    }


    /**
     * @param $date
     * @return false|string
     * @throws Exception
     */
    public function longDate($date)
    {
        if (!$date instanceof DateTimeInterface && !$date instanceof DateInterval
            && !$date instanceof DateTimeImmutable)
            return date($this->setting->getDateLong(),strtotime($date));

        return $date->format($this->setting->getDateLong());
    }

    public function moduleExists($identifier): bool
    {
        $addonRepository = $this->entityManager->getRepository(Addons::class);
        $addons = $addonRepository
            ->findOneBy(['identifier' => $identifier,'enabled' => true]);
        //dd($addons);

        return ($addons !== null);
    }
}
