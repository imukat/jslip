<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

define('MAX_LINES_PAR_PAGE', 42);

class tex_tmplt
{
    private $csvfile;
    private $dat;

    // for amount
    private function amount($x)
    {
        return ($x == 0) ? "" : "{\\tt{" . str_replace("-", "▲", number_format($x)) . "}}";
    }

    // TABセパレータCSVファイルからデータを取得し$datにデータを設定します。
    private function set_dat()
    {
        $csv    = file($this->csvfile); // TABセパレータCSVファイル・データ読み込み
        $data_n = 0;
        $cnt    = count($csv);
        for ($i = 0; $i < $cnt; $i++) {
            $rec    = explode("\t", $csv[$i]);
            switch ($rec[0]) {
                case "title":
                case "name":
                case "era":
                case "rows":
                    $this->dat[$rec[0]] = trim($rec[1]);
                    break;
                case "field":
                    $this->dat[$rec[0]]["n"]       = trim($rec[1]);
                    $this->dat[$rec[0]]["m"]       = trim($rec[2]);
                    $this->dat[$rec[0]]["mmdd"]    = trim($rec[3]);
                    $this->dat[$rec[0]]["memo"]    = trim($rec[4]);
                    $this->dat[$rec[0]]["item"]    = trim($rec[5]);
                    $this->dat[$rec[0]]["other"]   = trim($rec[6]);
                    $this->dat[$rec[0]]["amount0"] = trim($rec[7]);
                    $this->dat[$rec[0]]["amount1"] = trim($rec[8]);
                    $this->dat[$rec[0]]["remain"]  = trim($rec[9]);
                    $this->dat[$rec[0]]["name"]    = trim($rec[10]);
                    break;
                case "data":
                    $this->dat[$rec[0]][$data_n]["n"]       = trim($rec[1]);
                    $this->dat[$rec[0]][$data_n]["m"]       = trim($rec[2]);
                    $this->dat[$rec[0]][$data_n]["mmdd"]    = trim($rec[3]);
                    $this->dat[$rec[0]][$data_n]["memo"]    = trim($rec[4]);
                    $this->dat[$rec[0]][$data_n]["item"]    = trim($rec[5]);
                    $this->dat[$rec[0]][$data_n]["other"]   = trim($rec[6]);
                    $this->dat[$rec[0]][$data_n]["amount0"] = trim($rec[7]);
                    $this->dat[$rec[0]][$data_n]["amount1"] = trim($rec[8]);
                    $this->dat[$rec[0]][$data_n]["remain"]  = trim($rec[9]);
                    $this->dat[$rec[0]][$data_n]["name"]    = trim($rec[10]);
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
        $d    = $this->dat["data"];
        $name = $this->dat["name"];
        $era  = $this->dat["era"];
        $m    = $d[$n]["m"];
        $mm   = $d[$n]["m"] % 100;
        $item = $d[$n]["item"];
        $inam = $d[$n]["name"];

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{ccc}\n";
        echo "\\multicolumn{2}{l}{\\makebox[12.5cm][l]{" . $name . "}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{" . $item . "}} \\\\\n";
        echo "\\makebox[2.5cm][l]{} & ";
        echo "\\underline{\\makebox[10cm][c]{\\bf{" . $inam . "}}} & ";
        echo "\\makebox[2.5cm][r]{" . $era . "年度} \\\\\n";
        echo "\\multicolumn{2}{l}{\\makebox[12.5cm][c]{}} & ";
        echo "\\makebox[2.5cm][r]{" . $mm . "月度} \\\\\n";
        echo "\\end{tabular}\n";

        echo "\\begin{center}\n";
        echo "\\begin{tabular}{cccccc}\n";
        echo "\\Hline\n";
        echo "\\makebox[0.8cm][c]{\\bf{日付}} \\Vline & ";
        echo "\\makebox[5.0cm][c]{\\bf{摘　　要}} \\Vline & ";
        echo "\\makebox[0.5cm][c]{\\bf{丁数\\ \\ }} \\Vline & ";
        echo "\\makebox[2.2cm][c]{\\bf{借　方}} \\Vline & ";
        echo "\\makebox[2.2cm][c]{\\bf{貸　方}} \\Vline & ";
        echo "\\makebox[2.2cm][c]{\\bf{差引残高}} \\\\\n";
        echo "\\Hline\n";

        $max   = $n + MAX_LINES_PAR_PAGE;
        $x     = $n;
        $month = $mm;
        while ($n < $max)
        {
            if (!isset($d[$n]["item"])) {
                break;
            }

            if ($item != $d[$n]["item"]) {
                break;
            }

            $mm    = $d[$n]["m"] % 100;
            $md    = "{\\tt{" . $d[$n]["mmdd"] . "}}";
            $memo  = $d[$n]["memo"];
            $other = intval($d[$n]["other"] / 10000);
            $am0   = $d[$n]["amount0"];
            $am1   = $d[$n]["amount1"];
            $rem   = $d[$n]["remain"];

            $other = ($other == 0) ? "" : "{\\tt{" . $other . "}}";
            $am0   = ($am0   == 0) ? "" : $this->amount($am0);
            $am1   = ($am1   == 0) ? "" : $this->amount($am1);
            $rem   = ($rem   == 0) ? "" : $this->amount($rem);

            if ($x != $n) {
                if ($month != $mm) {
                    $month = $mm;
                    echo "\\Hline\n";
                } else {
                    echo "\\hline\n";
                }
            }

            echo "\\makebox[0.8cm][c]{" . $md    ."} & ";
            echo "\\makebox[5.0cm][l]{" . $memo  ."} & ";
            echo "\\makebox[0.5cm][c]{" . $other ."} & ";
            echo "\\makebox[2.2cm][r]{" . $am0   ."} & ";
            echo "\\makebox[2.2cm][r]{" . $am1   ."} & ";
            echo "\\makebox[2.2cm][r]{" . $rem   ."} \\\\\n";

            $n++;
        }

        echo "\\Hline\n";
        echo "\\end{tabular}\n";
        echo "\\end{center}\n";
        echo "\\newpage\n";

        return $n;
    }

    // check data for debug
    public function chk_dat()
    {
        echo "title = " . $this->dat["title"] . "\n\n";
        echo "name = "  . $this->dat["name"]  . "\n\n";
        echo "era = "   . $this->dat["era"]   . "\n\n";
        echo "rows = "  . $this->dat["rows"]  . "\n\n";

        echo $this->dat["field"]["n"]       . ", " .
             $this->dat["field"]["m"]       . ", " .
             $this->dat["field"]["mmdd"]    . ", " .
             $this->dat["field"]["memo"]    . ", " .
             $this->dat["field"]["item"]    . ", " .
             $this->dat["field"]["other"]   . ", " . 
             $this->dat["field"]["amount0"] . ", " .
             $this->dat["field"]["amount1"] . ", " .
             $this->dat["field"]["remain"]  . ", " .
             $this->dat["field"]["name"]    . "\n\n";

        $cnt = $this->dat["rows"];
        for ($i = 0; $i < $cnt; $i++) {
            echo $this->dat["data"][$i]["n"]       . ", " .
                 $this->dat["data"][$i]["m"]       . ", " .
                 $this->dat["data"][$i]["mmdd"]    . ", " .
                 $this->dat["data"][$i]["memo"]    . ", " .
                 $this->dat["data"][$i]["item"]    . ", " .
                 $this->dat["data"][$i]["other"]   . ", " .
                 $this->dat["data"][$i]["amount0"] . ", " .
                 $this->dat["data"][$i]["amount1"] . ", " .
                 $this->dat["data"][$i]["remain"]  . ", " .
                 $this->dat["data"][$i]["name"]    . "\n\n";
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
        $n = $my->make_a_page($n, $cnt);
}
?>

\end{document}
