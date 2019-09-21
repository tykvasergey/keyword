<?php


namespace BroSolutions\Keyword\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Framework\App\RequestInterface;

/**
 * Data provider for "Custom Attribute" field of product page
 */
class CustomAttr extends AbstractModifier
{
    const FIELD_ORDER = 1;
    const FIELD_CODE = 'select_keyword';
    /**
     * @param LocatorInterface            $locator
     * @param UrlInterface                $urlBuilder
     * @param ArrayManager                $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ArrayManager $arrayManager,
        RequestInterface $request,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->arrayManager = $arrayManager;
        $this->request = $request;
        $this->eavConfig = $eavConfig;

    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->customiseCustomAttrField($meta);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $getParams = $this->request->getParams();
        if(!empty($getParams['id']) && !empty($data[$getParams['id']]["product"][self::FIELD_CODE])) {
            $data[$getParams['id']]["product"][self::FIELD_CODE] = explode(',', $data[$getParams['id']]["product"][self::FIELD_CODE]);
        }

        return $data;
    }

    /**
     * Customise Custom Attribute field
     *
     * @param array $meta
     *
     * @return array
     */
    protected function customiseCustomAttrField(array $meta)
    {
        $elementPath = $this->arrayManager->findPath(self::FIELD_CODE, $meta, null, 'children');
        $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX . self::FIELD_CODE, $meta, null, 'children');

        if (!$elementPath) {
            return $meta;
        }

        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataScope'     => '',
                            'formElement'   => 'container',
                            'componentType' => 'container',
                            'component'     => 'Magento_Ui/js/form/components/group',
                            'sortOrder' => 30
                        ],
                    ],
                ],
                'children'  => [
                    self::FIELD_CODE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'component' => 'Magento_Ui/js/form/element/ui-select',
                                    'componentType' => Field::NAME,
                                    'formElement'   => 'multiselect',
                                    'elementTmpl'   => 'BroSolutions_Keyword/ui-select',
                                    'filterOptions' => true,
                                    'multiple'      => true,
                                    'options'       => $this->getOptions(),
                                    'config'           => [
                                        'dataScope' => self::FIELD_CODE
                                    ],
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        );

        return $meta;
    }

    /**
     * Retrieve custom attribute collection
     *
     * @return array
     */
    protected function getOptions()
    {
        $result = [];

        $attribute = $this->eavConfig->getAttribute('catalog_product', self::FIELD_CODE);
        $options = $attribute->getSource()->getAllOptions();

        foreach ($options as $option) {
            $result[] = ['value' => $option['value'], 'label' => $option['label']];
        }

        return $result;
    }
}