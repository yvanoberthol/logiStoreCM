<?php


namespace App\Util;


class PackageConstant
{

    public const MENSUAL = "MENSUAL";
    public const TRIMESTRIAL = "TRIMESTRIAL";
    public const SEMESTRIAL = "SEMESTRIAL";
    public const ANNUAL = "ANNUAL";

    public const PACKAGES = [
        'MENSUAL'=>30,
        'TRIMESTRIAL'=>90,
        'SEMESTRIAL'=>180,
        'ANNUAL'=>360,
    ];

}
