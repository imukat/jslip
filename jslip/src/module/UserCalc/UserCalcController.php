<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/../../lib/Tex.php');
require_once(dirname(__FILE__) . '/UserCalcModel.php');

class UserCalcController extends Controller
{
    public $rest;
    public $param;
    public $model;
    public $view;
    public $viewName;
    public $tex;
    public $pdf;
    public $tsv;
    public $tmplt;
    public $err;
    public $bid;
    public $basic;
    public $begin;
    public $end;
    public $lcdt;

    public function main($param) {

        $this->rest  = '';
        $this->pdf   = '';
        $this->param = $param;
        $this->bid   = $_SESSION['minfo']['bid'];
        $this->model = new UserCalcModel($this->bid);
        $this->view  = new View();
        $this->tex   = new Tex();

        $this->param['base'] = dirname(__FILE__);

        $basic = $this->model->getBasicByBid($this->bid);

        if (empty($basic[0])) {
            $this->_error('基本情報が見つかりません。');
            return;
        } else {
            $this->basic = $basic[0];
        }

        $this->begin = str_replace('-', '/', $this->basic['term_begin']);
        $this->end   = str_replace('-', '/', $this->basic['term_end']);
        $this->lcdt  = str_replace('-', '/', $this->model->lastCalculatedDate);

        if (empty($this->param['act'])) {
            $this->_menu();
        } else {
            switch ($this->param['act']) {
                case 'calculate': $this->_calculate(); break;
                case 'slip':      $this->_slip();      break;
                case 'ledger':    $this->_ledger();    break;
                case 'tb_detail': $this->_tb_detail(); break;
                case 'tb':        $this->_tb();        break;
                case 'pl':        $this->_pl();        break;
                case 'bs':        $this->_bs();        break;
                case 'pls':       $this->_pls();       break;
                case 'bss':       $this->_bss();       break;
                default:          $this->_menu();      break;
            }
        }
    }

    public function dispPdf() {
        $this->_dispPdf();
        $this->_clearFiles();
    }

    private function _error($err) {
        $this->viewName = 'user_calc_err';
        $this->err      = $err;
    }

    private function _menu() {
        $this->viewName = 'user_calc_menu';
    }

    private function _calculate() {

        $err = $this->model->calculate($this->basic);

        if (empty($err)) {
            $this->lcdt = str_replace('-', '/', $this->model->lastCalculatedDate);
        } else {
            $this->_error($err);
            return;
        }

        $this->viewName = 'user_calc_menu';
    }

    private function _slip() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_slip.php';

        $this->model->setTsvSlip($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _ledger() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_ledger.php';

        $this->model->setTsvLedger($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _tb_detail() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_tb_detail.php';

        $this->model->setTsvTbDetail($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _tb() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_tb.php';

        $this->model->setTsvTb($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _pl() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_pl.php';

        $this->model->setTsvPl($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _bs() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_bs.php';

        $this->model->setTsvBs($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _pls() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_pls.php';

        $this->model->setTsvPls($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _bss() {

        $this->pdf   = $this->param['tmp_dir'] . '/' . $this->tex->pdf;
        $this->tsv   = $this->param['tmp_dir'] . '/' . $this->tex->tsv;
        $this->tmplt = $this->param['base']    . '/tex/tex_tmplt_bss.php';

        $this->model->setTsvBss($this->basic, $this->tsv);
        $this->tex->makePdf($this->param['tmp_dir'], $this->tmplt, $this->tsv);
    }

    private function _dispPdf() {
        header('Content-type: application/pdf');
        header('Content-Length: ' . filesize($this->pdf));
        header('Content-Disposition: inline; filename=' . basename($this->pdf));
        readfile($this->pdf);
    }

    private function _clearFiles() {
        exec(UTL_DELETE . ' -f ' . $this->param['tmp_dir'] . '/' . $this->tex->uid . '.*');
    }
}
