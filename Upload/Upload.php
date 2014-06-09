<?php

namespace DocDigital\Bundle\CdnBundle\Upload;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use DocDigital\Bundle\CdnBundle\Event\UploadEvent;
use DocDigital\Bundle\CdnBundle\Upload\CdnFileDocumentInterface;
/**
 * Contains basic File saving, deletion and Path creation for Document's PDF assets.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Upload
{
    /**
     * The EventDispatcher service.
     * 
     * @var EventDispatcher 
     */
    private $dispatcher;
    
    /**
     * Initialized the Dispatcher instance reference. 
     * 
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * Handles old file removal and new File relocation.
     * 
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     * 
     * @return void
     */
    public function upload(Kernel $kernel, CdnFileDocumentInterface $document)
    {
        if (null === $document->getFile()) {
            return;
        }
        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $absoluteFile = $document->getAbsolutePath($kernel);
        $dir = dirname($absoluteFile);
        $filename = basename($absoluteFile);
        $document->getFile()->move($dir, $filename);
        
        // check if we have an old file
        $oldPath = $document->getOldPath();
        if (! empty($oldPath) 
            && file_exists($oldFile = $document->getUploadRootDir($kernel) . $oldPath)
        ) {
            // delete the old image
            unlink($oldFile);
            // clear the temp image path
            $document->setOldPath(null);
        }
        $document->setFile(null);
    }
    
    /**
     * Creates the relative path e.g. Contract/2014-01/2014-01-16-18-56-14-200805029.pdf
     * for the uploaded file, after which it dispatches a FilterEvent to allow for 
     * extension.
     * 
     * @param Document $document  The Document.
     * @param Document $request   Might be useful for event handlers. {@link UploadEvent::RELATIVE_PATH_READY}
     * @param Document $extension If not available or you want to override the guessedExtension
     * send the extenssion here, WITH DOT. e.g. ".pdf"
     * 
     * @return string The File relative path, possibli modified by the filter.
     */
    public function createFileRelativePath(
        CdnFileDocumentInterface $document, Request $request = null, $extension = null
    ) {
        // TODO: figureout why $now->format('u') returns 000000
        $now = new \DateTime();
        $ext = (null !== $extension) ? $extension : '.' . $document->getFile()->guessExtension();
        $filename = $now->format('Y-m-d-H-i-s-u') . $ext;
        
        // Contract/2014-01/2014-01-16-18-56-14-200805029.pdf
        $fileRelativePath = sprintf(
                "%s/%s/%s", 
                $document->getTypeClassName(), 
                $this->getYearMonth($filename),
                $filename
            );

        $this->dispatcher->dispatch(
            UploadEvent::RELATIVE_PATH_READY,
            $event = new UploadEvent($fileRelativePath, $request)
        );
        
        return $event->getFileRelativePath();
    }
    
    /**
     * The fileName should have a dateTime string with this format: 
     * Year(YYYY)-month(mm)-day(dd)-Hour(HH)-Minute(MM)-Second(SS)-NanoSecond(nnnnnnnnn).pdf
     * example: 2014-01-16-18-56-14-200805029.pdf
     * 
     * This method should cut and return Year(YYYY)-month(mm)
     * example: 2014-01
     * 
     * @param string $fileName
     */
    private function getYearMonth($fileName)
    {
        return substr($fileName, 0, 7);
    }
    
    /**
     * Handles file deletion after document deletion.
     * 
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     * 
     * @return void
     */
    public function remove(Kernel $kernel, CdnFileDocumentInterface $document) 
    {
        if ($file = $document->getAbsolutePath($kernel)) {
            unlink($file);
        }
    }
}
