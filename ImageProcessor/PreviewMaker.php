<?php

namespace DocDigital\Bundle\CdnBundle\ImageProcessor;

use \Symfony\Component\Intl\Exception\NotImplementedException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Converts some pdfs pages, to jpg for carrousel or other uses.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class PreviewMaker
{
    /**
     * Caches the amount of pages each requested (entire) PDF file has. Preventing opening 
     * multiple times.
     * 
     * @var int[] 
     */
    private $pdfPages = array();
    
    /**
     * Returns an array with imageBlobs, one per PDF page.
     * 
     * @param string $path        The PDF file path to read.
     * @param int    $numberPages The number of pages, from $offset, of the PDF that 
     * need to render as $format.
     * @param int    $offset      The startting page (0 based) from wich to read.
     * @param string $format      The output format ['jpeg', 'png', ..]. defaults to 'png'
     * 
     * @return array With the converted pages as blob.
     * 
     * @see \Imagick::getimageblob()
     * @see \Imagick::setImageFormat()
     */
    public function getPdfPreview($path,  $numberPages = 3, $offset = 0, $format = 'png')
    {
        $transform = function (\Imagick $im) use ($format) {
            $im->setImageColorspace(255); 
            $im->setImageFormat($format);
            return $im->getimageblob();
        };
            
        $pages = $this->readPdf($path, $transform, $numberPages, $offset);
        return $pages;
    }
    
    /**
     * Opens path and read requested pages passing them to $transform.
     * 
     * @param string   $path        The PDF file path to read.
     * @param \Closure $transform   A Closure that handles transformation of each page.
     * @param int      $numberPages The number of pages, from $offset, of the PDF that 
     * need to render as $format.
     * @param int      $offset      The startting page (0 based) from wich to read.
     * 
     * @return array Of transformed PDF pages.
     */
    protected function readPdf($path, \Closure $transform, $numberPages = 3, $offset = 0)
    {
        $pages = array();
        $im = new \Imagick();
        // TODO: THIS resolution is not enough for som cases and too mucho for some others
        $im->setresolution(300, 300);
        for ($i = $offset; $i < ($offset + $numberPages); $i ++) {
            try {
                if (! $im->readimage(sprintf("%s[%s]", $path, $i))) {
                    continue;
                }
            } catch (\Exception $exc) {
                continue;
            }
            $pages[] = $transform($im);
        }
        return $pages;
    }

    /**
     * Proxy for {@link self::getPdfPreview()} that also writes returned image 
     * Blobgs to disk and return paths.
     * 
     * @param string $path        The PDF file path to read.
     * @param int    $numberPages The number of pages, from $offset, of the PDF that 
     * need to render as $format.
     * @param int    $offset      The startting page (0 based) from wich to read.
     * @param string $format      The output format ['jpeg', 'png', ..]. defaults to 'png'
     * 
     * @return string[] with the generates file paths.
     */
    public function getPdfPreviewPaths($path,  $numberPages = 3, $offset = 0, $format = 'png')
    {
        $blobs = $this->getPdfPreview($path, $numberPages, $offset, $format);
        throw new NotImplementedException(
            'We are caching, not writing to disk so far..'
        );
    }
    
    /**
     * Returns the page count of the PDF at the given path.
     * 
     * @param string $path The PDF absolute path.
     * 
     * @return int The page count
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @see self::pdfPages
     */
    public function getPdfPageCount($path)
    {
        if (isset($this->pdfPages[$path])) {
            return $this->pdfPages[$path];
        }
        
        $im = new \Imagick();
        $im->setresolution(3, 3);
        if (! $im->readimage($path)) {
            throw new NotFoundHttpException('PDF does not exist.');
        }
        $this->pdfPages[$path] = $im->getNumberImages();
        return $this->pdfPages[$path];
    }
}
