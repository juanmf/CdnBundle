<?php

namespace DocDigital\Bundle\CdnBundle\Pdf;

use DocDigital\Bundle\CdnBundle\Upload\CdnFileDocumentInterface;
use DocDigital\Bundle\PdfBundle\Form\Type\PdfInsertType;
use DocDigital\Bundle\ToolsBundle\Util\Logger\Logger;
use DocDigital\Lib\PdfManager\PdfManager;
use Symfony\Bridge\Monolog\Logger as SfLogger;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Handles uploaded PDF manipulation, according to {@link PdfInsertType}
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class PdfHandler
{
    /**
     * Kernel
     * 
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;
    
    /**
     * Pdf Manager interface
     * 
     * @var DocDigital\Lib\PdfManager\PdfManager
     */
    private $pdfManager;
    
    /**
     * SfLogger
     * @var SfLogger 
     */
    private $sfLogger;
    
    public function __construct(KernelInterface $kernel, PdfManager $pdfManager, SfLogger $sfLogger)
    {
        $this->kernel = $kernel;
        $this->pdfManager = $pdfManager;
        $this->sfLogger = $sfLogger;
    }
    
    /**
     * Tests for insertOptions form values and handles PDF manipulation.
     * 
     * 
     * @param CdnFileDocumentInterface $document
     * @param array                    $formRawData
     * 
     * @return null|int null if nothing to do. The resulting number of pages if 
     * PDF was changed.
     * 
     * @see DocDigital\Bundle\PdfBundle\Form\Type\PdfInsertType
     */
    public function handlePdfChanges(CdnFileDocumentInterface $document, array $formRawData)
    {
        if (! isset($formRawData['insertOptions'])) {
            return;
        }
        $oldPath = $document->getUploadRootDir($this->kernel) . $document->getOldPath();
        $newTmpPath = $document->getFile()->getRealPath();
        if (! ($oldMissing = file_exists($oldPath) && file_exists($newTmpPath))) {
            $msg = $oldMissing ? $oldPath : $document->getFile()->getBasename() . " ($newTmpPath)";
            throw new ResourceNotFoundException($msg . 'Not Found.');
        }
        
        switch ($formRawData['insertOptions']['insertPosition']) {
            case PdfInsertType::POSITION_BEGINNING:
                $newPdf = $this->pdfManager->insert($oldPath, $newTmpPath);
                break;
            case PdfInsertType::POSITION_END: 
                $newPdf = $this->pdfManager->append($oldPath, $newTmpPath);
                break;
            case PdfInsertType::POSITION_PAGE: 
                $newPdf = $this->pdfManager->insert(
                        $oldPath, $newTmpPath, $formRawData['insertOptions']['pageNumber']
                    );
                break;
            case PdfInsertType::POSITION_REPLACE: 
                return;
                break;
        }
        $pageCount = $newPdf->getPageCount();
        $newPdf->renderFile($mergedPdfPath = "$newTmpPath.merged");
        $document->setFile(new File($mergedPdfPath, true));
        return $pageCount;
    }
    
    /**
     * Tests for insertOptions form values and handles PDF manipulation.
     * 
     * @param array $formRawData
     * 
     * @return null|int null if nothing to do. The resulting number of pages if 
     * PDF was changed.
     */
    public function checkForPdfChanges(CdnFileDocumentInterface $document, array $formRawData)
    {
        if (! (isset($formRawData['insertOptions']) && ($document->getFile() instanceof File))) {
            return;
        }
        try {
            return $this->handlePdfChanges($document, $formRawData);
        } catch (Exception $exc) {
            if (class_exists(Logger)) {
                Logger::debug( 
                    __METHOD__ . ' Error: ' . $exc->getMessage() . $exc->getTraceAsString(), 
                    $this->sfLogger , Logger::APP_LOG_ERROR
                );
            }
        }
    }
}
