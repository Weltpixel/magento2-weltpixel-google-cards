<?php
namespace WeltPixel\GoogleCards\Controller\Adminhtml\Googlecards;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use WeltPixel\GoogleCards\Model\Config\FileUploader\FileProcessor;

class Saveogimage extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Magento_Cms::page';

    /**
     * @var FileProcessor
     */
    protected $fileProcessor;

    /**
     * @param Context $context
     * @param FileProcessor $fileProcessor
     */
    public function __construct(
        Context $context,
        FileProcessor $fileProcessor
    ) {
        $this->fileProcessor = $fileProcessor;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /**
         * The action is declared POST only, and the file field is still located by name from the
         * request rather than hard coded, because the ui component decides what it is called. The
         * empty case is handled here instead of passing a null field id into the uploader.
         */
        $files = $this->getRequest()->getFiles()->toArray();
        if (empty($files)) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'error' => (string)__('No file was uploaded.'),
                'errorcode' => 0,
            ]);
        }

        $result = $this->fileProcessor->saveToTmp(key($files));
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
