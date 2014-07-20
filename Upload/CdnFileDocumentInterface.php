<?php

namespace DocDigital\Bundle\CdnBundle\Upload;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This interface has the contract needed for {@link DocDigital\Bundle\CdnBundle\Upload\Upload}
 * to get the file handling related info. 
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
interface CdnFileDocumentInterface
{
    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile();
    
    /**
     * Set file.
     *
     * @return UploadedFile
     */
    public function setFile(UploadedFile $file = null);
    
    /**
     * Gets the old relative path to delete after the update.
     * 
     * @param string $oldPath The previous linked asset relative path
     */
    public function getOldPath();
    
    /**
     * Store the old relative path to delete after the update, or set it to null after deletion.
     * 
     * @param string $oldPath The previous linked asset relative path
     */
    public function setOldPath($oldPath);

    /**
     * Returns AbsolutePath to media (PDF).
     * 
     * @return string
     */
    public function getAbsolutePath(KernelInterface $kernel);
    
    /**
     * Retrieves the path to the Upload Root Directory
     * 
     * @param KernelInterface $kernel
     * 
     * @return string The path to the Upload Root Directory
     */
    public function getUploadRootDir(KernelInterface $kernel);
    
    /**
     * Returns the relative pdf path
     * 
     * @return string
     * @see self::getAbsolutePath()
     */
    public function getPdfPath();

    /**
     * Sets the relative pdf path
     * 
     * @param string $pdfPath
     */
    public function setPdfPath($pdfPath);
    
    /**
     * Returns the short (no namespace) class name of the DocumentType related to this 
     * Document instance.
     */
    public function getTypeClassName();
    
    /**
     * Sets the short (no namespace) class name of the DocumentType related to this 
     * Document instance.
     * 
     * @param string $typeClassName
     */
    public function setTypeClassName($typeClassName);
}
