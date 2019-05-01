-- -----------------------------------------------------------------------------
--
-- @link      https://datagram.co.jp/source/bksj for the canonical source repository
-- @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
-- @license   https://datagram.co.jp/source/bksj/license.txt
--
-- -----------------------------------------------------------------------------
-- jslip Temporary Work
--

use `datagram_js`

-- --------------------------------------------------------------------------------
-- 一時作業情報
--

--
-- 伝票情報
--
DROP TABLE IF EXISTS `w_slip`;
CREATE TABLE IF NOT EXISTS `w_slip` (
    `bid`            BIGINT         COMMENT '基本情報ID: bid',
    `id`             BIGINT         COMMENT '伝票ID: id',
    `scd`            BIGINT         COMMENT '部門ID: scd',
    `ymd`            DATE           COMMENT '伝票日付: ymd',
    `line`           INT            COMMENT '行番号: line',
    `debit`          BIGINT         COMMENT '借方科目: debit',
    `credit`         BIGINT         COMMENT '貸方科目: credit',
    `debit_name`     VARCHAR(80)    COMMENT '借方科目名: debit_name',
    `credit_name`    VARCHAR(80)    COMMENT '貸方科目名: credit_name',
    `debit_account`  BIGINT         COMMENT '借方勘定科目: debit_account',
    `credit_account` BIGINT         COMMENT '貸方勘定科目: credit_account',
    `debit_amount`   DECIMAL(18, 4) COMMENT '借方金額: debit_amount',
    `credit_amount`  DECIMAL(18, 4) COMMENT '貸方金額: credit_amount',
    `amount`         DECIMAL(18, 4) COMMENT '金額: amount',
    `remark`         TEXT           COMMENT '摘要: remark',
    `settled_flg`    TINYINT        COMMENT '決算フラグ: settled_flg',
    PRIMARY KEY (`bid`, `id`, `line`)
) ENGINE=InnoDB COMMENT '伝票情報: w_slip';

--
-- 勘定科目情報
--
DROP TABLE IF EXISTS `w_account`;
CREATE TABLE IF NOT EXISTS `w_account` (
    `bid`         BIGINT COMMENT '基本情報ID: bid',
    `ccd`         INT    COMMENT '分類コード: ccd',
    `account`     INT    COMMENT '勘定科目: account',
    `item`        INT    COMMENT '科目: item',
    `account_cd`  BIGINT COMMENT '勘定科目コード: account_cd',
    `item_cd`     BIGINT COMMENT '科目コード: item_cd',
    `account_ccd` INT    COMMENT '勘定科目分類コード: account_ccd',
    `division`    INT    COMMENT '貸借区分: division',
    PRIMARY KEY (`bid`, `ccd`, `account`, `item`)
) ENGINE=InnoDB COMMENT '勘定科目情報: w_account';

--
-- 勘定科目用全科目情報
--
DROP TABLE IF EXISTS `w_item`;
CREATE TABLE IF NOT EXISTS `w_item` (
    `bid`      BIGINT COMMENT '基本情報ID: bid',
    `item`     BIGINT COMMENT '科目コード: item',
    `account`  BIGINT COMMENT '勘定科目コード: account',
    `ccd`      INT    COMMENT '勘定科目分類コード: ccd',
    `division` INT    COMMENT '貸借区分: division',
    PRIMARY KEY (`bid`, `item`)
) ENGINE=InnoDB COMMENT '勘定科目用全科目情報: w_item';

--
-- 総勘定元帳
--
DROP TABLE IF EXISTS `w_ledger`;
CREATE TABLE IF NOT EXISTS `w_ledger` (
    `bid`         BIGINT         COMMENT '基本情報ID: bid',
    `account`     BIGINT         COMMENT '科目: account',
    `typ`         INT            COMMENT '処理区分: typ',
    `item`        BIGINT         COMMENT '勘定科目: item',
    `ymd`         DATE           COMMENT '伝票日付: ymd',
    `id`          BIGINT         COMMENT '伝票番号: id',
    `line`        INT            COMMENT '行番号: line',
    `m`           INT            COMMENT '年月度: m',
    `other`       BIGINT         COMMENT '相手方科目   : other',
    `memo`        TEXT           COMMENT '摘要・備考: memo',
    `settled_flg` TINYINT        COMMENT '決算フラグ: settled_flg',
    `amount`      DECIMAL(18, 4) COMMENT '金額: amount',
    `amount0`     DECIMAL(18, 4) COMMENT '借方金額: amount0',
    `amount1`     DECIMAL(18, 4) COMMENT '貸方金額: amount1',
    `remain`      DECIMAL(18, 4) COMMENT '差引残高: remain',
    `division`    INT            COMMENT '貸借区分: division',
    `mmdd`        VARCHAR(8)     COMMENT '表示用月日: mmdd'
) ENGINE=InnoDB COMMENT '総勘定元帳: w_ledger';

--
-- 勘定科目コード分類
--
DROP TABLE IF EXISTS `w_aicd`;
CREATE TABLE IF NOT EXISTS `w_aicd` (
    `bid`  BIGINT      COMMENT '基本情報ID: bid',
    `c1`   INT         COMMENT '大分類コード: c1',
    `c2`   INT         COMMENT '中分類コード: c2',
    `c3`   INT         COMMENT '小分類コード: c3',
    `c4`   INT         COMMENT '細分類コード: c4',
    `ctg`  INT         COMMENT '分類コード: ctg',
    `div`  INT         COMMENT '勘定分類コード: div',
    `name` VARCHAR(80) COMMENT '細分類名: name'
) ENGINE=InnoDB COMMENT '勘定科目コード分類: w_aicd';

--
-- 試算表（繰越分）
--
DROP TABLE IF EXISTS `w_tb_a`;
CREATE TABLE IF NOT EXISTS `w_tb_a` (
    `bid`           BIGINT         COMMENT '基本情報ID: bid',
    `item`          BIGINT         COMMENT '勘定科目: item',
    `m`             INT            COMMENT '年月度: m',
    `name`          VARCHAR(80)    COMMENT '科目名: name',
    `remain`        DECIMAL(18, 4) COMMENT '差引残高: remain',
    `debit_amount`  DECIMAL(18, 4) COMMENT '借方金額: debit_amount',
    `credit_amount` DECIMAL(18, 4) COMMENT '貸方金額: credit_amount',
    `division`      INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '試算表（繰越分）: w_tb_a';

--
-- 試算表（月度合計）
--
DROP TABLE IF EXISTS `w_tb_b`;
CREATE TABLE IF NOT EXISTS `w_tb_b` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `item`       BIGINT         COMMENT '勘定科目: item',
    `m`          INT            COMMENT '年月度: m',
    `debit_sum`  DECIMAL(18, 4) COMMENT '借方月度合計金額: debit_sum',
    `credit_sum` DECIMAL(18, 4) COMMENT '貸方月度合計金額: credit_sum'
) ENGINE=InnoDB COMMENT '試算表（月度合計）: w_tb_b';

--
-- 試算表（繰越分・月度合計）
--
DROP TABLE IF EXISTS `w_tb_c`;
CREATE TABLE IF NOT EXISTS `w_tb_c` (
    `bid`           BIGINT         COMMENT '基本情報ID: bid',
    `item`          BIGINT         COMMENT '勘定科目: item',
    `m`             INT            COMMENT '年月度: m',
    `name`          VARCHAR(80)    COMMENT '科目名: name',
    `debit_amount`  DECIMAL(18, 4) COMMENT '借方金額: debit_amount',
    `credit_amount` DECIMAL(18, 4) COMMENT '貸方金額: credit_amount',
    `division`      INT            COMMENT '貸借区分: division',
    `debit_sum`     DECIMAL(18, 4) COMMENT '借方月度合計金額: debit_sum',
    `credit_sum`    DECIMAL(18, 4) COMMENT '貸方月度合計金額: credit_sum',
    `remain`        DECIMAL(18, 4) COMMENT '差引残高: remain',
    `ctg3`          BIGINT         COMMENT '小分類コード: ctg3',
    `ctg4`          BIGINT         COMMENT '細分類コード: ctg4'
) ENGINE=InnoDB COMMENT '試算表（繰越分・月度合計）: w_tb_c';

--
-- 試算表（結果：個別科目別）参照：t_item
--
DROP TABLE IF EXISTS `w_tb_rslt1`;
CREATE TABLE IF NOT EXISTS `w_tb_rslt1` (
    `bid`           BIGINT         COMMENT '基本情報ID: bid',
    `m`             INT            COMMENT '年月度: m',
    `item`          BIGINT         COMMENT '勘定科目: item',
    `debit_remain`  DECIMAL(18, 4) COMMENT '借方残高: debit_remain',
    `debit_sum`     DECIMAL(18, 4) COMMENT '借方月度合計: debit_sum',
    `name`          VARCHAR(80)    COMMENT '科目名: name',
    `credit_sum`    DECIMAL(18, 4) COMMENT '貸方月度合計: credit_sum',
    `credit_remain` DECIMAL(18, 4) COMMENT '貸方残高: credit_remain',
    `mm`            INT            COMMENT '月度: mm',
    `division`      INT            COMMENT '貸借区分: division',
    `ctg_div`       INT            COMMENT '勘定分類コード: ctg_div',
    `ctg1`          BIGINT         COMMENT '大分類コード: ctg1',
    `ctg2`          BIGINT         COMMENT '中分類コード: ctg2',
    `ctg3`          BIGINT         COMMENT '小分類コード: ctg3',
    `ctg4`          BIGINT         COMMENT '細分類コード: ctg4',
    `ctg5`          BIGINT         COMMENT '処理科目コード: ctg5'
) ENGINE=InnoDB COMMENT '試算表（個別科目別）: w_tb_rslt1';

--
-- 試算表（結果：勘定科目別）参照：t_account_item
--
DROP TABLE IF EXISTS `w_tb_rslt2`;
CREATE TABLE IF NOT EXISTS `w_tb_rslt2` (
    `bid`           BIGINT         COMMENT '基本情報ID: bid',
    `m`             INT            COMMENT '年月度: m',
    `mm`            INT            COMMENT '月度: mm',
    `ccd`           INT            COMMENT '分類コード: ccd',
    `item`          INT            COMMENT '勘定科目: item',
    `division`      INT            COMMENT '貸借区分: division',
    `ctg_div`       INT            COMMENT '勘定分類コード: ctg_div',
    `debit_remain`  DECIMAL(18, 4) COMMENT '借方残高: debit_remain',
    `debit_sum`     DECIMAL(18, 4) COMMENT '借方月度合計: debit_sum',
    `credit_sum`    DECIMAL(18, 4) COMMENT '貸方月度合計: credit_sum',
    `credit_remain` DECIMAL(18, 4) COMMENT '貸方残高: credit_remain'
) ENGINE=InnoDB COMMENT '試算表（勘定科目別）: w_tb_rslt2';

--
-- 決算（精算表）
--
DROP TABLE IF EXISTS `w_sa_a`;
CREATE TABLE IF NOT EXISTS `w_sa_a` (
    `bid`           BIGINT         COMMENT '基本情報ID: bid',
    `m`             INT            COMMENT '年月度: m',
    `mm`            INT            COMMENT '月度: mm',
    `ccd`           INT            COMMENT '分類コード: ccd',
    `item`          INT            COMMENT '勘定科目: item',
    `debit_remain`  DECIMAL(18, 4) COMMENT '借方残高: debit_remain',
    `credit_remain` DECIMAL(18, 4) COMMENT '貸方残高: credit_remain',
    `bsd`           DECIMAL(18, 4) COMMENT '貸借借方: bsd',
    `bsc`           DECIMAL(18, 4) COMMENT '貸借貸方: bsc',
    `pld`           DECIMAL(18, 4) COMMENT '損益借方: pld',
    `plc`           DECIMAL(18, 4) COMMENT '損益貸方: plc',
    `cod`           DECIMAL(18, 4) COMMENT '繰越借方: cod',
    `coc`           DECIMAL(18, 4) COMMENT '繰越貸方: coc'
) ENGINE=InnoDB COMMENT '決算（精算表）: w_sa_a';

--
-- 決算（精算表月次合計）
--
DROP TABLE IF EXISTS `w_sa_b`;
CREATE TABLE IF NOT EXISTS `w_sa_b` (
    `bid`           BIGINT      COMMENT '基本情報ID: bid',
    `m`             INT         COMMENT '年月度: m',
    `mm`            INT         COMMENT '月度: mm',
    `debit_remain`  DECIMAL(18, 4) COMMENT '借方残高: debit_remain',
    `credit_remain` DECIMAL(18, 4) COMMENT '貸方残高: credit_remain',
    `bsd`           DECIMAL(18, 4) COMMENT '貸借借方: bsd',
    `bsc`           DECIMAL(18, 4) COMMENT '貸借貸方: bsc',
    `pld`           DECIMAL(18, 4) COMMENT '損益借方: pld',
    `plc`           DECIMAL(18, 4) COMMENT '損益貸方: plc',
    `cod`           DECIMAL(18, 4) COMMENT '繰越借方: cod',
    `coc`           DECIMAL(18, 4) COMMENT '繰越貸方: coc'
) ENGINE=InnoDB COMMENT '決算（精算表月次合計）: w_sa_b';

--
-- 決算（決算利益）
--
DROP TABLE IF EXISTS `w_sa_c`;
CREATE TABLE IF NOT EXISTS `w_sa_c` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算利益）: w_sa_c';

--
-- 決算（決算貸借対照表勘定借方）
--
DROP TABLE IF EXISTS `w_sa_d`;
CREATE TABLE IF NOT EXISTS `w_sa_d` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算貸借対照表勘定借方）: w_sa_d';

--
-- 決算（決算貸借対照表勘定貸方）
--
DROP TABLE IF EXISTS `w_sa_e`;
CREATE TABLE IF NOT EXISTS `w_sa_e` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算貸借対照表勘定貸方）: w_sa_e';

--
-- 決算（決算損益勘定借方）
--
DROP TABLE IF EXISTS `w_sa_f`;
CREATE TABLE IF NOT EXISTS `w_sa_f` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算損益勘定借方）: w_sa_f';

--
-- 決算（決算損益勘定借方原価）
--
DROP TABLE IF EXISTS `w_sa_g`;
CREATE TABLE IF NOT EXISTS `w_sa_g` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算損益勘定借方原価）: w_sa_g';

--
-- 決算（決算損益勘定貸方）
--
DROP TABLE IF EXISTS `w_sa_h`;
CREATE TABLE IF NOT EXISTS `w_sa_h` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd'
) ENGINE=InnoDB COMMENT '決算（決算損益勘定貸方）: w_sa_h';

--
-- 決算（結果）
--
DROP TABLE IF EXISTS `w_sa_rslt`;
CREATE TABLE IF NOT EXISTS `w_sa_rslt` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd',
    `ctg_div`    INT            COMMENT '勘定分類コード: ctg_div',
    `division`   INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '決算（結果）: w_sa_rslt';

--
-- 決算（貸借借方）
--
DROP TABLE IF EXISTS `w_sa_bsd`;
CREATE TABLE IF NOT EXISTS `w_sa_bsd` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd',
    `ctg_div`    INT            COMMENT '勘定分類コード: ctg_div',
    `division`   INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '決算（貸借借方）: w_sa_bsd';

--
-- 決算（貸借貸方）
--
DROP TABLE IF EXISTS `w_sa_bsc`;
CREATE TABLE IF NOT EXISTS `w_sa_bsc` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd',
    `ctg_div`    INT            COMMENT '勘定分類コード: ctg_div',
    `division`   INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '決算（貸借貸方）: w_sa_bsc';

--
-- 決算（損益借方）
--
DROP TABLE IF EXISTS `w_sa_pld`;
CREATE TABLE IF NOT EXISTS `w_sa_pld` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd',
    `ctg_div`    INT            COMMENT '勘定分類コード: ctg_div',
    `division`   INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '決算（損益借方）: w_sa_pld';

--
-- 決算（損益貸方）
--
DROP TABLE IF EXISTS `w_sa_plc`;
CREATE TABLE IF NOT EXISTS `w_sa_plc` (
    `bid`        BIGINT         COMMENT '基本情報ID: bid',
    `m`          INT            COMMENT '年月度: m',
    `mm`         INT            COMMENT '月度: mm',
    `name`       VARCHAR(80)    COMMENT '勘定科目名: name',
    `remain`     DECIMAL(18, 4) COMMENT '差引残高: remain',
    `account_cd` BIGINT         COMMENT '勘定科目コード: account_cd',
    `ctg_div`    INT            COMMENT '勘定分類コード: ctg_div',
    `division`   INT            COMMENT '貸借区分: division'
) ENGINE=InnoDB COMMENT '決算（損益貸方）: w_sa_plc';

--
-- 年度末
--
DROP TABLE IF EXISTS `w_efy`;
CREATE TABLE IF NOT EXISTS `w_efy` (
    `bid` BIGINT COMMENT '基本情報ID: bid',
    `m`   INT    COMMENT '年月度: m'
) ENGINE=InnoDB COMMENT '年度末: w_efy';

--
-- 計算
--
DROP TABLE IF EXISTS `w_calc`;
CREATE TABLE IF NOT EXISTS `w_calc` (
    `bid`  BIGINT    COMMENT '基本情報ID: bid',
    `last` TIMESTAMP COMMENT '最終計算日時: last'
) ENGINE=InnoDB COMMENT '計算: w_calc';

-- --------------------------------------------------------------------------------
-- EOL
