<?php

namespace Browscap\Writer;

use Psr\Log\LoggerInterface;

/**
 * Class BrowscapXmlGenerator
 *
 * @package Browscap\Generator
 */
class XmlWriter implements WriterInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @var resource
     */
    private $file = null;

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = fopen($file, 'w');
    }

    public function __destruct()
    {
        fclose($this->file);
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Generate the header
     *
     * @param string[] $comments
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function renderHeader(array $comments = array())
    {
        $this->getLogger()->debug('rendering comments');

        //fputs($this->file, $xmlWriter->startElement('comments'));

        foreach ($comments as $text) {
            //fputs($this->file, $xmlWriter->startElement('comment'));
            //fputs($this->file, $xmlWriter->writeCData($text));
            //fputs($this->file, $xmlWriter->endElement());
        }

        //fputs($this->file, $xmlWriter->endElement());

        return $this;
    }

    /**
     * renders the version information
     *
     * @param string[] $versionData
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function renderVersion(array $versionData = array())
    {
        $this->getLogger()->debug('rendering version information');

        //fputs($this->file, $xmlWriter->startElement('gjk_browscap_version'));

        if (!isset($versionData['version'])) {
            $versionData['version'] = '0';
        }

        if (!isset($versionData['released'])) {
            $versionData['released'] = '';
        }

        //fputs($this->file, $xmlWriter->startElement('item'));
        //fputs($this->file, $xmlWriter->writeAttribute('name', 'Version'));
        //fputs($this->file, $xmlWriter->writeAttribute('value', $versionData['version']));
        //fputs($this->file, $xmlWriter->endElement());

        //fputs($this->file, $xmlWriter->startElement('item'));
        //fputs($this->file, $xmlWriter->writeAttribute('name', 'Released'));
        //fputs($this->file, $xmlWriter->writeAttribute('value', $versionData['released']));
        //fputs($this->file, $xmlWriter->endElement());

        //fputs($this->file, $xmlWriter->endElement());

        return $this;
    }

    /**
     * renders the header for a division
     *
     * @param string $division
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function renderDivisionHeader($division)
    {
        return $this;
    }

    /**
     * renders all found useragents into a string
     *
     * @param array[] $allDivisions
     * @param string  $output
     * @param array   $allProperties
     *
     * @throws \InvalidArgumentException
     * @return \Browscap\Writer\WriterInterface
     */
    public function renderDivisionBody(array $allDivisions, $output, array $allProperties)
    {
        return $this;
    }

    /**
     * @param \Browscap\Formatter\FormatterInterface $formatter
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function addFormatter(\Browscap\Formatter\FormatterInterface $formatter)
    {
        return $this;
    }

    /**
     * @param \Browscap\Filter\FilterInterface $filter
     *
     * @return \Browscap\Writer\WriterInterface
     */
    public function addFilter(\Browscap\Filter\FilterInterface $filter)
    {
        return $this;
    }

    /**
     * Generate and return the formatted browscap data
     *
     * @return string
     */
    public function generate()
    {
        $this->getLogger()->debug('build output for xml file');

        return $this->render(
            $this->collectionData,
            array_keys(array('Parent' => '') + $this->collectionData['DefaultProperties'])
        );
    }

    /**
     * renders all found useragents into a string
     *
     * @param array[] $allDivisions
     * @param array   $allProperties
     *
     * @return string
     */
    private function render(array $allDivisions, array $allProperties)
    {
        $this->getLogger()->debug('rendering XML structure');

        $xmlWriter = new \XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $xmlWriter->setIndentString('');
        $xmlWriter->startDocument('1.0', 'UTF-8');
        file_put_contents($this->file, $xmlWriter->flush(true));

        $xmlWriter->startElement('browsercaps');
        $this->renderHeader($xmlWriter);
        file_put_contents($this->file, $xmlWriter->flush(true), FILE_APPEND);

        $this->renderVersion($xmlWriter);
        file_put_contents($this->file, $xmlWriter->flush(true), FILE_APPEND);

        $xmlWriter->startElement('browsercapitems');

        $counter = 1;

        $this->getLogger()->debug('rendering all divisions');

        foreach ($allDivisions as $key => $properties) {
            $this->getLogger()->debug('rendering division "' . $properties['division'] . '" - "' . $key . '"');

            $counter++;

            if (!$this->firstCheckProperty($key, $properties, $allDivisions)) {
                $this->getLogger()->debug('first check failed on key "' . $key . '" -> skipped');

                continue;
            }

            if (!in_array($key, array('DefaultProperties', '*'))) {
                $parent = $allDivisions[$properties['Parent']];
            } else {
                $parent = array();
            }

            $propertiesToOutput = $properties;

            foreach ($propertiesToOutput as $property => $value) {
                if (!isset($parent[$property])) {
                    continue;
                }

                $parentProperty = $parent[$property];

                switch ((string) $parentProperty) {
                    case 'true':
                        $parentProperty = true;
                        break;
                    case 'false':
                        $parentProperty = false;
                        break;
                    default:
                        $parentProperty = trim($parentProperty);
                        break;
                }

                if ($parentProperty != $value) {
                    continue;
                }

                unset($propertiesToOutput[$property]);
            }

            // create output - xml
            $xmlWriter->startElement('browscapitem');
            $xmlWriter->writeAttribute('name', $key);

            foreach ($allProperties as $property) {
                if (!isset($propertiesToOutput[$property])) {
                    continue;
                }

                if (!CollectionParser::isOutputProperty($property)) {
                    continue;
                }

                if (CollectionParser::isExtraProperty($property)) {
                    continue;
                }

                $this->createItem($xmlWriter, $property, $this->formatValue($property, $propertiesToOutput));
            }

            $xmlWriter->endElement(); // browscapitem
            file_put_contents($this->file, $xmlWriter->flush(true), FILE_APPEND);

            unset($propertiesToOutput);
        }

        unset($allDivisions, $allProperties, $counter, $key, $properties, $property);

        $xmlWriter->endElement(); // browsercapitems
        $xmlWriter->endElement(); // browsercaps
        $xmlWriter->endDocument();

        file_put_contents($this->file, $xmlWriter->flush(true), FILE_APPEND);

        return '';
    }

    /**
     * @param \XMLWriter $xmlWriter
     * @param string     $property
     * @param mixed      $valueOutput
     */
    private function createItem(XMLWriter $xmlWriter, $property, $valueOutput)
    {
        $this->getLogger()->debug('create item for property "' . $property . '"');
        $xmlWriter->startElement('item');
        $xmlWriter->writeAttribute('name', $property);
        $xmlWriter->writeAttribute('value', $valueOutput);
        $xmlWriter->endElement();
    }
}
