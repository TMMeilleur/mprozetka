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

require_once(dirname(__FILE__).'/src/MpRozetkaRepository.php');
require_once(dirname(__FILE__).'/src/YML/YMLSettings.php');
require_once(dirname(__FILE__).'/src/YML/YMLGenerator.php');

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;

class Mprozetka extends Module
{
    protected $config_form = false;
    protected $repository;
    private $xmlPath = 'yml/order-list.xml';
    protected $offerListUrl;
    protected $selectedCategories;
    protected $excludedProducts;

    public function __construct()
    {
        $this->name = 'mprozetka';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'ASG Group (Alexander Grosul)';
        $this->need_instance = 1;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Rozetka');
        $this->description = $this->l('The module provides all necessary information in order to connect with Rozetka.com.ua marketplace ');

        $this->confirmUninstall = $this->l('If you will uninstall the module be sure that you aren\'t going to get problems with your Rozetka.com.ua marketplace');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->repository = new mpRozetkaRepository(Db::getInstance(), $this->context->shop);
        $this->ymlSettings = new YMLSettings($this->getLocalPath().$this->xmlPath);
        $this->ymlGenerator = new YMLGenerator($this->ymlSettings);
    }

    public function install()
    {
        Configuration::updateValue('MPROZETKA_LIVE_MODE', true);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MPROZETKA_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitMprozetkaModule')) == true) {
            $this->postProcess();
        }

        if (file_exists($this->getLocalPath().$this->xmlPath)) {
            $this->offerListUrl = $this->getPathUri().$this->xmlPath;
        }

        $this->selectedCategories = array_map(function ($item) {
            return $item['id_category'];
        }, $this->repository->getSelectedCategories());

        $this->excludedProducts = $this->repository->getExcludedProducts();

        if (((bool)Tools::isSubmit('submitMprozetkaModuleRegenerate')) == true) {
            $this->ymlGenerator->run(
                $this->getShopInfo(),
                $this->getShopCurrencies(),
                $this->getShopCategories(),
                $this->getShopProducts()
            );
        }

        $output = $this->renderForm();


        return $output;
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMprozetkaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'MPROZETKA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode').' '.$this->offerListUrl ? '<a target="_blank" href="'.$this->offerListUrl.'">'.$this->offerListUrl.'</a>' : '',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'categories',
                        'label' => 'Categories',
                        'desc' => $this->l('Select all categories which you want to share on a Rozetka.com.ua marketplace'),
                        'name' => 'selected_categories',
                        'tree' => array(
                            'root_category' => Configuration::get('PS_ROOT_CATEGORY'),
                            'id' => 'id_category',
                            'name' => 'name_category',
                            'selected_categories' => $this->selectedCategories,
                            'use_search' => true,
                            'use_checkbox' => true
                        )

                    ),
                    array(
                        'type' => 'autocomplete',
                        'name' => 'excluded_products',
                        'label' => $this->l('Excluded products'),
                        'descr' => $this->l('Start typing a name of a product you want to exclude from Rozetka.com.ua marketplace'),
                        'col' => 3,
                        'id' => 'products'
                    )
                ),
                'buttons' => array(
                    array(
                        'name' => 'submitMprozetkaModuleRegenerate',
                        'title' => $this->l('Regenerate'),
                        'class' => 'pull-right',
                        'icon' => 'process-icon-refresh',
                        'type' => 'submit'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'excluded_products' => $this->excludedProducts,
            'MPROZETKA_LIVE_MODE' => Configuration::get('MPROZETKA_LIVE_MODE', true)
        );
    }

    protected function postProcess()
    {
        Configuration::updateValue('MPROZETKA_LIVE_MODE', Tools::getValue('MPROZETKA_LIVE_MODE'));
        if ($errors = $this->repository->setSelectedCategories(Tools::getValue('selected_categories'))) {
            $this->displayError($this->l($errors));
        }
        if ($errors = $this->repository->setExcludedProducts(explode('-', Tools::getValue('inputproducts')))) {
            $this->displayError($this->l($errors));
        }
    }

    private function getShopInfo()
    {
        return array(
            'name' => $this->context->shop->name,
            'company' => $this->context->shop->name,
            'url' => $this->context->shop->getBaseURL()
        );
    }

    private function getShopCurrencies()
    {
        $currencies = array_map(function ($item) {
            return array('id' => $item['iso_code'], 'rate' => $item['conversion_rate']);
        }, Currency::getCurrencies(false, true));

        return $currencies;
    }

    private function getShopCategories()
    {
        $result = array();

        if ($this->selectedCategories) {
            foreach ($this->selectedCategories as $key => $selectedCategory) {
                $category = new Category($selectedCategory, $this->context->shop->id);
                $result[$key]['id'] = $selectedCategory;
                $result[$key]['name'] = $category->name;
                if ($category->id_parent != Configuration::get('PS_CATEGORY_ROOT') && in_array($category->id_parent, $this->selectedCategories)) {
                    $result[$key]['parentId'] = $category->id_parent;
                }
            }
        }

        return $result;
    }

    private function getShopProducts()
    {
        $selectedProducts = array();
        if ($this->selectedCategories) {
            foreach ($this->selectedCategories as $id_category) {
                $category = new Category((int)$id_category);

                $searchProvider = new CategoryProductSearchProvider(
                    $this->context->getTranslator(),
                    $category
                );
                $customer = new Customer();
                $this->context->customer = $customer;
                $context = new ProductSearchContext($this->context);

                $query = new ProductSearchQuery();

                $result = $searchProvider->runQuery(
                    $context,
                    $query
                );

                $assembler = new ProductAssembler($this->context);

                $presenterFactory = new ProductPresenterFactory($this->context);
                $presentationSettings = $presenterFactory->getPresentationSettings();
                $presenter = new ProductListingPresenter(
                    new ImageRetriever(
                        $this->context->link
                    ),
                    $this->context->link,
                    new PriceFormatter(),
                    new ProductColorsRetriever(),
                    $this->context->getTranslator()
                );

                $productsForTemplate = [];
                $excludedProductsIds = array_map(function ($product) {
                    return $product['id'];
                }, $this->excludedProducts);
                foreach ($result->getProducts() as $key => $rawProduct) {
                    if (in_array($rawProduct['id_product'], $excludedProductsIds)) {
                        continue;
                    }
                    $productsForTemplate[$key] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct($rawProduct),
                        $this->context->language
                    );
                    $product = new Product($rawProduct['id_product'], true, $this->context->language->id);
                    $productImages = $product->getImages($this->context->language->id);
                    $productImagesResult = array();
                    if ($productImages) {
                        $imageRetriever = new ImageRetriever($this->context->link);
                        foreach ($productImages as $k => $productImage) {
                            $productImagesResult[$k]['img'] = $imageRetriever->getImage($product, $productImage['id_image']);
                        }
                    }
                    $productsForTemplate[$key]['ready_images'] = $productImagesResult;
                }

                $selectedProducts[$id_category] = $productsForTemplate;
            }
        }

        return $selectedProducts;
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }
}
