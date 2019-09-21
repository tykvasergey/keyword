<?php

namespace BroSolutions\Keyword\Model;


class ProducAttribute
{

    protected $_logger;

    protected $_attributeRepository;

    protected $_attributeOptionManagement;

    protected $_option;

    protected $_attributeOptionLabel;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterface $attributeOptionLabel,
        \Magento\Eav\Model\Entity\Attribute\Option $option,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_logger = $logger;
        $this->_attributeRepository = $attributeRepository;
        $this->_attributeOptionManagement = $attributeOptionManagement;
        $this->_option = $option;
        $this->_attributeOptionLabel = $attributeOptionLabel;
        $this->_storeManager = $storeManager;
    }

    function createNewAttributeValue($attributeCode, $attributeValue)
    {
        $result = [];
        $storeId = $this->_storeManager->getStore()->getId();

        $attribute_id = $this->_attributeRepository->get('catalog_product', $attributeCode)->getAttributeId();
        $options = $this->_attributeOptionManagement->getItems('catalog_product', $attribute_id);
        /* if attribute option already exists, remove it */

        foreach($options as $option) {
            if ($option->getLabel() == $attributeValue) {
                $this->_attributeOptionManagement->delete('catalog_product', $attribute_id, $option->getValue());
            }
        }

        /* new attribute option */
        $this->_option->setValue($attributeValue);

        $this->_attributeOptionLabel->setStoreId($storeId);
        $this->_attributeOptionLabel->setLabel($attributeValue);

        $this->_option->setLabel($attributeValue);
        $this->_option->setStoreLabels([$this->_attributeOptionLabel]);
        $this->_option->setSortOrder(0);
        $this->_option->setIsDefault(false);

        $idOption = $this->_attributeOptionManagement->add('catalog_product', $attribute_id, $this->_option);

        if($idOption) {
            $id = str_replace('id_', '', $idOption);
            $result = ['value' => $id, 'label' => $attributeValue];
        }

        return $result;
    }
}