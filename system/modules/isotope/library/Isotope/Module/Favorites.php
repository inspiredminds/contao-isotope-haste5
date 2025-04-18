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

use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\Input;
use Contao\System;
use Isotope\Frontend\ProductCollectionAction\AddToCartAction;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Model\ProductCollectionItem;

/**
 * @property bool $iso_moveFavorites
 */
class Favorites extends AbstractProductCollection
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_iso_favorites';

    /**
     * @inheritdoc
     */
    protected function getCollection()
    {
        return Isotope::getFavorites();
    }

    /**
     * @inheritdoc
     */
    protected function getEmptyMessage()
    {
        return $GLOBALS['TL_LANG']['MSC']['noItemsInFavorites'];
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
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function getCollectionTemplate()
    {
        $template = parent::getCollectionTemplate();

        $template->isEditable = true;

        return $template;
    }

    /**
     * @inheritdoc
     */
    protected function updateItemTemplate(
        IsotopeProductCollection $collection,
        ProductCollectionItem $item,
        array $data,
        array $quantity,
        &$hasChanges
    ) {
        $data = parent::updateItemTemplate($collection, $item, $data, $quantity, $hasChanges);

        /** @var UrlParser $urlParser */
        $urlParser = System::getContainer()->get(UrlParser::class);

        $data['cart_href'] = $urlParser->addQueryString('add_to_cart=' . $item->id);

        // Add single item to cart
        if (Input::get('add_to_cart') == $item->id && $item->hasProduct()) {
            Isotope::getCart()->addProduct(
                $item->getProduct(),
                $item->quantity,
                ['jumpTo' => $item->getRelated('jumpTo')]
            );

            if ($this->iso_moveFavorites) {
                $collection->deleteItem($item);
                $hasChanges = true;
            }

            Controller::redirect($urlParser->removeQueryString(['add_to_cart']));
        }

        // Add all items to cart based on quantity field and global button
        if (Input::post('FORM_SUBMIT') === $this->strFormId
            && null !== (string) Input::post('button_add_to_cart')
            && (0 === \count($quantity) || $quantity[$item->id] > 0)
        ) {
            Isotope::getCart()->addProduct(
                $item->getProduct(),
                ($quantity[$item->id] ?? null) > 0 ? $quantity[$item->id] : 1,
                ['jumpTo' => $item->getRelated('jumpTo')]
            );

            if ($this->iso_moveFavorites) {
                $collection->deleteItem($item);
            }

            $hasChanges = true;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getActions()
    {
        return [
            new AddToCartAction(),
        ];
    }
}
