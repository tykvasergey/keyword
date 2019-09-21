<?php


namespace BroSolutions\Keyword\Controller\Adminhtml\Attribute;

use Magento\Backend\App\Action\Context;

class Add extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \BroSolutions\Keyword\Model\ProducAttribute $producAttribute
    )
    {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->producAttribute = $producAttribute;

        parent::__construct($context);
    }

    public function execute()
    {
        $data = [];
        $params = $this->getRequest()->getParams();

        $result = $this->producAttribute->createNewAttributeValue($params['attribute_name'], $params['attribute_option']);
        $data = $result;

        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        return $result;
    }
}