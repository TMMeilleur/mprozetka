<?php
/**
 * 2017 ASG Group
 *
 * MP Rozetka
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 2.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-2.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the module to newer
 * versions in the future.
 *
 *  @author    ASG Group (Alexander Grosul)
 *  @copyright 2017 ASG Group
 *  @license   http://opensource.org/licenses/GPL-2.0 General Public License (GPL 2.0)
 */

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mprozetka_categories` (
    `id_mprozetka_entity` int(11) NOT NULL AUTO_INCREMENT,
    `id_category` int(11) NOT NULL,
    `id_shop` int(11) NOT NULL,
    PRIMARY KEY  (`id_mprozetka_entity`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mprozetka_excluded_products` (
    `id_mprozetka_entity` int(11) NOT NULL AUTO_INCREMENT,
    `id_product` int(11) NOT NULL,
    `id_shop` int(11) NOT NULL,
    PRIMARY KEY  (`id_mprozetka_entity`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
