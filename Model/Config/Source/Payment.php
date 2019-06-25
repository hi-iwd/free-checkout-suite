<?php

namespace IWD\Opc\Model\Config\Source;

use Magento\Payment\Model\Config\Source\Allmethods;

class Payment extends Allmethods
{
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        array_unshift($options, ['value' => '', 'label' => '-- Please select a payment method --']);

        return $this->_filterOptions($options);
    }

    /**
     * Filter empty payment groups without values
     *
     * @param array $options
     * @return array
     */
    protected function _filterOptions(array $options)
    {
        foreach ($options as $k => $option) {
            if (!isset($option['value'])) {
                unset($options[$k]);
            }
        }

        return $options;
    }
}
