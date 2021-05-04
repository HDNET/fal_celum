<?php

declare(strict_types=1);

namespace HDNET\FalCelum\Index;

use HDNET\FalCelum\Client\CelumClient;
use HDNET\FalCelum\Driver\CelumDriver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\File;

class Extractor implements ExtractorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected CelumClient $celumClient;

    public function __construct(CelumClient $celumClient)
    {
        $this->celumClient = $celumClient;
    }

    public function getFileTypeRestrictions()
    {
        return [];
    }

    public function getDriverRestrictions()
    {
        return [CelumDriver::DRIVER_TYPE];
    }

    public function getPriority()
    {
        return 60;
    }

    public function getExecutionPriority()
    {
        return 60;
    }

    public function canProcess(File $file)
    {
        return $file->getStorage()->getDriverType() === CelumDriver::DRIVER_TYPE;
    }

    public function extractMetaData(File $file, array $previousExtractedData = [])
    {
        $this->logger->debug("extractMetaData(" . $file->getIdentifier() . ", " . json_encode($previousExtractedData) . ")");
        return $this->celumClient->getFileInfo($file->getIdentifier())['info'];
    }
}
