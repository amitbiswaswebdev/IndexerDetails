<?php
/**
 * Copyright © 2023 EasyMage. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author    Amit Biswas <amit.biswas.webdev@gmail.com>
 * @copyright 2023 EasyMage
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace EasyMage\IndexerDetails\Block\Adminhtml\Backend\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use EasyMage\IndexerDetails\Ui\DataProvider\Product\DataProvider;

/**
 * @ViewDetails
 */
class Link extends AbstractRenderer
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Context $context,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Renders grid column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row): string
    {
        $link = '';
        $type = $row->getIndexerId();
        if (in_array($type, DataProvider::ALLOWED_VIEW_DETAILS, true)) {
            $url = $this->urlBuilder->getUrl(
                    'indexer/indexer/details',
                    [
                        '_current' => true,
                        '_use_rewrite' => true,
                        '_query' => [
                            'type' => $type
                        ]
                    ]
                );
            $link = '<a href="'.$url.'">View Details</a>';
        }
        return $link;
    }
}
