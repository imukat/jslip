-- -----------------------------------------------------------------------------
--
-- @link      https://datagram.co.jp/source/bksj for the canonical source repository
-- @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
-- @license   https://datagram.co.jp/source/bksj/license.txt
--
-- -----------------------------------------------------------------------------
-- jslip Transaction
--

use `datagram_js`

-- --------------------------------------------------------------------------------
-- 処理情報
--

DROP TABLE IF EXISTS `t_member`;
DROP TABLE IF EXISTS `t_auth`;

--
-- 認証
--
CREATE TABLE IF NOT EXISTS `t_auth` (
    `aid`           BIGINT      NOT NULL AUTO_INCREMENT COMMENT '認証ID: aid',
    `login_id`      VARCHAR(80) NOT NULL                COMMENT 'ログインID: login_id',
    `password`      VARCHAR(80) NOT NULL                COMMENT 'パスワード: password',
    `update_person` BIGINT                              COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                           COMMENT '更新日時: update_time',
    PRIMARY KEY (`aid`)
) ENGINE=InnoDB COMMENT '認証: t_auth';

--
-- 会員
--
CREATE TABLE IF NOT EXISTS `t_member` (
    `mid`           BIGINT      NOT NULL AUTO_INCREMENT COMMENT '会員ID: mid',
    `aid`           BIGINT      NOT NULL                COMMENT '認証ID: aid',
    `name`          VARCHAR(80) NOT NULL                COMMENT '名前: name',
    `role`          TEXT        NOT NULL                COMMENT '役割: role',
    `email`         TEXT                                COMMENT '電子メール: email',
    `tel`           TEXT                                COMMENT '電話: tel',
    `update_person` BIGINT                              COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                           COMMENT '更新日時: update_time',
    PRIMARY KEY (`mid`),
    FOREIGN KEY (`aid`) REFERENCES `t_auth`(`aid`)
) ENGINE=InnoDB COMMENT '会員: t_member';

--
-- 処理情報:基本情報
--
DROP TABLE IF EXISTS `t_basic`;
CREATE TABLE IF NOT EXISTS `t_basic` (
    `id`            BIGINT NOT NULL AUTO_INCREMENT COMMENT '基本情報ID: id',
    `mid`           BIGINT                         COMMENT '会員ID: mid',
    `attribute`     BIGINT                         COMMENT '属性: attribute',
    `name`          TEXT                           COMMENT '名称: name',
    `disp_name`     TEXT                           COMMENT '表示名称: disp_name',
    `term_year`     INT                            COMMENT '本年度: term_year',
    `term_begin`    DATE                           COMMENT '期首: term_begin',
    `term_end`      DATE                           COMMENT '期末: term_end',
    `round`         TINYINT                        COMMENT 'まるめ指定: round',
    `calendar`      TEXT                           COMMENT '歴方式: calendar',
    `valid_flg`     BOOLEAN                        COMMENT '有効フラグ: valid_flg',
    `update_person` BIGINT                         COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '基本情報: t_basic';

--
-- 処理情報:部門
--
DROP TABLE IF EXISTS `t_section`;
CREATE TABLE IF NOT EXISTS `t_section` (
    `id`             BIGINT NOT NULL AUTO_INCREMENT COMMENT '部門ID: id',
    `bid`            BIGINT                         COMMENT '基本情報ID: bid',
    `kana`           VARCHAR(80)                    COMMENT '部門名かな: kana',
    `name`           VARCHAR(80)                    COMMENT '部門名: name',
    `update_person`  BIGINT                         COMMENT '更新者ID: update_person',
    `update_time`    TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '部門: t_section';

--
-- 処理情報:科目（勘定科目t_accountを細分化したもの）
--
DROP TABLE IF EXISTS `t_item`;
CREATE TABLE IF NOT EXISTS `t_item` (
    `id`            BIGINT NOT NULL AUTO_INCREMENT COMMENT '科目ID: id',
    `bid`           BIGINT                         COMMENT '基本情報ID: bid',
    `kcd`           INT                            COMMENT '科目コード: kcd',
    `ccd`           INT                            COMMENT '分類コード: ccd',
    `account`       INT                            COMMENT '勘定科目: account',
    `item`          INT                            COMMENT '科目: item',
    `kana`          VARCHAR(80)                    COMMENT '科目かな: kana',
    `name`          VARCHAR(80)                    COMMENT '科目名: name',
    `valid_flg`     BOOLEAN                        COMMENT '有効フラグ: valid_flg',
    `delete_flg`    BOOLEAN                        COMMENT '削除フラグ: delete_flg',
    `edit_flg`      BOOLEAN                        COMMENT '編集フラグ: edit_flg',
    `dummy`         TEXT                           COMMENT 'ダミー: dummy',
    `update_person` BIGINT                         COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '科目（使用勘定科目）: t_item';

--
-- 処理情報:勘定科目
--
DROP TABLE IF EXISTS `t_account`;
CREATE TABLE IF NOT EXISTS `t_account` (
    `id`            BIGINT NOT NULL AUTO_INCREMENT COMMENT '勘定科目ID: id',
    `bid`           BIGINT                         COMMENT '基本情報ID: bid',
    `ccd`           INT                            COMMENT '分類コード: ccd',
    `item`          INT                            COMMENT '勘定科目: item',
    `item_ccd`      INT                            COMMENT '勘定科目分類: item_ccd',
    `division`      INT                            COMMENT '貸借区分: division',
    `kana`          VARCHAR(80)                    COMMENT '勘定科目かな: kana',
    `name`          VARCHAR(80)                    COMMENT '勘定科目名: name',
    `delete_flg`    BOOLEAN                        COMMENT '削除フラグ: delete_flg',
    `edit_flg`      BOOLEAN                        COMMENT '編集フラグ: edit_flg',
    `update_person` BIGINT                         COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '勘定科目: t_account';

--
-- 処理情報:仕訳帳
--
DROP TABLE IF EXISTS `t_jslip`;
DROP TABLE IF EXISTS `t_journal`;
CREATE TABLE IF NOT EXISTS `t_journal` (
    `id`            BIGINT NOT NULL AUTO_INCREMENT COMMENT '仕訳帳ID: id',
    `bid`           BIGINT                         COMMENT '基本情報ID: bid',
    `scd`           BIGINT                         COMMENT '部門ID: scd',
    `ymd`           DATE                           COMMENT '伝票日付: ymd',
    `attr`          TEXT                           COMMENT '属性（予備）: attr',
    `settled_flg`   TINYINT                        COMMENT '決算フラグ: settled_flg',
    `not_use_flg`   BOOLEAN                        COMMENT '不使用フラグ: not_use_flg',
    `update_person` BIGINT                         COMMENT '更新者ID: update_person',  
    `update_time`   TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '仕訳帳: t_journal';

--
-- 処理情報:仕訳伝票
--
CREATE TABLE IF NOT EXISTS `t_jslip` (
    `id`            BIGINT NOT NULL AUTO_INCREMENT COMMENT '仕訳伝票ID: id',
    `jid`           BIGINT                         COMMENT '仕訳帳ID: jid',
    `line`          INT                            COMMENT '行番号: line',
    `debit`         BIGINT                         COMMENT '借方科目: debit',
    `credit`        BIGINT                         COMMENT '貸方科目: credit',
    `amount`        DECIMAL(18, 4)                 COMMENT '金額: amount',
    `remark`        TEXT                           COMMENT '摘要: remark',
    `attr`          TEXT                           COMMENT '属性（予備）: attr',
    `update_person` BIGINT                         COMMENT '更新者ID: update_person',
    `update_time`   TIMESTAMP                      COMMENT '更新日時: update_time',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`jid`) REFERENCES `t_journal`(`id`)
) ENGINE=InnoDB COMMENT '仕訳伝票: t_jslip';

--
-- 処理情報:税率
--
DROP TABLE IF EXISTS `t_tax`;
CREATE TABLE IF NOT EXISTS `t_tax` (
    `id`        BIGINT NOT NULL AUTO_INCREMENT COMMENT '税率ID: id',
    `bid`       BIGINT NOT NULL                COMMENT '基本情報ID: bid',
    `name`      TEXT NOT NULL                  COMMENT '名称: name',
    `rate`      DECIMAL(6, 4) NOT NULL         COMMENT '税率: rate',
    `valid_flg` BOOLEAN                        COMMENT '有効フラグ: valid_flg',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '税率: t_tax';

--
-- 処理情報:暦変換
--
DROP TABLE IF EXISTS `t_era`;
CREATE TABLE IF NOT EXISTS `t_era` (
    `id`         BIGINT NOT NULL AUTO_INCREMENT COMMENT '歴変換ID: id',
    `bid`        BIGINT NOT NULL                COMMENT '基本情報ID: bid',
    `ymd`        DATE NOT NULL                  COMMENT '開始日付: ymd',
    `era`        VARCHAR(8) NOT NULL            COMMENT '歴名: era',
    `abr`        VARCHAR(4) NOT NULL            COMMENT '略語: abr',
    `delete_flg` BOOLEAN                        COMMENT '削除フラグ: delete_flg',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT '暦変換: t_era';

--
-- 初期値
--

--
-- check password : php -f chk_pw.php
--
-- t_auth/t_member : root and guest
--
INSERT INTO `t_auth` (`aid`, `login_id`, `password`) VALUES
(1000000000000000000, 'root',  '$2y$10$eD5lLTU7FtukhPiCF6bkH.zjl0wx3vf4l1CtwGWz4SQxcVocCbSNG'),
(1000000000000000001, 'guest', '$2y$10$rUQAL6p.YlBrcZh927ZDiu8aRv5qcK/xVeRLqAGGiUX7lkBudvksa');
INSERT INTO `t_member` (`mid`, `aid`, `name`, `role`) VALUES
(1000000000000000000, 1000000000000000000, 'Charlie Root',  'root'),
(1000000000000000001, 1000000000000000001, 'Welcome Guest', 'user');

-- --------------------------------------------------------------------------------
-- EOL
