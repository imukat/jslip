<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

class Tex
{
    public $uid;             // ユニークID
    public $tsv, $tex, $dvi; // ファイル名
    public $pdf, $log;       // ファイル名

    // PDFファイル作成
    public function makePdf($wd, $tmplt, $tsv)
    {
        // コマンド作成
        $cmd1 = UTL_PHP . ' -f ' . $tmplt . ' ' . $tsv;
        $cmd2 = UTL_PLATEX . ' ' . $this->tex;
        $cmd3 = './nostderr ' . UTL_DVIPDFMX . ' ' . $this->dvi;

        $cwd = getcwd();    // 現在のディレクトリを憶えておきます。
        chdir($wd);         // PDF作成作業ディレクトリに移動します。

        // TeXファイル作成
        ob_start();
        passthru($cmd1);
        $var = ob_get_contents();
        ob_end_clean();
        file_put_contents($this->tex, $var);

        // PDFファイル作成（結果はファイルlogに書き込みます。）
        ob_start();
        passthru($cmd2);
        passthru($cmd3);
        $var = ob_get_contents();
        ob_end_clean();
        file_put_contents($this->log, $var);

        chdir($cwd); // get back to where you once belong.
    }

    public function __construct()
    {
        $uid        = uniqid('_pdf_');
        $this->uid  = $uid;
        $this->tsv  = $uid . '.tsv';  // tmpltファイルが必要とするデータファイル名。
        $this->tex  = $uid . '.tex';
        $this->dvi  = $uid . '.dvi';
        $this->pdf  = $uid . '.pdf';
        $this->log  = $uid . '.log';
    }
}
