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

class YMLSettings
{
    protected $encoding = 'UTF-8';
    protected $template = '';

    public function __construct($path)
    {
        $this->template = $path;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setTemplate($fileName)
    {
        $this->template = $fileName;

        return $this;
    }
}
