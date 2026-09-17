<?php

/**
 * System config image field backend model
 */

namespace WeltPixel\GoogleCards\Model\Config\Backend;

/**
 * Class Image
 *
 * @package WeltPixel\GoogleCards\Model\Config\Backend
 */
class Image extends \Magento\Config\Model\Config\Backend\Image
{
    /**
     * The tail part of directory path for uploading
     *
     */
    const UPLOAD_DIR = 'weltpixel/google_logo'; // Folder save image

    /**
     * How many path segments a stored value may legitimately have.
     *
     * Core prepends the scope to the file name before storing it, so a value looks like
     * "default/logo.png" or "stores/1/logo.png". A value written before this class forced scope
     * info on is a bare file name. Anything longer is not something this module wrote.
     */
    const OLD_VALUE_MAX_SEGMENTS = 3;

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'png', 'gif'];
    }

    /**
     * Save uploaded file before saving config value
     *
     * Save changes and delete file if "delete" option passed
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();
        $deleteFlag = is_array($value) && !empty($value['delete']);
        $oldFile = $this->_getOldValueRelativePath();

        if (empty($file)) {
            if ($oldFile !== '' && $deleteFlag) {
                $this->_mediaDirectory->delete($oldFile);
            }
            return parent::beforeSave();
        }

        $fileTmpName = $file['tmp_name'];

        if ($oldFile !== '' && ($fileTmpName || $deleteFlag)) {
            $this->_mediaDirectory->delete($oldFile);
        }
        return parent::beforeSave();
    }

    /**
     * Path of the previously uploaded file, relative to the media directory.
     *
     * The old value is read back from configuration rather than from the request, and it is used to
     * delete a file, so it is checked before it is trusted. A plain basename would be wrong here:
     * core stores the scope together with the file name, so the value legitimately contains
     * slashes and flattening it would leave every replaced file on disk forever. Instead each
     * segment is checked, and a value that traverses, is absolute, is empty or carries a scheme is
     * refused outright rather than repaired, so no delete happens at all in that case.
     *
     * The delete therefore cannot leave this module's own upload directory.
     *
     * @return string empty when nothing should be deleted
     */
    protected function _getOldValueRelativePath()
    {
        $oldValue = $this->getOldValue();
        if (!is_string($oldValue) || trim($oldValue) === '' || strpos($oldValue, "\0") !== false) {
            return '';
        }

        $segments = explode('/', str_replace('\\', '/', trim($oldValue)));
        if (count($segments) > self::OLD_VALUE_MAX_SEGMENTS) {
            return '';
        }

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return '';
            }
        }

        return self::UPLOAD_DIR . '/' . implode('/', $segments);
    }
}
