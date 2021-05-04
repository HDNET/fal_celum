<?php

declare(strict_types=1);

namespace HDNET\FalCelum\Driver;

use HDNET\FalCelum\Exception;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * AbstractReadOnlyDriver
 *
 * Define all functions with write access to build a read-only-driver
 */
abstract class AbstractReadOnlyDriver implements DriverInterface
{
    protected const READ_ONLY_STORAGE_EXCEPTION_MESSAGE = 'Storage is read-only. If you see this message, you should configure the storage as read-onle';

    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function renameFolder($folderIdentifier, $newName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function createFile($fileName, $parentFolderIdentifier): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function renameFile($fileIdentifier, $newName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function replaceFile($fileIdentifier, $localFilePath): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function deleteFile($fileIdentifier): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function setFileContents($fileIdentifier, $contents): void
    {
        throw new Exception(self::READ_ONLY_STORAGE_EXCEPTION_MESSAGE);
    }

    public function getPermissions($identifier)
    {
        return ['r' => true, 'w' => false];
    }
}
