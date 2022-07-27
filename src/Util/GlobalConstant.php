<?php


namespace App\Util;


use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class GlobalConstant
{
    public const OUTOFDATE = "out of date";

    public static function getExtension($fileName){
        $ext= explode('.', $fileName);
        return end($ext);
    }

    public static function getValueByType($value,$type){
        switch ($type){
            case 'boolean':
                return (bool) $value;
                break;
            case 'integer':
                return (int) preg_replace('/\s+/','',$value);
                break;
            case 'double':
                return (double) $value;
                break;
            default:
                return $value;
                break;
        }
    }
    public static function getValueIfEmpty($type){
        switch ($type){
            case 'boolean':
                return false;
                break;
            case 'integer':
                return 0;
                break;
            case 'double':
                return 0.0;
                break;
            default:
                return '';
                break;
        }
    }

    public static function getMonthsAndYear(Request $request,$model = []): array
    {
        $model['months'] = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $model['monthNow'] = $request->get('month') ?? date('n');
        $model['year'] = $request->get('year') ?? date('Y');

        return $model;
    }

    /**
     * @param $start
     * @param $end
     * @return bool
     * @throws Exception
     */
    public static function compareDate($start, $end): bool
    {

        if (!$start instanceof DateTime){
            $start = new DateTime($start);
        }

        if (!$end instanceof DateTime){
            $end = new DateTime($end);
        }

        return ($end->getTimestamp() >= $start->getTimestamp());
    }

    /**
     * @param $start
     * @param $end
     * @return int
     * @throws Exception
     */
    public static function getInterval($start, $end): int
    {

        if (!$start instanceof DateTime){
            $start = new DateTime($start);
        }

        if (!$end instanceof DateTime){
            $end = new DateTime($end);
        }

        $startFormated= date_create($start->format('Y-m-d'));
        $endFormated = date_create($end->format('Y-m-d'));

        $interval = date_diff($endFormated,$startFormated);
        $intervalFormated = $interval->days;
        if ($start > $end){
            $intervalFormated = '-'.$intervalFormated;
        }

        return (int) $intervalFormated;
    }

    /**
     * @param $start
     * @param int $hourLimit
     * @return bool
     * @throws Exception
     */
    public static function limitPassed($start,int $hourLimit): bool
    {

        if (!$start instanceof DateTime){
            $start = new DateTime($start);
        }
        $today =(new DateTime())->getTimestamp();

        $timestampDayLimit = $hourLimit * 3600;

        $timeLimit = $start->getTimeStamp() + $timestampDayLimit;

        return ($today > $timeLimit);
    }
}
