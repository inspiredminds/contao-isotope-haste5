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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Isotope\CompatibilityHelper;
use Isotope\Frontend\ProductCollectionAction\ReorderAction;
use Isotope\Model\ProductCollection\Order;
use Isotope\Template;

/**
 * @property int    $iso_cart_jumpTo
 * @property int    $iso_gallery
 * @property string $iso_collectionTpl
 * @property string $iso_orderCollectionBy
 */
class OrderDetails extends AbstractProductCollection
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_iso_orderdetails';

    /**
     * Display a wildcard in the back end
     *
     * @param bool $blnBackend
     *
     * @return string
     */
    public function generate($blnBackend = false)
    {
        if ($blnBackend) {
            $this->backend = true;
            $this->jumpTo  = 0;
            $this->setWildcard(false);
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        $order = $this->getCollection();

        if (null === $order) {
            return;
        }

        parent::compile();

        /** @var Formatter $formatter */
        $formatter = System::getContainer()->get(Formatter::class);

        $this->Template->info                 = StringUtil::deserialize($order->checkout_info, true);
        $this->Template->date                 = $formatter->date($order->locked);
        $this->Template->time                 = $formatter->time($order->locked);
        $this->Template->datim                = $formatter->datim($order->locked);
        $this->Template->orderDetailsHeadline = sprintf($GLOBALS['TL_LANG']['MSC']['orderDetailsHeadline'], $order->getDocumentNumber(), $this->Template->datim);
        $this->Template->orderStatus          = sprintf($GLOBALS['TL_LANG']['MSC']['orderStatusHeadline'], $order->getStatusLabel());
        $this->Template->orderStatusKey       = $order->getStatusAlias();
    }

    /**
     * @inheritdoc
     */
    protected function getCollection()
    {
        $order = Order::findOneBy('uniqid', (string) Input::get('uid'));
        $isMember = System::getContainer()->get('security.helper')->isGranted('ROLE_MEMBER');

        // Also check owner (see #126)
        if (null === $order
            || ($isMember
                && $order->member > 0
                && FrontendUser::getInstance()->id != $order->member
            )
        ) {
            $this->Template          = new Template('mod_message');
            $this->Template->type    = 'error';
            $this->Template->message = $GLOBALS['TL_LANG']['ERR']['orderNotFound'];

            return null;
        }

        // Order belongs to a member but not logged in
        if (CompatibilityHelper::isFrontend() && $this->iso_loginRequired && $order->member > 0 && !$isMember) {
            throw new AccessDeniedException();
        }

        if (CompatibilityHelper::isFrontend()) {
            /** @var PageModel $objPage */
            global $objPage;

            $order->preventSaving(false);
            $order->orderdetails_page = $objPage->id;
        }

        return $order;
    }

    /**
     * @inheritdoc
     */
    protected function getEmptyMessage()
    {
        // An order can never be empty
        return '';
    }

    /**
     * @inheritdoc
     */
    protected function canEditQuantity()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function canRemoveProducts()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getActions()
    {
        if (CompatibilityHelper::isBackend()) {
            return [];
        }

        return [
            new ReorderAction($this),
        ];
    }
}
