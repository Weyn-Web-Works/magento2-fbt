<?php

/**
 * @package     M2FBT
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\Afbt\Controller\Fetch;

use Codilar\Afbt\Api\AfbtIndexRepositoryInterface;
use Codilar\Afbt\Helper\Data;
use Codilar\Afbt\Model\Config;
use Codilar\Core\Helper\Product;
use Exception;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Reports\Block\Product\AbstractProduct;

class Index extends Action
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var AfbtIndexRepositoryInterface
     */
    private $afbtIndexRepository;
    /**
     * @var Product
     */
    private $productHelper;
    /**
     * @var AbstractProduct
     */
    private $abstractProduct;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Config
     */
    private $config;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param AfbtIndexRepositoryInterface $afbtIndexRepository
     * @param Product $productHelper
     * @param AbstractProduct $abstractProduct
     * @param Config $config
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        Data $helper,
        AfbtIndexRepositoryInterface $afbtIndexRepository,
        Product $productHelper,
        AbstractProduct $abstractProduct,
        Config $config,
        JsonFactory $jsonFactory
    )
    {
        $this->helper = $helper;
        $this->afbtIndexRepository = $afbtIndexRepository;
        $this->productHelper = $productHelper;
        $this->abstractProduct = $abstractProduct;
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
    }

    /**
     * Fetch frequently bought together products from the afbt_index table.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $productId = $data['product_id'];
        try {
            $afbtIndex = $this->afbtIndexRepository->getById($productId, "pp_id");
            $associatedIds = $afbtIndex->getAspIdsArray();
            $parentProduct = $this->helper->getProduct($productId);
            $associatedProductData = [];
            $associatedProductData["associated_products"] = [];
            if ($associatedIds) {
                foreach ($associatedIds as $associatedId) {
                    if(count($associatedProductData["associated_products"]) >= $this->config->getNoOfCombos()) {
                        break;
                    }
                    if (!$associatedId) {
                        continue;
                    }
                    $product = $this->helper->getProduct($associatedId);
                    if ($product && $product->isSalable()) {
                        $associatedProductData["associated_products"][] = [
                            "parent_product_id" => $productId,
                            "id" => $associatedId,
                            "name" => $product->getName(),
                            "url" => $product->getProductUrl(),
                            "image" => $this->helper->getProductImage($product),
                            "price_html" => $this->productHelper->getProductPriceHtml($product),
                            "price" => $this->productHelper->getPrice($product),
                            "add_to_cart_url" => $this->getAddToCartUrl($product),
                            "total_price" => $this->productHelper->getFormattedPrice($this->productHelper->getPrice($parentProduct) + $this->productHelper->getPrice($product))
                        ];
                    }
                }
            }
            if (count($associatedProductData["associated_products"]) > 0) {
                $associatedProductData["status"] = true;
                $associatedProductData["parent_product"] = [
                    "parent_product_id" => null,
                    "id" => $productId,
                    "name" => $parentProduct->getName(),
                    "url" => $parentProduct->getProductUrl(),
                    "image" => $this->helper->getProductImage($parentProduct),
                    "price_html" => $this->productHelper->getProductPriceHtml($parentProduct),
                    "price" => $this->productHelper->getPrice($parentProduct),
                    "add_to_cart_url" => $this->getAddToCartUrl($parentProduct)
                ];
            }
            else {
                $associatedProductData["false"] = true;
            }
            return $this->jsonFactory->create()->setData($associatedProductData);
        } catch (NoSuchEntityException | Exception $e) {
            return $this->jsonFactory->create()->setData(["status" => false, "message" => $e->getMessage()]);
        }
    }

    /**
     * Get product add to cart url.
     *
     * @param CatalogProduct $product
     * @return string
     */
    protected function getAddToCartUrl(CatalogProduct $product): string
    {
        return $this->abstractProduct->getAddToCartUrl($product);
    }


}