<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once('config.php');
require_once('local.php');

class Init
{
    public $sts   = 'NG';
    public $func  = FUNC;
    public $param = [];

    public function getFunc() {

        if (empty($this->param['func'])) {
            $func = DEF_FUNC;
        } else {
            if (in_array($this->param['func'], $this->func)) {
                $func = $this->param['func'];
            } else {
                $func = DEF_FUNC;
            }
        }

        return $func;
    }

    function __construct($root, $get, $post) {

        switch (TARGET) {
            case 'dev':
            case 'staging':
            case 'product':
                $this->sts = 'OK';
                break;
            default:
                $this->sts = 'NG_TARGET';
                break;
        }

        if (!empty($get)) {
            $this->param = $get;
        }

        if (!empty($post)) {
            if (empty($this->param)) {
                $this->param = $post;
            } else {
                foreach ($post as $k => $d) {
                    $this->param[$k] = $d;
                }
            }
        }

        $this->param['root']     = $root;
        $this->param['url_base'] = URL_BASE;
        $this->param['tmp_dir']  = $root . '/tmp/' . TMP_DIR;
    }
}
