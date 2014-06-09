<?php

namespace DocDigital\Bundle\CdnBundle\Upload;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of CdnFileDocumentStandardMethods
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
trait CdnFileDocumentTrait
{
    /**
     * File max size is 20MB
     * 
     * TODO: @ Assert\File IS NOT WORKING.
     *
     * @Assert\Valid()
     * @Assert\File(maxSize="20M", groups={"creation"}, mimeTypes={"application/pdf", "application/x-pdf"})
     * @Assert\NotBlank(groups={"creation"})
     * @var UploadedFile 
     */
    private $file = null;
    
    /**
     * Temporarily holds old file name when overriding preexisting.
     * 
     * @var string
     */
    private $oldPath;
    
    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Set file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }
    
    /**
     * Get getTypeClassName.
     *
     * @return UploadedFile
     */
    public function getTypeClassName()
    {
        return $this->typeClassName;
    }
    
    /**
     * Gets the old relative path to delete after the update.
     * 
     * @param string $oldPath The previous linked asset relative path
     */
    public function getOldPath()
    {
        return $this->oldPath;
    }
    
    /**
     * Store the old relative path to delete after the update, or set it to null after deletion.
     * 
     * @param string $oldPath The previous linked asset relative path
     */
    public function setOldPath($oldPath)
    {
        $this->oldPath = $oldPath;
    }
    
    /**
     * Returns AbsolutePath to media (PDF).
     * 
     * @return string
     */
    public function getAbsolutePath(KernelInterface $kernel)
    {
        // TODO: $this->getPdfPath(); not defined
        return  $this->getUploadRootDir($kernel) . $this->getPdfPath();
    }

    /**
     * Retrieves the path to the Upload Root Directory
     * 
     * @param KernelInterface $kernel
     * 
     * @return string The path to the Upload Root Directory
     */
    public function getUploadRootDir(KernelInterface $kernel)
    {
        return $kernel->getContainer()->getParameter('dd_cdn.upload.dir');
    }
    
    /**
     * Returns the relative pdf path
     * 
     * @return string
     * @see self::getAbsolutePath()
     */
    public function getPdfPath()
    {
        return $this->pdfPath;
    }

    /**
     * Sets the relative pdf path
     * 
     * @param string $pdfPath
     */
    public function setPdfPath($pdfPath)
    {
        $this->pdfPath = $pdfPath;
    }
}
