<?php
/**
 * Plugin for Magento Commerce CMS Staging Page Hydrator
 * Processes og_meta_image field before hydration to ensure proper image path storage
 */

namespace WeltPixel\GoogleCards\Plugin\CmsStaging;

use WeltPixel\GoogleCards\Model\Config\FileUploader\FileProcessor;
use Psr\Log\LoggerInterface;

class PageHydratorPlugin
{
    /**
     * @var FileProcessor
     */
    protected $fileProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param FileProcessor $fileProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        FileProcessor $fileProcessor,
        LoggerInterface $logger
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->logger = $logger;
    }

    /**
     * Process og_meta_image field before hydration
     *
     * @param \Magento\CmsStaging\Model\Page\Hydrator $subject
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeHydrate(
        \Magento\CmsStaging\Model\Page\Hydrator $subject,
        array $data
    ): array {
        $imageField = 'og_meta_image';

        if (!isset($data[$imageField])) {
            return [$data];
        }

        // If already a string (processed), return as-is
        if (is_string($data[$imageField])) {
            return [$data];
        }

        // Handle array format from UI component
        if (is_array($data[$imageField])) {
            $entityImage = $data[$imageField];

            // Check if it's a nested array (UI component format: [0 => [...]])
            if (isset($entityImage[0]) && is_array($entityImage[0])) {
                $entityImage = $entityImage[0];
            }

            // Image was removed (empty array)
            if (empty($entityImage)) {
                $data[$imageField] = null;
                return [$data];
            }

            // Existing image - no changes made
            if (isset($entityImage['existingImage'])) {
                $data[$imageField] = $entityImage['existingImage'];
                return [$data];
            }

            // New image uploaded - move from tmp to permanent location
            if (isset($entityImage['file'])) {
                try {
                    $entityImagePath = $this->fileProcessor->saveToPath($entityImage);
                    $data[$imageField] = $entityImagePath;
                } catch (\Exception $e) {
                    $this->logger->error(
                        'WeltPixel GoogleCards: Error processing og_meta_image in CMS Staging: ' . $e->getMessage()
                    );
                    $data[$imageField] = null;
                }
                return [$data];
            }

            // Fallback: if array doesn't match expected format, set to null
            $data[$imageField] = null;
        }

        return [$data];
    }
}

