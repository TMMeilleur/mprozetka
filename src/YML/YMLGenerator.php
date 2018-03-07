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

require_once(dirname(__FILE__).'/YMLSettings.php');

class YMLGenerator
{
    private $tmpFile;
    private $writer;
    private $settings;

    public function __construct(YMLSettings $settings)
    {
        $this->settings = $settings;
        $this->tmpFile = $this->settings->getTemplate() !== null ? \tempnam(\sys_get_temp_dir(), 'YMLGenerator') : 'php://output';
        $this->writer = new \XMLWriter();
        $this->writer->openUri($this->tmpFile);
        $this->writer->setIndent(true);
    }

    /**
     * Run xml file generation
     *
     * @param array $shopInfo - main information about a shop
     * @param array $currencies - shop currencies information
     * @param array $categories - shop categories
     * @param array $products - shop products
     *
     * @return bool
     */
    public function run(array $shopInfo, array $currencies, array $categories, array $products)
    {
        $this->addHeading();
        $this->addShopInfo($shopInfo);
        $this->addShopCurrencies($currencies);
        $this->addShopCategories($categories);
        $this->addShopProducts($products);
        $this->addFooter();

        if (null !== $this->settings->getTemplate()) {
            \copy($this->tmpFile, $this->settings->getTemplate());
            @\unlink($this->tmpFile);
        }

        return true;
    }

    /**
     * Add heading of xml file
     */
    protected function addHeading()
    {
        $this->writer->startDocument('1.0', $this->settings->getEncoding());
        $this->writer->startDTD('yml_catalog', null, 'shops.dtd');
        $this->writer->endDTD();
        $this->writer->startElement('yml_catalog');
        $this->writer->writeAttribute('date', \date('Y-m-d H:i'));
        $this->writer->startElement('shop');
    }

    /**
     * Add closing tags to the end of a xml file
     */
    protected function addFooter()
    {
        $this->writer->fullEndElement();
        $this->writer->fullEndElement();
        $this->writer->endDocument();
    }

    /**
     * Add xml shop info elements
     *
     * @param $info
     */
    protected function addShopInfo($info)
    {
        foreach ($info as $name => $value) {
            if ($value !== null) {
                $this->writer->writeElement($name, $value);
            }
        }
    }

    /**
     * Add xml shop currencies elements
     *
     * @param $currencies
     */
    protected function addShopCurrencies($currencies)
    {

        $this->writer->startElement('currencies');
        foreach ($currencies as $currency) {
            $this->writer->startElement('currency');
            $this->writer->writeAttribute('id', $currency['id']);
            $this->writer->writeAttribute('rate', $currency['rate']);
            $this->writer->endElement();
        }
        $this->writer->endElement();
    }

    /**
     * Add xml shop categories elements
     *
     * @param $categories
     */
    protected function addShopCategories($categories)
    {
        $this->writer->startElement('categories');
        foreach ($categories as $category) {
            $this->writer->startElement('category');
            $this->writer->writeAttribute('id', $category['id']);
            if (isset($category['parentId'])) {
                $this->writer->writeAttribute('parentId', $category['parentId']);
            }
            $this->writer->text($this->filterElem($category['name']));
            $this->writer->endElement();
        }
        $this->writer->endElement();
    }

    /**
     * Add xml shop products elements
     *
     * @param $products
     */
    protected function addShopProducts($products)
    {
        $this->writer->startElement('offers');
        foreach ($products as $id_category => $categoryProducts) {
            foreach ($categoryProducts as $categoryProduct) {
                $this->writer->startElement('offer');
                $this->writer->writeAttribute('id', $id_category.$categoryProduct['id_product']);
                if ($categoryProduct['quantity'] > 0) {
                    $this->writer->writeAttribute('available', 'true');
                } else {
                    $this->writer->writeAttribute('available', 'false');
                }
                $this->writer->writeElement('url', $this->filterElem($categoryProduct['canonical_url']));
                $this->writer->writeElement('price', $categoryProduct['price_amount']);
                $this->writer->writeElement('currencyId', Currency::getDefaultCurrency()->iso_code);
                $this->writer->writeElement('categoryId', $id_category);
                if ($categoryProduct['manufacturer_name']) {
                    $this->writer->writeElement('vendor', $categoryProduct['manufacturer_name']);
                }
                $this->writer->writeElement('categoryId', $id_category);
                $this->writer->writeElement('stock_quantity', $categoryProduct['quantity']);
                $this->writer->writeElement('name', $this->filterElem($categoryProduct['name']));
                $this->writer->writeElement('description', '<![CDATA['.$categoryProduct['description'].']]');
                if (isset($categoryProduct['ready_images']) && $categoryProduct['ready_images']) {
                    foreach ($categoryProduct['ready_images'] as $productImage) {
                        $this->writer->writeElement('picture', $productImage['img']['large']['url']);
                    }
                }
                if (isset($categoryProduct['features']) && $categoryProduct['features']) {
                    foreach ($categoryProduct['features'] as $feature) {
                        $this->writer->startElement('param');
                        $this->writer->writeAttribute('name', $this->filterElem($feature['name']));
                        $this->writer->text($this->filterElem($feature['value']));
                        $this->writer->endElement();
                    }
                }
                $this->writer->endElement();
            }
        }
        $this->writer->endElement();
    }

    /**
     * Filter forbidden elements from a string
     *
     * @param $s - string to filtering
     *
     * @return mixed|string
     */
    private function filterElem($s) {
        $a['&nbsp;'] = ' ';
        $a['&ndash;'] = ' ';
        $a['&raquo;'] = ' ';
        $a['&laquo;'] = ' ';
        $a['&ldquo;'] = ' ';
        $a['&rdquo;'] = ' ';
        $a['&bull;'] = ' ';
        $a['&oacute;'] = ' ';
        $a['&plusmn;'] = ' ';
        $s = str_replace(array_keys($a), array_values($a), $s);
        $s = strip_tags($s);
        $s = htmlspecialchars_decode($s);

        $a['"'] = '&quot;';
        $a['&;'] = '&amp;';
        $a['>'] = '&gt;';
        $a['<'] = '&lt;';
        $a["'"] = '&apos;';
        $s = str_replace(array_keys($a), array_values($a), $s);

        return $s;
    }
}
