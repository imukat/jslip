<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

define('MAX_LINES_PAR_PAGE', 40);

class tex_tmplt
{
    private $csvfile;
    private $dat;

    // for amount
    private function amount($x)
    {
        return ($x == 0) ? "" : "{\\tt{" . str_replace("-", "▲", number_format($x)) . "}}";
    }

    // for amount
    private function amount0($x)
    {
        return ($x == 0) ? 0 : "{\\tt{" . str_replace("-", "▲", number_format($x)) . "}}";
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
                case "ty":
                case "tax1":
                case "tax2":
                case "pprof":
                case "ploss":
                case "profit":
                case "rows":
                    $this->dat[$rec[0]] = trim($rec[1]);
                    break;
                case "field":
                    $this->dat[$rec[0]]["n"]          = trim($rec[1]);
                    $this->dat[$rec[0]]["m"]          = trim($rec[2]);
                    $this->dat[$rec[0]]["mm"]         = trim($rec[3]);
                    $this->dat[$rec[0]]["account_cd"] = trim($rec[4]);
                    $this->dat[$rec[0]]["name"]       = trim($rec[5]);
                    $this->dat[$rec[0]]["remain"]     = trim($rec[6]);
                    $this->dat[$rec[0]]["division"]   = trim($rec[7]);
                    break;
                case "data":
                    $this->dat[$rec[0]][$data_n]["n"]          = trim($rec[1]);
                    $this->dat[$rec[0]][$data_n]["m"]          = trim($rec[2]);
                    $this->dat[$rec[0]][$data_n]["mm"]         = trim($rec[3]);
                    $this->dat[$rec[0]][$data_n]["account_cd"] = trim($rec[4]);
                    $this->dat[$rec[0]][$data_n]["name"]       = trim($rec[5]);
                    $this->dat[$rec[0]][$data_n]["remain"]     = trim($rec[6]);
                    $this->dat[$rec[0]][$data_n]["division"]   = trim($rec[7]);
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
        $ty     = $this->dat["ty"];

        $tax1   = $this->dat["tax1"];
        $tax2   = $this->dat["tax2"];
        $pprof  = $this->dat["pprof"];
        $ploss  = $this->dat["ploss"];
        $profit = $this->dat["profit"];
        $xval   = $profit - $tax1 - $tax2 + $pprof + $ploss;
        $xnam   = ($xval < 0) ? "当期未処分損失" : "当期未処分利益";

        $m      = $d[$n]["m"];
        $mm     = $d[$n]["mm"];
        $inam   = $d[$n]["name"];

        $yyyy   = intval($m / 100);
        $term   = $this->term($bymd, $yyyy, $mm);
        $term0  = $term[0];
        $term1  = $term[1];

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{ccc}\n";
        echo "\\multicolumn{2}{l}{\\makebox[12.5cm][l]{" . $name . "}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{" . $era . "年度}} \\\\\n";
        echo "\\makebox[2.5cm][l]{ } & ";
        echo "\\makebox[10cm][c]{\\bf\\LARGE{" . $title . "}} & ";
        echo "\\makebox[2.5cm][r]{} \\\\\n";
        echo "\\makebox[2.5cm][l]{} & ";
        echo "\\makebox[10cm][c]{{\\tt{" . $term0 . " 〜 " . $term1 . "}}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{}} \\\\\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{@{\\Vline\\ }c|c|c|c@{\\ \\Vline}}\n";
        echo "\\Hline\n";
        echo "\\multicolumn{2}{@{\\Vline\\ }c|}{\\makebox[7.4cm][c]{\\bf{資　　産}}} & ";
        echo "\\multicolumn{2}{c@{\\ \\Vline}}{\\makebox[7.4cm][c]{\\bf{負　債 ・ 資　本}}} \\\\\n";
        echo "\\Hline\n";

        $max    = $n + MAX_LINES_PAR_PAGE;
        $month  = $mm;
        $drec   = array();
        $n0     = 0;
        $n1     = 0;
        while (true) {
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

            switch ($d[$n]["name"]) {
                case "未払消費税":
                case "未払法人税等":
                case "前期繰越利益":
                case "前期繰越損失":
                case "当期利益":
                    break;
                default:
                    if ($d[$n]["division"]) {
                        $drec[$n1][1]["name"]   = $d[$n]["name"];
                        $drec[$n1][1]["remain"] = $d[$n]["remain"];
                        $n1++;
                    } else {
                        $drec[$n0][0]["name"]   = $d[$n]["name"];
                        $drec[$n0][0]["remain"] = $d[$n]["remain"];
                        $n0++;
                    }
                    break;
            }

            $n++;
        }

        $drec[$n1][1]["name"]   = $xnam;
        $drec[$n1][1]["remain"] = $xval;
        $n1++;

        $dsum = 0;
        $csum = 0;
        $nmax = max($n0, $n1);
        for ($i = 0; $i < $nmax; $i++) {
            if ($i < $n0) {
                $dn = $drec[$i][0]["name"];
                $dr = $this->amount($drec[$i][0]["remain"]);
                $dsum += $drec[$i][0]["remain"];
            } else {
                $dn = "";
                $dr = "";
            }

            if ($i < $n1) {
                $cn = $drec[$i][1]["name"];
                $cr = $this->amount($drec[$i][1]["remain"]);
                $csum += $drec[$i][1]["remain"];
            } else {
                $cn = "";
                $cr = "";
            }

            echo "\\makebox[3.7cm][l]{" . $dn . "} & ";
            echo "\\makebox[3.7cm][r]{" . $dr . "} & ";
            echo "\\makebox[3.7cm][l]{" . $cn . "} & ";
            echo "\\makebox[3.7cm][r]{" . $cr . "} \\\\\n";
        }

        if ($flg) {
            $dr = $this->amount0($dsum);
            $cr = $this->amount0($csum);

            echo "\\Hline\n";
            echo "\\makebox[3.7cm][c]{\bf{合　計}} & ";
            echo "\\makebox[3.7cm][r]{" . $dr . "} & ";
            echo "\\makebox[3.7cm][c]{\bf{合　計}} & ";
            echo "\\makebox[3.7cm][r]{" . $cr . "} \\\\\n";
        }

        echo "\\Hline\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\n";

        return $n;
    }

    // make a result
    public function make_a_result()
    {
        $tax1   = $this->dat["tax1"];
        $tax2   = $this->dat["tax2"];
        $pprof  = $this->dat["pprof"];
        $ploss  = $this->dat["ploss"];
        $profit = $this->dat["profit"];
        $prof2  = $profit - $tax1 - $tax2;
        $x      = $pprof - $ploss;
        $y      = $x + $prof2;
        $nam1   = ($x < 0) ? "期首繰越損失" : "期首繰越利益";
        $nam2   = ($y < 0) ? "当期未処分損失" : "当期未処分利益";

        echo "\\bigskip\n";
        echo "\n";
        echo "\\begin{center}\n";
        echo "\\begin{tabular}{ccc}\n";
        echo "\\multicolumn{3}{c}{\\makebox[8cm][l]{" . $nam2 . "の内訳}} \\\\\n";
        echo "\\multicolumn{3}{c}{\\makebox[8cm][l]{}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{当期利益}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($profit) . "}}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{未払法人税等}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($tax1) . "}}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{未払消費税}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($tax2) . "}}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{税引後当期利益}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($prof2) . "}}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{" . $nam1 . "}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($x) . "}}} \\\\\n";
        echo "\\multicolumn{1}{c}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[4cm][l]{" . $nam2 . "}} & ";
        echo "\\multicolumn{1}{c}{\\makebox[3cm][r]{\\tt{" . $this->amount0($y) . "}}} \\\\\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\n";
        echo "\\newpage\n";
    }

    // check data for debug
    public function chk_dat()
    {
        echo "title = "  . $this->dat["title"]  ."\n\n";
        echo "name = "   . $this->dat["name"]   ."\n\n";
        echo "era = "    . $this->dat["era"]    ."\n\n";
        echo "bymd = "   . $this->dat["bymd"]   ."\n\n";
        echo "ty = "     . $this->dat["ty"]     ."\n\n";
        echo "tax1 = "   . $this->dat["tax1"]   ."\n\n";
        echo "tax2 = "   . $this->dat["tax2"]   ."\n\n";
        echo "pprof = "  . $this->dat["pprof"]  ."\n\n";
        echo "ploss = "  . $this->dat["ploss"]  ."\n\n";
        echo "profit = " . $this->dat["profit"] ."\n\n";
        echo "rows = "   . $this->dat["rows"]   ."\n\n";

        echo $this->dat["field"]["n"]          . ", " .
             $this->dat["field"]["m"]          . ", " .
             $this->dat["field"]["mm"]         . ", " .
             $this->dat["field"]["account_cd"] . ", " .
             $this->dat["field"]["name"]       . ", " .
             $this->dat["field"]["remain"]     . ", " .
             $this->dat["field"]["division"]   . "\n\n";

        $cnt = $this->dat["rows"];
        for ($i = 0; $i < $cnt; $i++) {
            echo $this->dat["data"][$i]["n"]          . ", " .
                 $this->dat["data"][$i]["m"]          . ", " .
                 $this->dat["data"][$i]["mm"]         . ", " .
                 $this->dat["data"][$i]["account_cd"] . ", " .
                 $this->dat["data"][$i]["name"]       . ", " .
                 $this->dat["data"][$i]["remain"]     . ", " .
                 $this->dat["data"][$i]["division"]   . "\n\n";
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

$my  = new tex_tmplt($argv[1]);
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
    $n   = 0;
    $cnt = $dat["rows"];
    while ($n < $cnt)
        $n = $my->make_a_page($n);

    $my->make_a_result();
}
?>

\end{document}
