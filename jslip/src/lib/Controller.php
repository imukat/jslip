<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

class Controller
{
    public function chkYmd($dt)
    {
        $dt  = str_replace('/', '-', $dt);
        $ymd = explode('-', $dt);

        if (empty($ymd[2])) {
            return false;
        }

        return checkdate($ymd[1], $ymd[2], $ymd[0]);
    }
}
