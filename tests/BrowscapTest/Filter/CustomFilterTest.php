<?php
declare(strict_types = 1);
namespace BrowscapTest\Filter;

use Browscap\Data\Division;
use Browscap\Data\PropertyHolder;
use Browscap\Filter\CustomFilter;
use Browscap\Filter\FilterInterface;
use Browscap\Writer\IniWriter;
use Browscap\Writer\WriterInterface;
use PHPUnit\Framework\TestCase;

class CustomFilterTest extends TestCase
{
    /**
     * @var CustomFilter
     */
    private $object;

    protected function setUp() : void
    {
        $propertyHolder = $this->getMockBuilder(PropertyHolder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOutputProperty'])
            ->getMock();

        $propertyHolder
            ->expects(static::any())
            ->method('isOutputProperty')
            ->willReturn(true);

        $this->object = new CustomFilter($propertyHolder, ['Parent']);
    }

    /**
     * tests getter for the filter type
     */
    public function testGetType() : void
    {
        static::assertSame(FilterInterface::TYPE_CUSTOM, $this->object->getType());
    }

    /**
     * tests detecting if a divion should be in the output
     *
     * @throws \ReflectionException
     */
    public function testIsOutput() : void
    {
        $division = $this->createMock(Division::class);

        static::assertTrue($this->object->isOutput($division));
    }

    /**
     * Data Provider for the test testIsOutputProperty
     *
     * @return array
     */
    public function outputPropertiesDataProvider() : array
    {
        return [
            ['Comment', false],
            ['Browser', false],
            ['Platform', false],
            ['Platform_Description', false],
            ['Device_Name', false],
            ['Device_Maker', false],
            ['RenderingEngine_Name', false],
            ['RenderingEngine_Description', false],
            ['Parent', true],
            ['Platform_Version', false],
            ['RenderingEngine_Version', false],
            ['Version', false],
            ['MajorVer', false],
            ['MinorVer', false],
            ['CssVersion', false],
            ['AolVersion', false],
            ['Alpha', false],
            ['Beta', false],
            ['Win16', false],
            ['Win32', false],
            ['Win64', false],
            ['Frames', false],
            ['IFrames', false],
            ['Tables', false],
            ['Cookies', false],
            ['BackgroundSounds', false],
            ['JavaScript', false],
            ['VBScript', false],
            ['JavaApplets', false],
            ['ActiveXControls', false],
            ['isMobileDevice', false],
            ['isSyndicationReader', false],
            ['Crawler', false],
            ['lite', false],
            ['sortIndex', false],
            ['Parents', false],
            ['division', false],
            ['Browser_Type', false],
            ['Device_Type', false],
            ['Device_Pointing_Method', false],
            ['isTablet', false],
            ['Browser_Maker', false],
        ];
    }

    /**
     * @dataProvider outputPropertiesDataProvider
     *
     * @param string $propertyName
     * @param bool   $isExtra
     */
    public function testIsOutputProperty(string $propertyName, bool $isExtra) : void
    {
        $mockWriterIni = $this->getMockBuilder(IniWriter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType'])
            ->getMock();

        $mockWriterIni
            ->expects(static::never())
            ->method('getType')
            ->willReturn(WriterInterface::TYPE_INI);

        static::assertSame($isExtra, $this->object->isOutputProperty($propertyName, $mockWriterIni));
    }

    /**
     * @dataProvider outputPropertiesDataProvider
     *
     * @param string $propertyName
     */
    public function testIsOutputPropertyModified(string $propertyName) : void
    {
        $propertyHolder = $this->getMockBuilder(PropertyHolder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOutputProperty'])
            ->getMock();

        $propertyHolder
            ->expects(static::any())
            ->method('isOutputProperty')
            ->willReturn(false);

        $mockWriterIni = $this->getMockBuilder(IniWriter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType'])
            ->getMock();

        $mockWriterIni
            ->expects(static::never())
            ->method('getType')
            ->willReturn(WriterInterface::TYPE_INI);

        $object = new CustomFilter($propertyHolder, ['Parent']);
        static::assertFalse($object->isOutputProperty($propertyName, $mockWriterIni));
    }

    /**
     * @dataProvider outputPropertiesDataProvider
     *
     * @param string $propertyName
     */
    public function testIsOutputPropertyWithPropertyHolder(string $propertyName) : void
    {
        $propertyHolder = $this->getMockBuilder(PropertyHolder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOutputProperty'])
            ->getMock();

        $propertyHolder
            ->expects(static::once())
            ->method('isOutputProperty')
            ->willReturn(false);

        $mockWriterIni = $this->getMockBuilder(IniWriter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType'])
            ->getMock();

        $mockWriterIni
            ->expects(static::never())
            ->method('getType')
            ->willReturn(WriterInterface::TYPE_INI);

        $object = new CustomFilter($propertyHolder, ['Parent']);
        static::assertFalse($object->isOutputProperty($propertyName, $mockWriterIni));
    }

    /**
     * tests if a section is always in the output
     */
    public function testIsOutputSectionAlways() : void
    {
        static::assertTrue($this->object->isOutputSection([]));
        static::assertTrue($this->object->isOutputSection(['lite' => false]));
        static::assertTrue($this->object->isOutputSection(['lite' => true]));
    }
}
