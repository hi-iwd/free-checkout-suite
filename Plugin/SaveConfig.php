<?php

namespace IWD\Opc\Plugin;

use IWD\Opc\Helper\Data as OpcHelper;

class SaveConfig
{
    public $opcHelper;

    public function __construct(
        OpcHelper $opcHelper
    ) {
        $this->opcHelper = $opcHelper;
    }

    public function afterSave($subject, $result)
    {
        if ($subject->getSection() === 'payment') {
            $this->opcHelper->requestToApi();
        }

        return $result;
    }
}
