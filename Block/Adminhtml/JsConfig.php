<?php

namespace BroSolutions\Keyword\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\App\AreaList;

class JsConfig extends Template
{
    public function __construct(
        Template\Context $context,
        AreaList $areaList,
        array $data = []
    )
    {
        $this->areaList= $areaList;
        parent::__construct($context, $data);
    }

    public function getBaseUrl()
    {
        return $this->areaList->getFrontName('adminhtml');
    }
}
