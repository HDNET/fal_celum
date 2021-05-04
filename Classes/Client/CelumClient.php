<?php

declare(strict_types=1);

namespace HDNET\FalCelum\Client;

use GuzzleHttp\Client;
use HDNET\FalCelum\Cache;
use HDNET\FalCelum\Configuration;
use HDNET\FalCelum\Encryption;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class CelumClient implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    protected $celumUrl;
    protected $cora;
    protected $locale;
    protected $defaultLocale;
    protected Cache $cache;
    protected $directDownload;
    private $provider;
    private $description;
    private $secret;
    private $client;
    private $options;
    private $format;

    public function __construct(Configuration $configuration)
    {

        // Todo migrate to configuration


        $res = GeneralUtility::makeInstance(Encryption::class)->decrypt($config['licenseKey']);
        // Impossible to throw exceptions on invalid license, somehow there are instances created before the configuration is entered.
        if (preg_match('/^(.*)_([^_]+)$/', $res, $matches) and $matches[2] > time()) {
            $this->celumUrl = rtrim($matches[1]);
        } else {
            return;
        }
        $this->cora = $this->celumUrl . '/cora/';
        $this->format = $config['downloadFormat'];
        $this->directDownload = $this->celumUrl . '/direct/download?format=' . $config['downloadFormat'] . '&id=';
        $this->provider = ['video' => $config['publicURLsProviderVideo'], 'image' => $config['publicURLsProviderImage']];
        $this->description = ['video' => $config['publicURLsDescriptionVideo'], 'image' => $config['publicURLsDescriptionImage']];
        $this->locale = $config['locale'];
        $this->defaultLocale = $config['defaultLocale'];
        $this->secret = $config['directDownloadSecret'];
        $this->cache = GeneralUtility::makeInstance(Cache::class);
        $this->client = new Client(['base_uri' => $this->cora]);
        $this->options = ['headers' => ['Authorization' => 'celumApiKey ' . $config['celumApiKey']]];
    }

    protected function extractId($identifier)
    {
        return basename(rtrim($identifier, '/'));
    }

    protected function extractName(&$names)
    {
        $default = null;
        foreach ($names as $name) {
            if ($name['locale'] == $this->defaultLocale) {
                $default = $name['value'];
            } elseif (($name['locale'] == $this->locale) and $name['value']) {
                return $name['value'];
            }
        }
        return $default;
    }

    public function getFolderInfo($identifier)
    {
        $key = str_replace('/', '_', $identifier);

        return $this->cache->cache($key, function () use ($identifier) {
            $id = $this->extractId($identifier);
            $continue = true;
            $top = 200;
            for ($skip = 0; $continue; $skip += $top) {
                $continue = false;
                $response = $this->client->request('GET', 'Nodes(' . $id . ')?$expand=children($select=id%3B$top=' . $top . '%3B$skip=' . $skip . '),assets($select=id%3B$top=' . $top . '%3B$skip=' . $skip . ')&$select=id,name,children,assets', $this->options)->getBody();
                if ($response) {
                    $response = json_decode((string) $response, true);
                    if ($skip == 0) {
                        $data = ['info' =>
                            [
                                'identifier' => $identifier,
                                'name' => $this->extractName($response['name']),
                            ],
                            'children' => [],
                            'assets' => []
                        ];
                    }
                    if (isset($response['children'])) {
                        $c = count($response['children']);
                        if ($c > 0) {
                            foreach ($response['children'] as $child) {
                                $data['children'][] = $identifier . $child['id'] . '/';
                            }
                            if ($c == $top) {
                                $continue = true;
                            }
                        }
                    }
                    if (isset($response['assets'])) {
                        $c = count($response['assets']);
                        if ($c > 0) {
                            foreach ($response['assets'] as $asset) {
                                $data['assets'][] = $identifier . $asset['id'];
                            }
                            if ($c == $top) {
                                $continue = true;
                            }
                        }
                    }
                } elseif ($skip == 0) {
                    return ['info' => null, 'children' => [], 'assets' => []];
                }
            }
            return $data;
        });
    }

    public function getFileInfo($identifier)
    {
        $key = str_replace('/', '_', $identifier);
        return $this->cache->cache($key, function () use ($identifier) {
            $response = $this->client->request('GET', 'Assets(' . $this->extractId($identifier) . ')?$select=id,name,fileInformation,fileProperties,modificationInformation,previewInformation,fileCategory&$expand=publicUrls', $this->options)->getBody();
            if ($response) {
                $response = json_decode((string) $response, true);
                foreach ($response['fileProperties'] as $prop) {
                    if ($prop['name'] === 'width') {
                        $width = $prop['value'];
                    } elseif ($prop['name'] === 'height') {
                        $height = $prop['value'];
                    }
                }
                if ($width and $height) {
                    if ($this->format === 'prvw' and (($width > 1024) or ($height > 1024))) {
                        if ($width > $height) {
                            $height = intval($height * 1024 / $width);
                            $width = 1024;
                        } else {
                            $width = intval($width * 1024 / $height);
                            $height = 1024;
                        }
                    }
                } else {
                    $width = 0;
                    $height = 0;
                }
                $publicUrl = false;
                $type = $response['fileCategory'];
                if (($type == 'image') or ($type == 'video')) {
                    // echo $this->description . " " . $this->provider . " " . json_encode($response['publicUrls']) . "; ";
                    foreach ($response['publicUrls'] as $purl) {
                        if (($purl['provider'] == $this->provider[$type]) and ($purl['description'] == $this->description[$type])) {
                            $publicUrl = $purl['url'];
                        }
                    }
                }
                if (!$publicUrl) {
                    $id = $this->extractId($identifier);
                    $publicUrl = $this->directDownload . $id;
                    if ($this->secret) {
                        $publicUrl .= '&token=' . hash('sha256', $id . $this->secret);
                    }
                }
                $name = $response['name'];
                if ($this->format === 'prvw' or $this->format === 'largeprvw' or $this->format === 'thumb') {
                    $ext = '.jpg';
                } else {
                    $ext = '.' . $response['fileInformation']['fileExtension'];
                }
                if (substr($name, -strlen($ext)) !== $ext) {
                    if (substr($name, -1) === '.') {
                        $name .= substr($ext, 1);
                    } else {
                        $name .= $ext;
                    }
                }
                return [
                    'info' => [
                        'identifier' => $identifier,
                        'name' => $name,
                        'size' => $response['fileInformation']['originalFileSize'],
                        'width' => $width,
                        'height' => $height,
                        'mimetype' => $type . '/' . $response['fileInformation']['fileExtension'],
                        'ctime' => strtotime($response['modificationInformation']['creationDateTime']),
                        'mtime' => strtotime($response['modificationInformation']['lastModificationDateTime']),
                    ],
//                    'preview' => $response['previewInformation']['previewUrl'],
//                    'thumbnail' => $response['previewInformation']['thumbUrl'],
                    'publicUrl' => $publicUrl,
                    'extension' => $response['fileInformation']['fileExtension'],
                ];
            }

            return ['info' => null];
        });
    }

    public function getUrl($identifier, $type = 'publicUrl')
    {
        $ret = $this->getFileInfo($identifier)[$type];
        $this->logger->debug("getUrl($identifier, $type): $ret");
        return $ret;
    }
}
