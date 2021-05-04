<?php

declare(strict_types=1);
/**
 *
 */

namespace HDNET\FalCelum\Driver;

use HDNET\FalCelum\Exception;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * AbstractReadOnlyDriver
 *
 * Define all functions with write access
 */
abstract class AbstractReadOnlyDriver implements DriverInterface
{
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function renameFolder($folderIdentifier, $newName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function createFile($fileName, $parentFolderIdentifier): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function renameFile($fileIdentifier, $newName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function replaceFile($fileIdentifier, $localFilePath): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function deleteFile($fileIdentifier): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): void
    {
        throw new Exception('Storage is read-only.');
    }

    public function setFileContents($fileIdentifier, $contents): void
    {
        throw new Exception('Storage is read-only.');
    }
}
