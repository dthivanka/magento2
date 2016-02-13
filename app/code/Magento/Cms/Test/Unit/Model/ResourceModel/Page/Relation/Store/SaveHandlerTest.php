<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Test\Unit\Model\ResourceModel\Page\Relation\Store;

use Magento\Cms\Model\ResourceModel\Page;
use Magento\Cms\Model\ResourceModel\Page\Relation\Store\SaveHandler;
use Magento\Framework\Model\Entity\MetadataPool;

class SaveHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SaveHandler
     */
    protected $model;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataPool;

    /**
     * @var Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourcePage;

    protected function setUp()
    {
        $this->metadataPool = $this->getMockBuilder('Magento\Framework\Model\Entity\MetadataPool')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourcePage = $this->getMockBuilder('Magento\Cms\Model\ResourceModel\Page')
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new SaveHandler(
            $this->metadataPool,
            $this->resourcePage
        );
    }

    public function testExecute()
    {
        $entityId = 1;
        $linkId = 2;
        $oldStore = 1;
        $newStore = 2;
        $linkField = 'link_id';

        $adapter = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->getMockForAbstractClass();

        $whereForDelete = [
            $linkField . ' = ?' => $linkId,
            'store_id IN (?)' => [$oldStore],
        ];
        $adapter->expects($this->once())
            ->method('delete')
            ->with('cms_page_store', $whereForDelete)
            ->willReturnSelf();

        $whereForInsert = [
            $linkField => $linkId,
            'store_id' => $newStore,
        ];
        $adapter->expects($this->once())
            ->method('insertMultiple')
            ->with('cms_page_store', [$whereForInsert])
            ->willReturnSelf();

        $entityMetadata = $this->getMockBuilder('Magento\Framework\Model\Entity\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getEntityConnection')
            ->willReturn($adapter);
        $entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);

        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with('Magento\Cms\Model\Page')
            ->willReturn($entityMetadata);

        $this->resourcePage->expects($this->once())
            ->method('lookupStoreIds')
            ->willReturn([$oldStore]);
        $this->resourcePage->expects($this->once())
            ->method('getTable')
            ->with('cms_page_store')
            ->willReturn('cms_page_store');

        $page = $this->getMockBuilder('Magento\Cms\Model\Page')
            ->disableOriginalConstructor()
            ->setMethods([
                'getStores',
                'getStoreId',
                'getId',
                'getData',
            ])
            ->getMock();
        $page->expects($this->once())
            ->method('getStores')
            ->willReturn(null);
        $page->expects($this->once())
            ->method('getStoreId')
            ->willReturn($newStore);
        $page->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);
        $page->expects($this->exactly(2))
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkId);

        $result = $this->model->execute('Magento\Cms\Model\Page', $page);
        $this->assertInstanceOf('Magento\Cms\Model\Page', $result);
    }
}