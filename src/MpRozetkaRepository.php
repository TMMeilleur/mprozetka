<?php
/**
 * 2017-2018 ASG Group
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
 *  @copyright 2017-2018 ASG Group
 *  @license   http://opensource.org/licenses/GPL-2.0 General Public License (GPL 2.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpRozetkaRepository
{
    private $db;
    private $shop;
    private $db_prefix;

    public function __construct(Db $db, Shop $shop)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
    }

    public function getSelectedCategories()
    {
        $sql = 'SELECT * FROM '.$this->db_prefix.'mprozetka_categories WHERE `id_shop` = '.(int)$this->shop->id;

        return $this->db->executeS($sql);
    }

    public function setSelectedCategories($categories)
    {
        if (!$this->clearCategories()) {
            return 'Cannot clear old selected categories';
        }

        $result = true;
        if ($categories) {
            foreach ($categories as $category) {
                $result &= $this->db->insert('mprozetka_categories', array('id_category' => (int)$category, 'id_shop' => (int)$this->shop->id));
            }
        }

        if (!$result) {
            return 'Something went wrong! The process might not be finished correct get assure that everything is fine';
        }

        return false;
    }

    public function clearCategories()
    {
        return $this->db->delete('mprozetka_categories', 'id_shop = '.(int)$this->shop->id);
    }

    public function checkCategorySelected($id_category)
    {
        return $this->db->getValue('SELECT id_category FROM '.$this->db_prefix.'mprozetka_categories WHERE id_shop = '.(int)$this->shop->id.' AND id_category = '.(int)$id_category);
    }

    public function setExcludedProducts($products)
    {
        if (!$this->clearExcludedProducts()) {
            return 'Cannot clear old selected categories';
        }

        $result = true;
        if ($products) {
            foreach ($products as $product) {
                if ($product) {
                    $result &= $this->db->insert(
                        'mprozetka_excluded_products',
                        array('id_product' => (int)$product, 'id_shop' => (int)$this->shop->id)
                    );
                }
            }
        }

        if (!$result) {
            return 'Something went wrong! The process might not be finished correct get assure that everything is fine';
        }

        return false;
    }

    public function clearExcludedProducts()
    {
        return $this->db->delete('mprozetka_excluded_products', 'id_shop = '.(int)$this->shop->id);
    }

    public function getExcludedProducts()
    {
        return Db::getInstance()->executeS('
                  SELECT pl.`name`, mep.`id_product` as `id`
                  FROM `'.$this->db_prefix.'product_lang` pl
                  RIGHT JOIN `'.$this->db_prefix.'mprozetka_excluded_products` mep
                  ON(mep.`id_product` = pl.`id_product`)
                  WHERE pl.`id_lang` = '.(int)Context::getContext()->language->id.'
                  AND pl.`id_shop` = '.(int)Context::getContext()->shop->id);
    }
}
