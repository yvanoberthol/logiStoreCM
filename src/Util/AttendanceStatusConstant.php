<?php


namespace App\Util;


class AttendanceStatusConstant
{
    public const STATUS = [
        "absent" => 'A',
        "late" => 'LA',
        "holiday" => 'H', //férié
        'leave' => 'LE', //permission, autorisation
    ];

    public const STATUSALERT = [
        "A" => ['absent','badge badge-danger'],
        "LA" => ['late','badge badge-warning'],
        "H" => ['holiday','badge badge-info'], //férié
        'LE' => ['leave','badge badge-primary'], //permission, autorisation
    ];

}
