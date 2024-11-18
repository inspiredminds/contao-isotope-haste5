<?php

/**
 * Copy of Haste\Data\Plain
 */

namespace Isotope\Helper\Data;

class Plain extends \ArrayObject
{
    /**
     * @param array|null|object $value
     * @param string            $label
     * @param array             $additional
     */
    public function __construct($value, $label='', array $additional=array())
    {
        $values = array_merge(
            array(
                'value' => $value,
                'label' => $label
            ),
            $additional
        );

        parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $varValue = ($this->formatted ?? $this->value ?? null);

        if (is_array($varValue)) {
            return implode(', ', $varValue);
        }

        return (string) $varValue;
    }
}
