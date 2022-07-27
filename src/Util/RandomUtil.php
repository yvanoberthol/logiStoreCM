<?php


namespace App\Util;


use Exception;

final class RandomUtil
{

    /**
     * @param $size
     * @return string
     * @throws Exception
     */
    public static function randomString($size): string
    {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';

            for ($i = 0; $i < $size; $i++) {
                $index = random_int(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }

            return $randomString;
    }

    /**
     * @param $size
     * @return string
     * @throws Exception
     */
    public static function randomNumber($size): string
    {
        $characters = '0123456789';
        $randomString = '';

        for ($i = 0; $i < $size; $i++) {
            $index = random_int(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

}
