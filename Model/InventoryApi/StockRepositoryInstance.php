<?php
/**
 * @author Convert Team
 * @copyright Copyright (c) 2018 Convert (http://www.convert.no/)
 */
namespace Porterbuddy\Porterbuddy\Model\InventoryApi;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Module\Manager;

/**
 * Class KasperFactory
 * @package Model\Klarna
 */
class StockRepositoryInstance
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * KasperFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    /**
     * @return \Magento\InventoryApi\Api\StockRepositoryInterface|null
     */
    public function get()
    {
        $instanceName = \Magento\InventoryApi\Api\StockRepositoryInterface::class;
        if ($this->moduleManager->isEnabled('Magento_InventoryApi')
            && class_exists($instanceName)) {
                return $this->objectManager->get($instanceName);
        }

        return null;
    }
}
