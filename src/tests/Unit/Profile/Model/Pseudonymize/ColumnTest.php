<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Profile\Model\Pseudonymize;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Waldhacker\Pseudify\Core\Processor\Encoder\Base64Encoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\CsvEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzCompressEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzDeflateEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzEncodeEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\HexEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\JsonEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ScalarEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\TYPO3\FlexformEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\XmlEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\YamlEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ZlibEncodeEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\DataProcessingInterface;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Column;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\MissingDataProcessingException;

class ColumnTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateCreatesColumn(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new ScalarEncoder([]));
        $this->assertEquals($column, Column::create('foo'));
    }

    public function testCreateCreatesColumnWithBase64Encoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new Base64Encoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_BASE64));
    }

    public function testCreateCreatesColumnWithCsvEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new CsvEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_CSV));
    }

    public function testCreateCreatesColumnWithGzCompressEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new GzCompressEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_GZCOMPRESS));
    }

    public function testCreateCreatesColumnWithGzDeflateEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new GzDeflateEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_GZDEFLATE));
    }

    public function testCreateCreatesColumnWithGzEncodeEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new GzEncodeEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_GZENCODE));
    }

    public function testCreateCreatesColumnWithHexEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new HexEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_HEX));
    }

    public function testCreateCreatesColumnWithJsonEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new JsonEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_JSON));
    }

    public function testCreateCreatesColumnWithScalarEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new ScalarEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_SCALAR));
    }

    public function testCreateCreatesColumnWithSerializedEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new SerializedEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_SERIALIZED));
    }

    public function testCreateCreatesColumnWithFlexformEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new FlexformEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_TYPO3_FLEXFORM));
    }

    public function testCreateCreatesColumnWithXmlEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new XmlEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_XML));
    }

    public function testCreateCreatesColumnWithYamlEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new YamlEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_YAML));
    }

    public function testCreateCreatesColumnWithZlibEncodeEncoder(): void
    {
        $column = new Column('foo');
        $column->setEncoder(new ZlibEncodeEncoder([]));
        $this->assertEquals($column, Column::create('foo', Column::DATA_TYPE_ZLIBENCODE));
    }

    public function testHasDataProcessingReturnsTrue(): void
    {
        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $this->assertTrue($column->hasDataProcessing('id-1'));
    }

    public function testHasDataProcessingReturnsFalse(): void
    {
        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $this->assertFalse($column->hasDataProcessing('id-2'));
    }

    public function testGetDataProcessingReturnsDataProcessing(): void
    {
        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $this->assertEquals($dataProcessing1->reveal(), $column->getDataProcessing('id-1'));
    }

    public function testGetDataProcessingThrowsException(): void
    {
        $this->expectException(MissingDataProcessingException::class);
        $this->expectExceptionCode(1621654992);

        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $column->getDataProcessing('id-42');
    }

    public function testRemoveDataProcessingRemovesDataProcessing(): void
    {
        $this->expectException(MissingDataProcessingException::class);
        $this->expectExceptionCode(1621654992);

        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $dataProcessing2 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing2->getIdentifier()->willReturn('id-2');

        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $column->addDataProcessing($dataProcessing2->reveal());
        $column->removeDataProcessing('id-1');
        $column->getDataProcessing('id-1');
    }

    public function testGetDataProcessingIdentifiersReturnsDataProcessingIdentifiers(): void
    {
        $dataProcessing1 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing1->getIdentifier()->willReturn('id-1');
        $dataProcessing2 = $this->prophesize(DataProcessingInterface::class);
        $dataProcessing2->getIdentifier()->willReturn('id-2');

        $column = new Column('foo');
        $column->addDataProcessing($dataProcessing1->reveal());
        $column->addDataProcessing($dataProcessing2->reveal());

        $this->assertEquals(['id-1', 'id-2'], $column->getDataProcessingIdentifiers());
    }

    public function testSetBindingTypeSetsBindingType(): void
    {
        $column = new Column('foo');
        $column->setBindingType(42);
        $this->assertEquals(42, $column->getBindingType());
    }
}
