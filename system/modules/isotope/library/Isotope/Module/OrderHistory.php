<?php

/*
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009 - 2019 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @link       https://isotopeecommerce.org
 * @license    https://opensource.org/licenses/lgpl-3.0.html
 */

namespace Isotope\Module;

use Codefog\HasteBundle\Formatter;
use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\FrontendUser;
use Contao\Input;
use Contao\System;
use Contao\PageModel;
use Haste\Generator\RowClass;
use Isotope\CompatibilityHelper;
use Isotope\Isotope;
use Isotope\Message;
use Isotope\Model\ProductCollection\Order;
use Isotope\Template;


/**
 * @property int $iso_orderdetails_module
 * @property int $iso_cart_jumpTo
 */
class OrderHistory extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_iso_orderhistory';

    /**
     * Disable caching of the frontend page if this module is in use
     * @var boolean
     */
    protected $blnDisableCache = true;

    /**
     * @inheritDoc
     */
    protected function getSerializedProperties()
    {
        $props = parent::getSerializedProperties();

        $props[] = 'iso_config_ids';

        return $props;
    }

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (CompatibilityHelper::isBackend()) {
            return $this->generateWildcard();
        }

        if (!\Contao\System::getContainer()->get('security.helper')->isGranted('ROLE_MEMBER') || 0 === \count($this->iso_config_ids)) {
            return '';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     * @return void
     */
    protected function compile()
    {
        $arrOrders = [];
        $objOrders = Order::findBy(
            [
                'order_status>0',
                'tl_iso_product_collection.member=?',
                'config_id IN (' . implode(',', array_map('intval', $this->iso_config_ids)) . ')'
            ],
            [FrontendUser::getInstance()->id],
            ['order' => 'locked DESC']
        );

        // No orders found, just display an "empty" message
        if (null === $objOrders) {
            $this->Template          = new Template('mod_message');
            $this->Template->type    = 'empty';
            $this->Template->message = $GLOBALS['TL_LANG']['ERR']['emptyOrderHistory'];

            return;
        }

        $reorder = (int) Input::get('reorder');
        $previousUid = Input::get('uid');

        /** @var Formatter $formatter */
        $formatter = System::getContainer()->get(Formatter::class);

        /** @var UrlParser $urlParser */
        $urlParser = System::getContainer()->get(UrlParser::class);

        foreach ($objOrders as $objOrder) {
            if ($this->iso_cart_jumpTo && $reorder === (int) $objOrder->id) {
                $this->reorder($objOrder);
            }

            Isotope::setConfig($objOrder->getConfig());
            $details = '';

            if ($this->iso_orderdetails_module) {
                Input::setGet('uid', $objOrder->uniqid);
                $details = Controller::getFrontendModule($this->iso_orderdetails_module);
            }

            $arrOrders[] = [
                'collection' => $objOrder,
                'raw'        => $objOrder->row(),
                'date'       => $formatter->date($objOrder->locked),
                'time'       => $formatter->time($objOrder->locked),
                'datime'     => $formatter->datim($objOrder->locked),
                'grandTotal' => Isotope::formatPriceWithCurrency($objOrder->getTotal()),
                'status'     => $objOrder->getStatusLabel(),
                'link'       => $this->jumpTo ? ($urlParser->addQueryString('uid=' . $objOrder->uniqid, PageModel::findById($this->jumpTo)?->getFrontendUrl())) : '',
                'details'    => $details,
                'reorder'    => $this->iso_cart_jumpTo ? ($urlParser->addQueryString('reorder=' . $objOrder->id)) : '',
                'class'      => $objOrder->getStatusAlias(),
            ];
        }

        Input::setGet('uid', $previousUid);

        RowClass::withKey('class')->addFirstLast()->addEvenOdd()->applyTo($arrOrders);

        $this->Template->orders = $arrOrders;
    }

    private function reorder(Order $order)
    {
        Isotope::getCart()->copyItemsFrom($order);

        Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['reorderConfirmation']);

        /** @var UrlParser $urlParser */
        $urlParser = System::getContainer()->get(UrlParser::class);

        Controller::redirect(
            $urlParser->addQueryString(
                'continue=' . base64_encode(System::getReferer()),
                PageModel::findById($this->iso_cart_jumpTo)?->getAbsoluteUrl()
            )
        );
    }
}
