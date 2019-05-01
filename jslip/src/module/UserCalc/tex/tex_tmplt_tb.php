<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

define('MAX_LINES_PAR_PAGE', 40);

class ledger_tmplt
{
    private $csvfile;
    private $dat;

    // for amount
    private function amount($x)
    {
        return ($x == 0) ? "" : "{\\tt{" . str_replace("-", "▲", number_format($x)) . "}}";
    }

    // get term (month)
    private function term($bymd, $y, $m)
    {
        $yyyymm = intval($bymd / 100);
        $by     = intval($yyyymm / 100);
        $bm     = $yyyymm % 100;

        $a      = array();
        $a[0]   = date("Y/m/d", mktime(0, 0, 0, $bm, 1, $by));
        $a[1]   = date("Y/m/d", mktime(0, 0, 0, $m + 1, 0, $y));

        return $a;
    }

    // How many pages.
    private function pages($n, $mm)
    {
        $cnt = $this->dat["rows"];
        $d   = $this->dat["data"];
        $x   = 0;
        for ($i = 0; $i < $cnt; $i++) {
            if ($mm == $d[$i]["mm"]) {
                $x++;
            }
        }

        $p = intval($x / MAX_LINES_PAR_PAGE);
        $y = $x % MAX_LINES_PAR_PAGE;
        if ($y) {
            $p++;
        }

        $z    = intval(($n % $x) / MAX_LINES_PAR_PAGE) + 1;
        $a    = array();
        $a[0] = $z;
        $a[1] = $p;

        return $a;
    }

    // calculation
    private function sum($mm)
    {
        $s[0] = 0;
        $s[1] = 0;
        $s[2] = "合計";
        $s[3] = 0;
        $s[4] = 0;

        $cnt = $this->dat["rows"];
        $d   = $this->dat["data"];

        for ($i = 0; $i < $cnt; $i++) {
            if ($mm == $d[$i]["mm"]) {
                $s[0] += $d[$i]["debit_remain"];
                $s[1] += $d[$i]["debit_sum"];
                $s[3] += $d[$i]["credit_sum"];
                $s[4] += $d[$i]["credit_remain"];
            }
        }

        return $s;
    }

    // TABセパレータCSVファイルからデータを取得し$datにデータを設定します。
    private function set_dat()
    {
        $csv    = file($this->csvfile); // TABセパレータCSVファイル・データ読み込み
        $data_n = 0;
        $cnt    = count($csv);
        for ($i = 0; $i < $cnt; $i++) {
            $rec = explode("\t", $csv[$i]);
            switch ($rec[0]) {
                case "title":
                case "name":
                case "era":
                case "bymd":
                case "rows":
                    $this->dat[$rec[0]] = trim($rec[1]);
                    break;
                case "field":
                    $this->dat[$rec[0]]["n"]             = trim($rec[1]);
                    $this->dat[$rec[0]]["m"]             = trim($rec[2]);
                    $this->dat[$rec[0]]["mm"]            = trim($rec[3]);
                    $this->dat[$rec[0]]["ctg_div"]       = trim($rec[4]);
                    $this->dat[$rec[0]]["division"]      = trim($rec[5]);
                    $this->dat[$rec[0]]["ccd"]           = trim($rec[6]);
                    $this->dat[$rec[0]]["item"]          = trim($rec[7]);
                    $this->dat[$rec[0]]["debit_remain"]  = trim($rec[8]);
                    $this->dat[$rec[0]]["debit_sum"]     = trim($rec[9]);
                    $this->dat[$rec[0]]["name"]          = trim($rec[10]);
                    $this->dat[$rec[0]]["credit_sum"]    = trim($rec[11]);
                    $this->dat[$rec[0]]["credit_remain"] = trim($rec[12]);
                    break;
                case "data":
                    $this->dat[$rec[0]][$data_n]["n"]             = trim($rec[1]);
                    $this->dat[$rec[0]][$data_n]["m"]             = trim($rec[2]);
                    $this->dat[$rec[0]][$data_n]["mm"]            = trim($rec[3]);
                    $this->dat[$rec[0]][$data_n]["ctg_div"]       = trim($rec[4]);
                    $this->dat[$rec[0]][$data_n]["division"]      = trim($rec[5]);
                    $this->dat[$rec[0]][$data_n]["ccd"]           = trim($rec[6]);
                    $this->dat[$rec[0]][$data_n]["item"]          = trim($rec[7]);
                    $this->dat[$rec[0]][$data_n]["debit_remain"]  = trim($rec[8]);
                    $this->dat[$rec[0]][$data_n]["debit_sum"]     = trim($rec[9]);
                    $this->dat[$rec[0]][$data_n]["name"]          = trim($rec[10]);
                    $this->dat[$rec[0]][$data_n]["credit_sum"]    = trim($rec[11]);
                    $this->dat[$rec[0]][$data_n]["credit_remain"] = trim($rec[12]);
                    $data_n++;
                    break;
            }
        }
    }

    // 主処理
    public function main()
    {
        $this->set_dat();
    }

    // make a page
    public function make_a_page($n)
    {
        $d      = $this->dat["data"];
        $title  = $this->dat["title"];
        $name   = $this->dat["name"];
        $era    = $this->dat["era"];
        $bymd   = $this->dat["bymd"];
        $m      = $d[$n]["m"];
        $mm     = $d[$n]["mm"];
        $item   = $d[$n]["item"];
        $inam   = $d[$n]["name"];

        $yyyy   = intval($m / 100);
        $term   = $this->term($bymd, $yyyy, $mm);
        $term0  = $term[0];
        $term1  = $term[1];

        $p = $this->pages($n, $mm);

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{ccc}\n";
        echo "\\multicolumn{2}{l}{\\makebox[12.5cm][l]{" . $name . "}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{" . $era . "年度}} \\\\\n";
        echo "\\makebox[2.5cm][l]{} & ";
        echo "\\makebox[10cm][c]{\\bf\\LARGE{" . $title . "}} & ";
        echo "\\makebox[2.5cm][r]{" . $mm . "月度} \\\\\n";
        echo "\\makebox[2.5cm][l]{} & ";
        echo "\\makebox[10cm][c]{{\\tt{" . $term0 . " 〜 " . $term1 . "}}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{" . $p[0] . "/" . $p[1] . "}} \\\\\n";
        echo "\\end{tabular}\n";

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{@{\\Vline\\ }c|c|c|c|c@{\\ \\Vline}}\n";
        echo "\\Hline\n";
        echo "\\multicolumn{2}{@{\\Vline\\ }c|}{\\makebox[4.4cm][c]{\\bf{借方}}} & ";
        echo "\\multirow{2}{*}{\\makebox[5cm][c]{\\bf{摘要}}} & ";
        echo "\\multicolumn{2}{c@{\\ \\Vline}}{\\makebox[4.4cm][c]{\\bf{貸方}}} \\\\\n";
        echo "\\cline{1-2}\\cline{4-5}\n";
        echo "\\makebox[2.2cm][c]{\\bf{当期残高}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{当月合計}} & ";
        echo "\\makebox[5cm][c]{\\bf{}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{当月合計}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{当期残高}} \\\\\n ";
        echo "\\Hline\n";

        $max   = $n + MAX_LINES_PAR_PAGE;
        $month = $mm;
        while (true)
        {
            if ($n >= $max) {
                $flg = 0;
                break;
            }

            if (!isset($d[$n]["mm"])) {
                $flg = 1;
                break;
            } else if ($month != $d[$n]["mm"]) {
                $flg = 1;
                break;
            }

            $dr  = $d[$n]["debit_remain"];
            $ds  = $d[$n]["debit_sum"];
            $nam = $d[$n]["name"];
            $cs  = $d[$n]["credit_sum"];
            $cr  = $d[$n]["credit_remain"];

            $dr = ($dr == 0) ? "" : $this->amount($dr);
            $ds = ($ds == 0) ? "" : $this->amount($ds);
            $cs = ($cs == 0) ? "" : $this->amount($cs);
            $cr = ($cr == 0) ? "" : $this->amount($cr);

            echo "\\makebox[2.2cm][r]{" . $dr  . "} & ";
            echo "\\makebox[2.2cm][r]{" . $ds  . "} & ";
            echo "\\makebox[5.0cm][c]{" . $nam . "} & ";
            echo "\\makebox[2.2cm][r]{" . $cs  . "} & ";
            echo "\\makebox[2.2cm][r]{" . $cr  . "} \\\\\n";

            $n++;
        }

        if ($flg) {
            $s  = $this->sum($mm);

            $dr  = $s[0];
            $ds  = $s[1];
            $nam = $s[2];
            $cs  = $s[3];
            $cr  = $s[4];

            $dr  = ($dr == 0) ? "" : $this->amount($dr);
            $ds  = ($ds == 0) ? "" : $this->amount($ds);
            $nam = "{\\bf{" . $nam . "}}";
            $cs  = ($cs == 0) ? "" : $this->amount($cs);
            $cr  = ($cr == 0) ? "" : $this->amount($cr);

            echo "\\Hline\n";
            echo "\\makebox[2.2cm][r]{" . $dr  . "} & ";
            echo "\\makebox[2.2cm][r]{" . $ds  . "} & ";
            echo "\\makebox[5.0cm][c]{" . $nam . "} & ";
            echo "\\makebox[2.2cm][r]{" . $cs  . "} & ";
            echo "\\makebox[2.2cm][r]{" . $cr  . "} \\\\\n";
        }

        echo "\\Hline\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\\newpage\n";

        return  $n;
    }

    // check data for debug
    public function chk_dat()
    {
        echo "title = " . $this->dat["title"] . "\n\n";
        echo "name = "  . $this->dat["name"]  . "\n\n";
        echo "era = "   . $this->dat["era"]   . "\n\n";
        echo "bymd = "  . $this->dat["bymd"]  . "\n\n";
        echo "rows = "  . $this->dat["rows"]  . "\n\n";

        echo $this->dat["field"]["n"]             . ", " .
             $this->dat["field"]["m"]             . ", " .
             $this->dat["field"]["mm"]            . ", " .
             $this->dat["field"]["ctg_div"]       . ", " .
             $this->dat["field"]["division"]      . ", " .
             $this->dat["field"]["ccd"]           . ", " .
             $this->dat["field"]["item"]          . ", " .
             $this->dat["field"]["debit_remain"]  . ", " .
             $this->dat["field"]["debit_sum"]     . ", " .
             $this->dat["field"]["name"]          . ", " .
             $this->dat["field"]["credit_sum"]    . ", " .
             $this->dat["field"]["credit_remain"] . "\n\n";

        $cnt  = $this->dat["rows"];
        for ($i = 0; $i < $cnt; $i++) {
            echo $this->dat["data"][$i]["n"]             . ", " .
                 $this->dat["data"][$i]["m"]             . ", " .
                 $this->dat["data"][$i]["mm"]            . ", " .
                 $this->dat["data"][$i]["ctg_div"]       . ", " .
                 $this->dat["data"][$i]["division"]      . ", " .
                 $this->dat["data"][$i]["ccd"]           . ", " .
                 $this->dat["data"][$i]["item"]          . ", " .
                 $this->dat["data"][$i]["debit_remain"]  . ", " .
                 $this->dat["data"][$i]["debit_sum"]     . ", " .
                 $this->dat["data"][$i]["name"]          . ", " .
                 $this->dat["data"][$i]["credit_sum"]    . ", " .
                 $this->dat["data"][$i]["credit_remain"] . "\n\n";
        }
    }

    // 表示データ取得
    public function get_dat()
    {
        return $this->dat;
    }

    // 明示的コンストラクタ
    public function __construct($filename)
    {
        $this->csvfile = $filename;
        $this->dat     = array();
    }
}

$my  = new ledger_tmplt($argv[1]);
$my->main();
$dat = $my->get_dat();
?>
\documentclass[a4j]{jarticle}

\usepackage{supertabular}
\usepackage{multirow}

\pagestyle{plain}

\topmargin -25mm
\oddsidemargin 0mm
\evensidemargin 0mm
\textheight 260mm
\textwidth 160mm
\parindent 1zw
\parskip   0.5zw

% 太い罫線のために
\def\Hline{\noalign{\hrule height .5mm}}
\def\Vline{\vrule width .5mm}

\begin{document}

<?php
if (0) {
    $my->chk_dat();
} else {
    $n = 0;
    $cnt = $dat["rows"];
    while ($n < $cnt)
        $n = $my->make_a_page($n);
}
?>

\end{document}
