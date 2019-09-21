<?php

namespace BroSolutions\Keyword\Model\Product;

use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Catalog category helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class View extends \Magento\Catalog\Helper\Product\View
{
    /**
     * List of catalog product session message groups
     *
     * @var array
     */
    protected $messageGroups;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Catalog product
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $_catalogProduct = null;

    /**
     * Catalog design
     *
     * @var \Magento\Catalog\Model\Design
     */
    protected $_catalogDesign;

    /**
     * Catalog session
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $_catalogSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $string;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Design $catalogDesign,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\Eav\Model\Config $eavConfig,
        array $messageGroups = [],
        \Magento\Framework\Stdlib\StringUtils $string = null
    ) {
        $this->_catalogSession = $catalogSession;
        $this->_catalogDesign = $catalogDesign;
        $this->_catalogProduct = $catalogProduct;
        $this->_coreRegistry = $coreRegistry;
        $this->messageGroups = $messageGroups;
        $this->messageManager = $messageManager;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->string = $string ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Stdlib\StringUtils::class);
        $this->eavConfig = $eavConfig;
        parent::__construct(
        $context,
        $catalogSession,
        $catalogDesign,
        $catalogProduct,
        $coreRegistry,
        $messageManager,
        $categoryUrlPathGenerator,
        $messageGroups,
            $string
        );
    }

    /**
     * Add meta information from product to layout
     *
     * @param \Magento\Framework\View\Result\Page $resultPage
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    private function preparePageMetadata(ResultPage $resultPage, $product)
    {
        $pageLayout = $resultPage->getLayout();
        $pageConfig = $resultPage->getConfig();

        $metaTitle = $product->getMetaTitle();
        $pageConfig->setMetaTitle($metaTitle);
        $pageConfig->getTitle()->set($metaTitle ?: $product->getName());


        $attribute = $this->eavConfig->getAttribute('catalog_product', 'select_keyword');
        $options = $attribute->getSource()->getAllOptions();

        $keywords = '';
        if($options) {
            foreach ($options as $option) {
                if($option['label']) {
                    $keywords .= ' ' . $option['label'];
                }
            }
        }

        $keyword = ($keywords) ? $keywords : $product->getMetaKeyword();

        $currentCategory = $this->_coreRegistry->registry('current_category');
        if ($keyword) {
            $pageConfig->setKeywords($keyword);
        } elseif ($currentCategory) {
            $pageConfig->setKeywords($product->getName());
        }

        $description = $product->getMetaDescription();
        if ($description) {
            $pageConfig->setDescription($description);
        } else {
            $pageConfig->setDescription($this->string->substr(strip_tags($product->getDescription()), 0, 255));
        }

        if ($this->_catalogProduct->canUseCanonicalTag()) {
            $pageConfig->addRemotePageAsset(
                $product->getUrlModel()->getUrl($product, ['_ignore_category' => true]),
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );
        }

        $pageMainTitle = $pageLayout->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle($product->getName());
        }

        return $this;
    }

    public function prepareAndRender(ResultPage $resultPage, $productId, $controller, $params = null)
    {
        /**
         * Remove default action handle from layout update to avoid its usage during processing of another action,
         * It is possible that forwarding to another action occurs, e.g. to 'noroute'.
         * Default action handle is restored just before the end of current method.
         */
        $defaultActionHandle = $resultPage->getDefaultLayoutHandle();
        $handles = $resultPage->getLayout()->getUpdate()->getHandles();
        if (in_array($defaultActionHandle, $handles)) {
            $resultPage->getLayout()->getUpdate()->removeHandle($resultPage->getDefaultLayoutHandle());
        }

        if (!$controller instanceof \Magento\Catalog\Controller\Product\View\ViewInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Bad controller interface for showing product')
            );
        }
        // Prepare data
        $productHelper = $this->_catalogProduct;
        if (!$params) {
            $params = new \Magento\Framework\DataObject();
        }

        // Standard algorithm to prepare and render product view page
        $product = $productHelper->initProduct($productId, $controller, $params);
        if (!$product) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Product is not loaded'));
        }

        $buyRequest = $params->getBuyRequest();
        if ($buyRequest) {
            $productHelper->prepareProductOptions($product, $buyRequest);
        }

        if ($params->hasConfigureMode()) {
            $product->setConfigureMode($params->getConfigureMode());
        }

        $this->_eventManager->dispatch('catalog_controller_product_view', ['product' => $product]);

        $this->_catalogSession->setLastViewedProductId($product->getId());

        if (in_array($defaultActionHandle, $handles)) {
            $resultPage->addDefaultHandle();
        }

        $this->initProductLayout($resultPage, $product, $params);
        $this->preparePageMetadata($resultPage, $product);
        return $this;
    }
}
