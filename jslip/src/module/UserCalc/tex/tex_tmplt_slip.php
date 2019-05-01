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

    // yyyy/mm/dd
    private function yyyymmdd($x)
    {
        $y = explode(" ", $x);
        $z = explode("-", $y[0]);

        return $z[0]."/" . $z[1] . "/" . $z[2];
    }

    // calculation
    private function sum($id, $n)
    {
        $s[0] = 0;
        $s[1] = "合計";
        $s[2] = 0;

        $d = $this->dat["data"];

        $i = $n;
        while (true) {
            if (!isset($d[$i]["id"])) {
                break;
            }

            if ($id != $d[$i]["id"]) {
                break;
            }

            $s[0] += $d[$i]["debit_amount"];
            $s[2] += $d[$i]["credit_amount"];
            $i--;
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
                case "rows":
                    $this->dat[$rec[0]] = trim($rec[1]);
                    break;
                case "field":
                    $this->dat[$rec[0]]["n"]              = trim($rec[1]);
                    $this->dat[$rec[0]]["id"]             = trim($rec[2]);
                    $this->dat[$rec[0]]["scd"]            = trim($rec[3]);
                    $this->dat[$rec[0]]["name"]           = trim($rec[4]);
                    $this->dat[$rec[0]]["ymd"]            = trim($rec[5]);
                    $this->dat[$rec[0]]["line"]           = trim($rec[6]);
                    $this->dat[$rec[0]]["debit"]          = trim($rec[7]);
                    $this->dat[$rec[0]]["credit"]         = trim($rec[8]);
                    $this->dat[$rec[0]]["debit_name"]     = trim($rec[9]);
                    $this->dat[$rec[0]]["credit_name"]    = trim($rec[10]);
                    $this->dat[$rec[0]]["debit_account"]  = trim($rec[11]);
                    $this->dat[$rec[0]]["credit_account"] = trim($rec[12]);
                    $this->dat[$rec[0]]["debit_amount"]   = trim($rec[13]);
                    $this->dat[$rec[0]]["credit_amount"]  = trim($rec[14]);
                    $this->dat[$rec[0]]["amount"]         = trim($rec[15]);
                    $this->dat[$rec[0]]["remarks"]        = trim($rec[16]);
                    $this->dat[$rec[0]]["settled_flg"]    = trim($rec[17]);
                    break;
                case "data":
                    $this->dat[$rec[0]][$data_n]["n"]              = trim($rec[1]);
                    $this->dat[$rec[0]][$data_n]["id"]             = trim($rec[2]);
                    $this->dat[$rec[0]][$data_n]["scd"]            = trim($rec[3]);
                    $this->dat[$rec[0]][$data_n]["name"]           = trim($rec[4]);
                    $this->dat[$rec[0]][$data_n]["ymd"]            = trim($rec[5]);
                    $this->dat[$rec[0]][$data_n]["line"]           = trim($rec[6]);
                    $this->dat[$rec[0]][$data_n]["debit"]          = trim($rec[7]);
                    $this->dat[$rec[0]][$data_n]["credit"]         = trim($rec[8]);
                    $this->dat[$rec[0]][$data_n]["debit_name"]     = trim($rec[9]);
                    $this->dat[$rec[0]][$data_n]["credit_name"]    = trim($rec[10]);
                    $this->dat[$rec[0]][$data_n]["debit_account"]  = trim($rec[11]);
                    $this->dat[$rec[0]][$data_n]["credit_account"] = trim($rec[12]);
                    $this->dat[$rec[0]][$data_n]["debit_amount"]   = trim($rec[13]);
                    $this->dat[$rec[0]][$data_n]["credit_amount"]  = trim($rec[14]);
                    $this->dat[$rec[0]][$data_n]["amount"]         = trim($rec[15]);
                    $this->dat[$rec[0]][$data_n]["remark"]         = trim($rec[16]);
                    $this->dat[$rec[0]][$data_n]["settled_flg"]    = trim($rec[17]);
                    $data_n++;
                    break;
            }
        }
    }

    // ヘッダ
    private function header($x, $n)
    {
        $this->dat["seq"]++;
        echo "\\multicolumn{5}{c}{\\makebox[7.2cm][c]{}}  \\\\\n";
        $x++;

        echo "\\makebox[2.2cm][l]{\\tt{" . sprintf("%06d", $this->dat["seq"]) . "}} & ";
        echo "\\makebox[2.2cm][l]{" . $this->yyyymmdd($this->dat["data"][$n]["ymd"]) . "} & ";
        echo "\\multicolumn{2}{l}{\\makebox[7.2cm][l]{}} & ";
        echo "\\makebox[2.2cm][r]{} \\\\\n";
        echo "\\Hline\n";
        $x++;
        echo "\\makebox[2.2cm][c]{\\bf{金額}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{借方科目}} & ";
        echo "\\makebox[5.0cm][c]{\\bf{摘　　要}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{貸方科目}} & ";
        echo "\\makebox[2.2cm][c]{\\bf{金額}} \\\\\n";
        echo "\\Hline\n";
        $x++;

        return  $x;
    }

    // フッタ
    private function footer($x, $id, $n)
    {
        $s    = $this->sum($id, $n);
        $s[0] = ($s[0] == 0) ? "" : $this->amount($s[0]);
        $s[2] = ($s[2] == 0) ? "" : $this->amount($s[2]);

        echo "\\makebox[2.2cm][r]{" . $s[0] . "} & ";
        echo "\\multicolumn{3}{c}{\\makebox[9.4cm][c]{\\bf{" . $s[1] . "}}} & ";
        echo "\\makebox[2.2cm][r]{" . $s[2] . "} \\\\\n";
        echo "\\Hline\n";
        $x++;

        return $x;
    }

    // 主処理
    public function main()
    {
        $this->set_dat();
        $this->dat["seq"]    = 0;
        $this->dat["pre_id"] = -1;
    }

    // make a page
    public function make_a_page($n, $cnt)
    {
        $max    = MAX_LINES_PAR_PAGE;
        $x      = 0;
        $d      = $this->dat["data"];
        $title  = $this->dat["title"];
        $name   = $this->dat["name"];
        $era    = $this->dat["era"];

        echo "\\begin{center}\n";

        echo "\\begin{tabular}{ccccc}\n";
        echo "\\multicolumn{4}{l}{\\makebox[12.5cm][l]{" . $name . "}} & ";
        echo "\\makebox[2.5cm][r]{\\tt{" . $era . "年度}} \\\\\n";
        $x++;
        echo "\\multicolumn{5}{c}{\\makebox[15.0cm][c]{\\Large\\bf{" . $title . "}}} \\\\\n";
        $x++;
        echo "\\multicolumn{5}{l}{\\makebox[15.0cm][c]{}} \\\\\n";
        $x++;

        $start = $x;
        $id    = $this->dat["pre_id"];
        while ($n < $cnt && $x < $max) {
            if ($id != $d[$n]["id"]) {
                if ($x != $start) {
                    $x = $this->footer($x, $id, $n - 1);
                }
                $x = $this->header($x, $n);
            }

            $id  = $d[$n]["id"];

            $dn  = $d[$n]["debit_name"];
            $cn  = $d[$n]["credit_name"];
            $da  = $d[$n]["debit_amount"];
            $ca  = $d[$n]["credit_amount"];
            $itm = $d[$n]["remark"];

            $da = ($da == 0) ? "" : $this->amount($da);
            $ca = ($ca == 0) ? "" : $this->amount($ca);

            echo "\\makebox[2.2cm][r]{" . $da  . "} & ";
            echo "\\makebox[2.2cm][c]{" . $dn  . "} & ";
            echo "\\makebox[5.0cm][c]{" . $itm . "} & ";
            echo "\\makebox[2.2cm][c]{" . $cn  . "} & ";
            echo "\\makebox[2.2cm][r]{" . $ca  . "} \\\\\n";
            echo "\\hline\n";

            $x++;
            $n++;
        }

        if ($n == $cnt) {
            $x = $this->footer($x, $id, $n - 1);
            echo "\\end{tabular}\n";
            echo "\\end{center}\n";
        } else {
            if ($id != $d[$n]["id"]) { // 先読み
                $x = $this->footer($x, $id, $n - 1);
            }
            echo "\\end{tabular}\n";
            echo "\\end{center}\n";
            echo "\\newpage\n";
        }

        $this->dat["pre_id"] = $id;

        return  $n;
    }

    // check data for debug
    public function chk_dat()
    {
        echo "title = " . $this->dat["title"] . "\n\n";
        echo "name = "  . $this->dat["name"]  . "\n\n";
        echo "era = "   . $this->dat["era"]   . "\n\n";
        echo "rows = "  . $this->dat["rows"]  . "\n\n";

        echo $this->dat["field"]["n"]              . ", ".
             $this->dat["field"]["id"]             . ", ".
             $this->dat["field"]["scd"]            . ", ".
             $this->dat["field"]["name"]           . ", ".
             $this->dat["field"]["ymd"]            . ", ".
             $this->dat["field"]["line"]           . ", ".
             $this->dat["field"]["debit"]          . ", ".
             $this->dat["field"]["credit"]         . ", ".
             $this->dat["field"]["debit_name"]     . ", ".
             $this->dat["field"]["credit_name"]    . ", ".
             $this->dat["field"]["debit_account"]  . ", ".
             $this->dat["field"]["credit_account"] . ", ".
             $this->dat["field"]["debit_amount"]   . ", ".
             $this->dat["field"]["credit_amount"]  . ", ".
             $this->dat["field"]["amount"]         . ", ".
             $this->dat["field"]["remark"]         . ", ".
             $this->dat["field"]["settled_flg"]    . "\n\n";

        $cnt = $this->dat["rows"];
        for ($i = 0; $i < $cnt; $i++) {
            echo $this->dat["data"][$i]["n"]              . ", ".
                 $this->dat["data"][$i]["id"]             . ", ".
                 $this->dat["data"][$i]["scd"]            . ", ".
                 $this->dat["data"][$i]["name"]           . ", ".
                 $this->dat["data"][$i]["ymd"]            . ", ".
                 $this->dat["data"][$i]["line"]           . ", ".
                 $this->dat["data"][$i]["debit"]          . ", ".
                 $this->dat["data"][$i]["credit"]         . ", ".
                 $this->dat["data"][$i]["debit_name"]     . ", ".
                 $this->dat["data"][$i]["credit_name"]    . ", ".
                 $this->dat["data"][$i]["debit_account"]  . ", ".
                 $this->dat["data"][$i]["credit_account"] . ", ".
                 $this->dat["data"][$i]["debit_amount"]   . ", ".
                 $this->dat["data"][$i]["credit_amount"]  . ", ".
                 $this->dat["data"][$i]["amount"]         . ", ".
                 $this->dat["data"][$i]["remark"]         . ", ".
                 $this->dat["data"][$i]["settled_flg"]    . "\n\n";
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

$my = new tex_tmplt($argv[1]);
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
        $n = $my->make_a_page($n, $cnt);
}
?>

\end{document}
