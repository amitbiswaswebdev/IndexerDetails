<?php
/**
 * Copyright © 2023 Easy. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author    Amit Biswas <amit.biswas.webdev@gmail.com>
 * @copyright 2023 Easy
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Easy\IndexerDetails\Ui\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as CoreProductDataProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Backend\Model\Session;
use Magento\Indexer\Model\IndexerFactory;
use Easy\IndexerDetails\Logger\Logger as IndexerDetailsLogger;
use InvalidArgumentException;

/**
 * Class DataProvider
 */
class DataProvider extends CoreProductDataProvider
{
    /**
     * Allowed indexer type to view details
     */
    public const ALLOWED_VIEW_DETAILS = [
        'catalogsearch_fulltext',
        'catalog_product_price',
        'targetrule_product_rule',
        'targetrule_rule_product',
        'catalog_product_price',
        'cataloginventory_stock',
        'catalogpermissions_product',
        'catalogrule_product',
        'catalog_product_price',
        'targetrule_product_rule',
        'catalog_product_attribute',
        'catalog_product_category',
        'inventory'
    ];

    /**
     * @var PoolInterface
     */
    private $modifiersPool;

    /**
     * @var Session
     */
    private Session $backendSession;

    /**
     * @var IndexerFactory
     */
    private IndexerFactory $indexerFactory;

    /**
     * @var IndexerDetailsLogger
     */
    private $logger;

    /**
     * @param Session $backendSession
     * @param IndexerFactory $indexerFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param IndexerDetailsLogger $logger
     * @param AddFieldToCollectionInterface[] $addFieldStrategies
     * @param AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $modifiersPool
     */
    public function __construct(
        Session $backendSession,
        IndexerFactory $indexerFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        IndexerDetailsLogger $logger,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = [],
        PoolInterface $modifiersPool = null
    ) {
        $this->backendSession = $backendSession;
        $this->indexerFactory = $indexerFactory;
        $this->logger = $logger;
        $this->modifiersPool = $modifiersPool ?: ObjectManager::getInstance()->get(
                PoolInterface::class
            );
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data,
            $modifiersPool
        );
    }

    /**
     * Get data
     *
     * @return array
     * @throws LocalizedException
     */
    public function getData(): array
    {
        if (!$this->getCollection()->isLoaded()) {
            $ids = [];
            try {
                $ids = $this->getBackLogIds();
            } catch (\InvalidArgumentException $exception) {
                $this->logger->error('Invalid argument while getting back log ids', [
                    'message' => $exception->getMessage(),
                    'stack_trace' => $exception->getTraceAsString(),
                ]);                
            }
            $uniqueIds = array_unique($ids);
            $this->getCollection()->addFieldToFilter('entity_id', ['in'=> $uniqueIds ]);
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();
        $data = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    protected function getBackLogIds() :array
    {
        $indexType = $this->backendSession->getIndexerType();
        if ($indexType !== null && in_array($indexType, SELF::ALLOWED_VIEW_DETAILS, true)) {
            $indexer = $this->indexerFactory->create();
            $indexer->load($indexType);
            $view = $indexer->getView();
        } else {
            throw new InvalidArgumentException(' Invalid index type : ' . $indexType);
        }
        $state = $view->getState()->loadByView($view->getId());
        $changelog = $view->getChangelog()->setViewId($view->getId());
        $currentVersionId = $changelog->getVersion();

        return $changelog->getList($state->getVersionId(), $currentVersionId);
    }
}
