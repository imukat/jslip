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

        return  $a;
    }

    // calculation
    private function sum($mm)
    {
        $s[0] = 0;
        $s[1] = 0;

        $cnt  = $this->dat["rows"];
        $d    = $this->dat["data"];

        for ($i = 0; $i < $cnt; $i++) {
            if ($mm == $d[$i]["mm"]) {
                $r  = $d[$i]["remain"];
                if ($d[$i]["division"]) {
                    $s[1] += $r;
                } else {
                    $s[0] += $r;
                }
            }
        }

        return  $s;
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
                case "bps":
                case "eps":
                case "bgs":
                case "egs":
                case "pcost":
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
        $bps    = $this->dat["bps"];
        $eps    = $this->dat["eps"];
        $bgs    = $this->dat["bgs"];
        $egs    = $this->dat["egs"];
        $pcost  = $this->dat["pcost"];
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
        echo "\\multicolumn{2}{@{\\Vline\\ }c|}{\\makebox[7.4cm][c]{\\bf{費　用}}} & ";
        echo "\\multicolumn{2}{c@{\\ \\Vline}}{\\makebox[7.4cm][c]{\\bf{収　益}}} \\\\\n";
        echo "\\Hline\n";

        $max   = $n + MAX_LINES_PAR_PAGE;
        $month = $mm;
        $drec  = array();
        $n0 = 0;
        $n1 = 0;
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

            if ($d[$n]["division"]) {
                $drec[$n1][1]["name"]   = $d[$n]["name"];
                $drec[$n1][1]["remain"] = $d[$n]["remain"];
                $n1++;
            } else {
                $drec[$n0][0]["name"]   = $d[$n]["name"];
                $drec[$n0][0]["remain"] = $d[$n]["remain"];
                $n0++;
            }

            $n++;
        }

        $nmax = max($n0, $n1);
        for ($i = 0; $i < $nmax; $i++) {
            if ($i < $n0) {
                $dn = $drec[$i][0]["name"];
                $dr = $this->amount($drec[$i][0]["remain"]);
            } else {
                $dn = "";
                $dr = "";
            }

            if ($i < $n1) {
                $cn = $drec[$i][1]["name"];
                $cr = $this->amount($drec[$i][1]["remain"]);
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
            $s  = $this->sum($mm);

            $dr = $s[0];
            $cr = $s[1];

            $dr = ($dr == 0) ? "" : $this->amount($dr);
            $cr = ($cr == 0) ? "" : $this->amount($cr);

            echo "\\Hline\n";
            echo "\\makebox[3.7cm][c]{\bf{合　計}} & ";
            echo "\\makebox[3.7cm][r]{" . $dr ."} & ";
            echo "\\makebox[3.7cm][c]{\bf{合　計}} & ";
            echo "\\makebox[3.7cm][r]{"  .$cr . "} \\\\\n";
        }

        echo "\\Hline\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\n";

        return  $n;
    }

    // make a result
    public function make_a_result()
    {
        $bps    = $this->dat["bps"];
        $eps    = $this->dat["eps"];
        $bgs    = $this->dat["bgs"];
        $egs    = $this->dat["egs"];
        $pcost  = $this->dat["pcost"];
        $psum   = $bps + $pcost;
        $pcs    = $eps - $bps + $pcost;
        $gsum   = $bgs;
        $gcs    = $egs - $bgs;

        echo "\\bigskip\n";
        echo "\n";
        echo "\\begin{center}\n";
        echo "\\begin{tabular}{ccccc}\n";
        echo "\\multicolumn{5}{l}{\\makebox[9cm][l]{売上原価の内訳}} \\\\\n";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{4}{l}{\\makebox[8cm][l]{製品売上原価}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{期首製品棚卸高}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($bps) . "}}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{当期製造原価}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($pcost) . "}}} \\\\\n";
        echo "\\multicolumn{3}{l}{\\makebox[5cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{合計}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($psum) . "}}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{期末製品棚卸高}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($eps) . "}}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{製品売上原価}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($pcs) . "}}} \\\\\n";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][l]{}} & ";
        echo "\\multicolumn{4}{l}{\\makebox[8cm][l]{商品売上原価}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{期首商品棚卸高}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($bgs) . "}}} \\\\\n";
        echo "\\multicolumn{3}{l}{\\makebox[5cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{合計}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($gsum) . "}}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{期末商品棚卸高}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($egs) . "}}} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[2cm][l]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][l]{商品売上原価}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[1cm][c]{}} & ";
        echo "\\multicolumn{1}{l}{\\makebox[3cm][r]{\\tt{" . $this->amount0($gcs) . "}}} \\\\\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\n";
        echo "\\newpage\n";
    }

    // check data for debug
    public function chk_dat()
    {
        echo "title = " . $this->dat["title"] . "\n\n";
        echo "name = "  . $this->dat["name"]  . "\n\n";
        echo "era = "   . $this->dat["era"]   . "\n\n";
        echo "bymd = "  . $this->dat["bymd"]  . "\n\n";
        echo "ty = "    . $this->dat["ty"]    . "\n\n";
        echo "bps = "   . $this->dat["bps"]   . "\n\n";
        echo "eps = "   . $this->dat["eps"]   . "\n\n";
        echo "bgs = "   . $this->dat["bgs"]   . "\n\n";
        echo "egs = "   . $this->dat["egs"]   . "\n\n";
        echo "pcost = " . $this->dat["pcost"] . "\n\n";
        echo "rows = "  . $this->dat["rows"]  . "\n\n";

        echo $this->dat["field"]["n"]          . ", " .
             $this->dat["field"]["m"]          . ", " .
             $this->dat["field"]["mm"]         . ", " .
             $this->dat["field"]["account_cd"] . ", " .
             $this->dat["field"]["name"]       . ", " .
             $this->dat["field"]["remain"]     . ", " .
             $this->dat["field"]["division"]   . "\n\n";

        $cnt    = $this->dat["rows"];
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
    $n = 0;
    $cnt = $dat["rows"];
    while ($n < $cnt)
        $n = $my->make_a_page($n);

    $my->make_a_result();
}
?>

\end{document}
