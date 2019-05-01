<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

session_start();

$root = dirname(__FILE__);

require_once($root . '/src/index.php');

$init = new Init($root, $_GET, $_POST);

if ($init->sts != 'OK') {
    switch ($init->sts) {
        case 'NG_TARGET':
            echo 'Illegal TARGET. Check src/local.php .';
            break;
        defalt:
            echo 'Failed at initilize process.';
            break;
    }
    exit(1);
}

$func = $init->getFunc();
$cnam = $func . 'Controller';

require_once($root . '/src/module/' . $func . '/' . $cnam . '.php');

$ctrl = new $cnam();
$ctrl->main($init->param);

if (empty($ctrl->rest)) {
    if (empty($ctrl->csv)) {
        if (empty($ctrl->pdf)) {
            if (empty($ctrl->singleViewName)) {
                require_once($root . '/src/view/pre.tmplt');
                require_once($root . '/src/module/' . $func . '/view/' . $ctrl->viewName . '.tmplt');
                require_once($root . '/src/view/post.tmplt');
            } else {
                require_once($root . '/src/module/' . $func . '/view/' . $ctrl->singleViewName . '.tmplt');
            }
        } else {
            $ctrl->dispPdf();
        }
    } else {
        $ctrl->dispCsv();
    }
} else {
    echo $ctrl->rest;
}
