<?php

declare(strict_types=1);

namespace HDNET\FalCelum\Driver;

use HDNET\FalCelum\Client\CelumClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class CelumDriver extends AbstractReadOnlyDriver implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public const DRIVER_TYPE = 'CelumDriver';

    public const ROOT_FOLDER_IDENTIFIER = '/';

    public const ROOT_FOLDER_NAME = 'Celum';

    protected CelumClient $client;
    protected $roots;
    protected $capabilities;
    protected $configuration;
    protected $storageUid;

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
        $this->capabilities = ResourceStorage::CAPABILITY_BROWSABLE | ResourceStorage::CAPABILITY_PUBLIC;
    }

    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration(): void
    {
        $this->logger->debug("processConfiguration()");
        $this->roots = preg_split('/\\s*,\\s*/', trim($this->configuration['roots']));
        foreach ($this->roots as $key => $val) {
            $this->roots[$key] = "/$val/";
        }
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize(): void
    {
        $this->logger->debug("initialize()");
        $this->client = GeneralUtility::makeInstance(CelumClient::class, $this->configuration, $this->storageUid);
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        $this->logger->debug("mergeConfigurationCapabilities($capabilities): $this->capabilities");
        return $this->capabilities;
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        $this->logger->debug("getRootLevelFolder(): " . self::ROOT_FOLDER_IDENTIFIER);
        return self::ROOT_FOLDER_IDENTIFIER;
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        $ret = $this->getRootLevelFolder();
        $this->logger->debug("getDefaultFolder(): $ret");
        return $ret;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($identifier)
    {
        $ret = $this->client->getUrl($identifier, 'publicUrl');
        $this->logger->debug("getPublicURL($identifier): $ret");
        return $ret;
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $ret = ((substr($fileIdentifier, -1, 1) != '/') and ($this->getFileInfoByIdentifier($fileIdentifier) !== null));
        $this->logger->debug("fileExists($fileIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $ret = (($folderIdentifier === self::ROOT_FOLDER_IDENTIFIER) or ($this->getFolderInfoByIdentifier($folderIdentifier) !== null));
        $this->logger->debug("folderExists($folderIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        $ret = $this->countFoldersInFolder($folderIdentifier) + $this->countFilesInFolder($folderIdentifier) == 0;
        $this->logger->debug("isFolderEmpty($folderIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        $ret = $this->hashIdentifier($fileIdentifier);
        $this->logger->debug("hash($fileIdentifier, $hashAlgorithm): $ret");
        return $ret;
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        $this->logger->debug("getFileContents($fileIdentifier)");
        return file_get_contents($this->client->getUrl($fileIdentifier, 'publicUrl'));
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     * @throws Exception
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        $this->logger->debug("fileExistsInFolder($fileName, $folderIdentifier)");
        throw new Exception('Only requests by identifier are supported.');
        //return in_array($fileName, $this->client->getFolderInfo($folderIdentifier)['assetNames']);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     * @throws Exception
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        $this->logger->debug("folderExistsInFolder($folderName, $folderIdentifier)");
        throw new Exception('Only requests by identifier are supported.');
        //return in_array($folderName, $this->client->getFolderInfo($folderIdentifier)['childrenNames']);
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        $this->logger->debug("getFileForLocalProcessing($fileIdentifier, $writable)");
        $tmp = GeneralUtility::tempnam('fal-tempfile-', '.' . $this->client->getFileInfo($fileIdentifier)['extension']);
        file_put_contents($tmp, fopen($this->client->getUrl($fileIdentifier, 'publicUrl'), 'r'));
        return $tmp;
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     */
    public function dumpFileContents($identifier): void
    {
        $this->logger->debug("dumpFileContents($identifier)");
        $handle = fopen('php://output', 'w');
        fwrite($handle, file_get_contents($this->client->getUrl($identifier, 'publicUrl'))); // ex thumbnail
        fclose($handle);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $id = rtrim($identifier, '/\\') . '/';
        $ret = ($identifier and strpos($id, $folderIdentifier) === 0);
        $this->logger->debug("isWithin($folderIdentifier, $identifier): " . $ret ? 'true' : 'false');
        return $ret;
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $ret = $this->client->getFileInfo($fileIdentifier)['info'];
        $this->logger->debug("getFileInfoByIdentifier($fileIdentifier, " . json_encode($propertiesToExtract) . "): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        static $cache = [];
        if (array_key_exists($folderIdentifier, $cache)) {
            return $cache[$folderIdentifier];
        }

        // @todo move to Cache class

        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($folderIdentifier == self::ROOT_FOLDER_IDENTIFIER) {
            $ret = ['identifier' => self::ROOT_FOLDER_IDENTIFIER, 'name' => self::ROOT_FOLDER_NAME, 'storage' => $this->storageUid];
        } else {
            $ret = $this->client->getFolderInfo($folderIdentifier)['info'];
        }
        $this->logger->debug("getFolderInfoByIdentifier($folderIdentifier): " . json_encode($ret));
        $cache[$folderIdentifier] = $ret;
        return $ret;
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     * @throws Exception
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        $this->logger->debug("getFileInFolder($fileName, $folderIdentifier)");
        throw new Exception('Only requests by identifier are supported.');
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($folderIdentifier == self::ROOT_FOLDER_IDENTIFIER) {
            $ret = [];
        } elseif (($start > 0) or ($numberOfItems > 0)) {
            $ret = array_slice($this->client->getFolderInfo($folderIdentifier)['assets'], $start >= 0 ? $start : 0, $numberOfItems <= 0 ? null : $numberOfItems);
        } else {
            $ret = $this->client->getFolderInfo($folderIdentifier)['assets'];
        }
        $this->logger->debug("getFilesInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($filenameFilterCallbacks) . ", $sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     * @throws Exception
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        $this->logger->debug("getFolderInFolder($folderName, $folderIdentifier)");
        throw new Exception('Only requests by identifier are supported.');
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($folderIdentifier == self::ROOT_FOLDER_IDENTIFIER) {
            $ret = $this->roots;
        } elseif (($start > 0) or ($numberOfItems > 0)) {
            $ret = array_slice($this->client->getFolderInfo($folderIdentifier)['children'], $start >= 0 ? $start : 0, $numberOfItems <= 0 ? null : $numberOfItems);
        } else {
            $ret = $this->client->getFolderInfo($folderIdentifier)['children'];
        }
        $this->logger->debug("getFoldersInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($folderNameFilterCallbacks) . "$sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        $ret = count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
        $this->logger->debug("countFilesInFolder($folderIdentifier, $recursive, " . json_encode($filenameFilterCallbacks) . "): $ret");
        return $ret;
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        $ret = count($this->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $folderNameFilterCallbacks));
        $this->logger->debug("countFoldersInFolder($folderIdentifier, $recursive, " . json_encode($folderNameFilterCallbacks) . "): $ret");
        return $ret;
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid($storageUid): void
    {
        $this->logger->debug("setStorageUid($storageUid)");
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities()
    {
        $this->logger->debug("getCapabilities(): $this->capabilities");
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     * @return bool
     */
    public function hasCapability($capability)
    {
        $ret = ($this->capabilities & $capability) === $capability;
        $this->logger->debug("hasCapability($capability): $ret");
        return $ret;
    }

    /**
     * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
     * is a configurable setting, but the setting does not change the way the
     * underlying file system treats the identifiers; the setting should
     * therefore always reflect the file system and not try to change its
     * behaviour
     *
     * @return bool
     */
    public function isCaseSensitiveFileSystem()
    {
        $this->logger->debug("isCaseSensitiveFileSystem(): true");
        return true;
    }

    /**
     * Cleans a fileName from not allowed characters
     *
     * @param string $fileName
     * @param string $charset Charset of the a fileName
     *                        (defaults to current charset; depending on context)
     * @return string the cleaned filename
     */
    public function sanitizeFileName($fileName, $charset = '')
    {
        $this->logger->debug("sanitizeFileName($fileName, $charset): $fileName");
        return $fileName;
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        $ret = sha1($identifier);
        $this->logger->debug("hashIdentifier($identifier): $ret");
        return $ret;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     * @return string
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        $ret = rtrim(dirname($fileIdentifier), '/\\') . '/';
        $this->logger->debug("getParentFolderIdentifierOfIdentifier($fileIdentifier): $ret");
        return $ret;
    }
}
