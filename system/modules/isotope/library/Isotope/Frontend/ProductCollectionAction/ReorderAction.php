<?php

/*
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009 - 2019 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @link       https://isotopeecommerce.org
 * @license    https://opensource.org/licenses/lgpl-3.0.html
 */

namespace Isotope\Frontend\ProductCollectionAction;

use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\Module;
use Contao\System;
use Contao\PageModel;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Message;

class ReorderAction extends AbstractButton
{
    /**
     * @var Module
     */
    private $module;

    /**
     * Constructor.
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(IsotopeProductCollection $collection)
    {
        return $this->module->iso_cart_jumpTo > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'reorder';
    }

    /**
     * {@inheritdoc}
     * @return false|void
     */
    public function handleSubmit(IsotopeProductCollection $collection)
    {
        if (!parent::handleSubmit($collection)) {
            return false;
        }

        Isotope::getCart()->copyItemsFrom($collection);

        Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['reorderConfirmation']);

        /** @var UrlParser $urlParser */
        $urlParser = System::getContainer()->get(UrlParser::class);

        Controller::redirect(
            $urlParser->addQueryString(
                'continue=' . base64_encode(System::getReferer()),
                PageModel::findById($this->module->iso_cart_jumpTo)?->getAbsoluteUrl()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(IsotopeProductCollection $collection)
    {
        return $GLOBALS['TL_LANG']['MSC']['reorderLabel'];
    }
}
