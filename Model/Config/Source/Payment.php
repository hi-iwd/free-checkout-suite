<?php

namespace IWD\Opc\Model\Config\Source;

use Magento\Payment\Model\Config\Source\Allmethods;

class Payment extends Allmethods
{
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        array_unshift($options, ['value' => '', 'label' => '-- Please select a payment method --']);
        return $options;
    }
}
