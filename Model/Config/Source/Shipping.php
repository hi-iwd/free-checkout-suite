<?php

namespace IWD\Opc\Model\Config\Source;

use Magento\Shipping\Model\Config\Source\Allmethods;

class Shipping extends Allmethods
{
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $options = parent::toOptionArray($isActiveOnlyFlag);
        $options[0]['label'] = '-- Please select a shipping method --';
        foreach ($options as &$option) {
            if (is_array($option['value'])) {
                foreach ($option['value'] as &$method) {
                    $method['label'] = preg_replace('#^\[.+?\]\s#', '', $method['label']);
                }
            }
        }

        return $options;
    }
}
