<?php
namespace WeltPixel\GoogleCards\Model\Config\FileUploader;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileProcessor
{
    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * Media Directory object (writable).
     *
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    const FILE_DIR = 'weltpixel/googlecards/cms';

    /**
     * @var AdapterFactory
     */
    protected $imageAdapterFactory;

    /**
     * FileProcessor constructor.
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param AdapterFactory $imageAdapterFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        AdapterFactory $imageAdapterFactory
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageAdapterFactory = $imageAdapterFactory;
    }

    /**
     * Save file to temp media directory
     *
     * @param  string $fileId
     * @return array
     */
    public function saveToTmp($fileId)
    {
        try {
            $result = $this->save($fileId, $this->getAbsoluteTmpMediaPath());
            $result['url'] = $this->getTmpMediaUrl($result['file']);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $result;
    }

    /**
     * @param $data
     * @param string $entity
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function saveToPath($data)
    {
        $file = $this->getValidatedFileName($data);

        $tmpPath = $this->getAbsoluteTmpMediaPath() . DIRECTORY_SEPARATOR . $file;
        $destinationPath = $this->getAbsoluteDestinationMediaPath() . DIRECTORY_SEPARATOR . $file;
        $this->mediaDirectory->renameFile($tmpPath, $destinationPath);

        return self::FILE_DIR . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Validate the file name a save carries back in the cms page form data.
     *
     * The uploader runs with file dispersion off, so the name it hands back is a single segment
     * with no directory part. The value that reaches saveToPath() is not that name though: it
     * comes back through the form and is whatever the request carried, and it was concatenated
     * into both a source and a destination path and handed to renameFile() unchecked. Magento's
     * directory writer already refuses to leave pub/media, so what this closes is the remaining
     * room to move a different file around inside it.
     *
     * A single segment is the right shape here. That is the opposite of the config image delete
     * fixed for M3, where the stored value legitimately carries the scope prefix and its slashes
     * and a basename would have pointed every delete at a path that does not exist.
     *
     * @param array $data
     * @return string
     * @throws LocalizedException
     */
    protected function getValidatedFileName($data)
    {
        $file = is_array($data) ? ($data['file'] ?? null) : null;

        if (!is_string($file)) {
            throw new LocalizedException(__('The uploaded image is missing a file name.'));
        }

        $file = trim(str_replace('\\', '/', $file));

        if ($file === '' || strpos($file, "\0") !== false) {
            throw new LocalizedException(__('The uploaded image has an invalid file name.'));
        }

        if (strpos($file, '/') !== false || $file === '.' || $file === '..') {
            throw new LocalizedException(__('The uploaded image name may not contain a path.'));
        }

        return $file;
    }

    /**
     * @param $file
     * @return array|false
     */
    public function getImageDetails($file)
    {
        try {
            $imageUrl = $this->getFinalMediaUrl($file);
            $imageDetails = getimagesize($this->mediaDirectory->getAbsolutePath() . $file);

            $result = [];
            $result['width'] = $imageDetails[0];
            $result['height'] = $imageDetails[1];
            $result['type'] = $imageDetails['mime'];
            $result['name'] = basename($file);
            $result['existingImage'] = $file;
            $result['url'] = $imageUrl;
            $result['previewType'] = 'image';
            $result['size'] = filesize($this->mediaDirectory->getAbsolutePath() . $file);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * Retrieve absolute temp media path
     *
     * @return string
     */
    protected function getAbsoluteTmpMediaPath()
    {
        return $this->mediaDirectory->getAbsolutePath('tmp/' . self::FILE_DIR);
    }

    /**
     * Retrieve absolute destination media path
     *
     * @return string
     */
    protected function getAbsoluteDestinationMediaPath()
    {
        return $this->mediaDirectory->getAbsolutePath(self::FILE_DIR);
    }

    /**
     * @param $file
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getTmpMediaUrl($file)
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . 'tmp/' . self::FILE_DIR . '/' . $this->prepareFile($file);
    }

    /**
     * @param $file
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFinalMediaUrl($file)
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
         . $this->prepareFile($file);
    }

    /**
     * Prepare file
     *
     * @param string $file
     * @return string
     */
    protected function prepareFile($file)
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }

    /**
     * @param $fileId
     * @param $destination
     * @return array
     * @throws \Exception
     */
    protected function save($fileId, $destination)
    {
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);

        /**
         * The extension allowlist above is a check on the name, not on the content.
         * Uploader::_validateFile() tests the extension and then runs whatever validate callbacks
         * were registered, and none were, so a file that is not an image at all was accepted as
         * long as it was called .png. The image adapter's validateUploadFile() opens the file and
         * rejects anything it cannot decode as an image; it is the same callback core registers
         * for product gallery uploads.
         */
        $uploader->addValidateCallback(
            'weltpixel_googlecards_og_image',
            $this->imageAdapterFactory->create(),
            'validateUploadFile'
        );

        $result = $uploader->save($destination);

        return $result;
    }
}
