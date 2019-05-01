<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

class View
{
    public function valid($v) {
        return ($v) ? '&#9711;' : '&#x2613;';
    }

    public function validSelect($v) {
        $opt = '<option value="true"'  . (($v) ? ' selected' : '') . '>&#9711;</option>'
             . '<option value="false"' . (($v) ? '' : ' selected') . '>&#x2613;</option>'
             ;
        return $opt;
    }

    public function calendarOption() {
        $opt = '<option value="ac">西暦</option>'
             . '<option value="japanese">和暦</option>'
             ;
        return $opt;
    }

    public function str($str) {
        return htmlspecialchars($str);
    }

    public function strDate($date) {
        return htmlspecialchars(str_replace('-', '/', $date));
    }

    public function strBasic($basic) {
        return $this->str($basic['disp_name'])
            . ' ' . $this->str($basic['era']['name']) . '年度'
            . '（' . $this->strDate($basic['term_begin'])
            . '～' . $this->strDate($basic['term_end']) . '）'
            ;
    }

    public function week($ymd) {
        $datetime = new DateTime($ymd);
        $week     = array("日", "月", "火", "水", "木", "金", "土");
        $w        = (int)$datetime->format('w');
        return $week[$w];
    }

    public function checkFlag($flg) {
        return (empty($flg)) ? '-' : '&#9711;';
    }

    public function settledFlag($flg, $settled) {
        return (empty($flg)) ? '-' : $settled[$flg];
    }
}
