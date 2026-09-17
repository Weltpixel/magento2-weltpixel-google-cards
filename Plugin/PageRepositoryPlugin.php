<?php

namespace WeltPixel\GoogleCards\Plugin;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\Message\ManagerInterface;
use WeltPixel\GoogleCards\Model\Config\FileUploader\FileProcessor;

/**
 * Class PageRepositoryPlugin
 * @package WeltPixel\GoogleCards\Plugin
 */
class PageRepositoryPlugin
{
    /**
     * @var FileProcessor
     */
    protected $fileProcessor;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * PageRepositoryPlugin constructor.
     * @param FileProcessor $fileProcessor
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        FileProcessor $fileProcessor,
        ManagerInterface $messageManager
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->messageManager = $messageManager;
    }

    /**
     * @param PageRepository $subject
     * @param PageInterface $page
     * @return PageInterface[]
     */
    public function beforeSave(
        PageRepository $subject,
        PageInterface $page
    ): array {
        $data = $page->getData();
        $imageField = 'og_meta_image';
        $entityImage = null;
        if (isset($data[$imageField])) {
            if (is_array($data[$imageField])) {
                $entityImage = $data[$imageField][0];
            } else {
                $entityImage = $data[$imageField];
            }
        }

        if ($entityImage && is_array($entityImage)) {
            /** Nothing was changed on the images */
            if (isset($entityImage['existingImage'])) {
                $data[$imageField] = $entityImage['existingImage'];
            } else {
                /** New Image was uploaded */
                try {
                    $entityImagePath = $this->fileProcessor->saveToPath($entityImage);
                    $data[$imageField] = $entityImagePath;
                } catch (\Exception $ex) {
                    /**
                     * The message manager was used here without ever being injected, so this
                     * handler called a method on null: any rejected or stale upload became a
                     * fatal instead of a message. It is reachable without a crafted request,
                     * because renameFile() throws when the tmp file is gone.
                     */
                    $this->messageManager->addErrorMessage($ex->getMessage());
                }
            }
        } else {
            $data[$imageField] = $entityImage;
        }

        $page->setData($data);
        return [$page];
    }
}
