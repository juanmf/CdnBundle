<?php

namespace DocDigital\Bundle\CdnBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of UploadEvent
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class UploadEvent extends Event
{
    const RELATIVE_PATH_READY = 'dd_document.upload.relative_path_ready';
    
    /**
     * The File relative path that gets forwarded when this event gets dispatched.
     * 
     * @var string
     */
    private $fileRelativePath = '';
   
    /**
     * The File relative path might need to be modified after a parameter given in the 
     * Request object, by client code. If Request is present, handlers might make use 
     * of it. As CdnBundle is decoupled from DocumentBundle, in a CDN-only server context;
     * Request is the only way to pass a value that belongs to DocumentType metadata.
     * 
     * @var Request
     */
    private $request = null;
   
    /**
     * Initializes fileRelativePath
     */
    public function __construct($fileRelativePath, Request $request = null)
    {
        $this->fileRelativePath = $fileRelativePath;
        $this->request = $request;
    }
    
    /**
     * The File relative path that gets forwarded when this event gets dispatched.
     * Possibly modified.
     * 
     * @return string
     */
    public function getFileRelativePath()
    {
        return $this->fileRelativePath;
    }

    /**
     * The File relative path that gets forwarded when this event gets dispatched.
     * 
     * @param string $fileRelativePath
     */
    public function setFileRelativePath($fileRelativePath)
    {
        $this->fileRelativePath = $fileRelativePath;
    }


}
