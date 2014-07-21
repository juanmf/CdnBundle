<?php

namespace DocDigital\Bundle\CdnBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use DocDigital\Bundle\CdnBundle\Upload\Document;
use DocDigital\Bundle\CdnBundle\Form\DocumentType;

/**
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     * TODO: Replace this with a random long string that holds in session 20 seconds
     * the necessary time to try to create the record in DocDigital server, fail, and 
     * delete the pdf here. 
     */
    CONST ALLOW_DELETE_TOKEN = '1';
  
    /**
     * @Route("/cdn/save/{typeClassName}", name="api_save_document", defaults={"_format"="json"})
     * @Method({"POST"})
     */
    public function saveAction(Request $request, $typeClassName)
    {
        $form = $this->createForm(new DocumentType(), $document = new Document());
        $form->handleRequest($request);
        if (! $form->isValid()) {
            return new JsonResponse (
                array(
                        'status' => 400, // Response::HTTP_BAD_REQUEST  since Sf2.4
                        'path'   => null, 
                        'error'  => $form->getErrorsAsString(),
                )
            );
        }
        $upload = $this->get('dd_cdn.upload');
        /* @var $upload \DocDigital\Bundle\CdnBundle\Upload\Upload */
        $document = $form->getData();
        $document->setPdfPath($upload->createFileRelativePath($document, $request));
        $pdfPages = $this->get('dd_cdn.preview_maker')->getPdfPageCount(
                $document->getFile()->getPathname()
            );
        $upload->upload($this->get('kernel'), $document);
                
        return new JsonResponse (
            array(
                'status'   => 200, // Response::HTTP_BAD_REQUEST  since Sf2.4
                'path'     => $document->getPdfPath(), 
                'pdfPages' => $pdfPages, 
                'error'    => null,
                'token'    => self::ALLOW_DELETE_TOKEN,
            )
        );
    }
    
    /**
     * For a given media path (assocaited with a Document's pdfPath) and pdf page number, 
     * this action returns and caches, the dinamically generated png of that pdf's page. 
     * Useful for showing preview thumbs or pics.
     * 
     * Media is located at location designated by %dd_cdn.upload.dir%<Document's pdfPath>
     * If in a previous request a path list is made, with proper paths, its not mandatory 
     * to re-access Documents in DB. as assets are in filesystem. This fn should be 
     * faster than {@link self::getPreviewAction()}
     * 
     * @Route("/document/getpreviewpath/{path}/{page}", name="document_previewpath_page")
     * @Method("GET")
     * @param string $path    The documents's pdf relative path.
     * @param string $page    The PDF's page number
     * 
     * @return Response The 'image/png' or 404 if requested page or pdf doesn't exist.
     */
    public function getPreviewWithPathAction($path, $page)
    {
        $path = self::getAbsoluteMediaPath($this->get('kernel'), strtr($path, '~', '/'));
        return $this->getPdfPagePreview($path, $page);
    }
    
    /**
     * Given an absolute path, returns a Response object with the page preview binary 
     * stream.
     * 
     * @param type $path
     * @param type $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws type
     */
    private function getPdfPagePreview($path, $page)
    {
        $preview = $this->get('dd_cdn.preview_maker');
        /* @var $preview \DocDigital\Bundle\DocumentBundle\ImageProcessor\PreviewMaker */
        $pages = $preview->getPdfPreview($path, 1, $page);
        if (empty($pages)) {
            throw $this->createNotFoundException('The PDF page number does not exist.');
        }
        $response = new Response($pages[0]);
        $this->configCachedResponse($response, 31536000, 'image/png');
        return $response;
    }
      
    /**
     * Returns the absolute path of the Document PDF. Given Document's pdfPath
     * attribute's value.
     * 
     * Media is located at a location designated by %dd_cdn.upload.dir%
     * 
     * @param string $relativePath The Document's pdfPath
     * 
     * @return string The absolute pdf path.
     * @see self::_getDocPdfPath()
     */
    public static function getAbsoluteMediaPath(Kernel $kernel, $relativePath)
    {
        if (empty($relativePath)) {
            return null;
        }
        return $kernel->getContainer()->getParameter('dd_cdn.upload.dir') . $relativePath;
    }
    
    /**
     * Makes Response cached for $seconds seconds and sets its content type.
     * 
     * @param Response $response The Response object
     * @param int      $seconds  The amount of seconds to hold the response in cache.
     * @param string   $cType    The content Type ['image/png', 'text/html', ..]
     * 
     * @return void
     */
    private function configCachedResponse(Response $response, $seconds, $cType)
    {
        $response->headers->set('Content-Type', $cType);
        $response->setPublic();
        // set the private or shared max age to 1 year, in seconds
        $response->setMaxAge($seconds);
        $response->setSharedMaxAge($seconds);
    }
    
    /**
     * Download a Document's PDF.
     * Token is a random string just sent to validae deletion upon DocDigital Server 
     * request failure.
     * 
     * @Route("/delete/{relativePath}/{token}", name="api_document_delete")
     * @Method("DELETE")
     */
    public function documentDeleteAction($relativePath, $token)
    {
        $relativePath = strtr($relativePath, '~', '/');
        $return = array('status' => 200);
        if (self::ALLOW_DELETE_TOKEN !== $token) {
            $return['error'] = 'Invalid Token';
            $return['status'] = '403';
            $logger = $this->get('logger');
            $logger->error("Invalid Token. $relativePath could not be deleted. Ensure we don't have orphan PDFs arround");
            return new JsonResponse($return) ; 
        }
        $document = new Document();
        $document->setPdfPath($relativePath);
        $kernel = $this->get('kernel');
        $this->get('dd_cdn.upload')->remove($kernel,  $document);
        $return['msg'] = "$relativePath deleted successfully";
        return new JsonResponse($return) ; 
    }
    
    /**
     * Download a Document's PDF.
     *
     * @Route("/download/{relativePath}/{contentDisposition}", name="document_download",
     *     defaults={"contentDisposition": "attachment"})
     * @Method("GET")
     */
    public function documentDownloadAction($relativePath, $contentDisposition)
    {
        $this->checkContentDisposition($contentDisposition);
        $relativePath = strtr($relativePath, '~', '/');
        $pdfStream = file_get_contents(self::getAbsoluteMediaPath($this->get('kernel'), $relativePath));
        $response = new Response($pdfStream);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }
    
    /**
     * Download a Document's PDF.
     *
     * @Route("/downloadfractions/{relativePath}/{ranges}/{contentDisposition}", name="document_download_fractions",
     *     defaults={"contentDisposition": "attachment", "ranges": "[]"})
     * @Method("GET")
     *
     * 
     * @param string $relativePath       The relative path to the PDF. with ~ instead 
     * of / as Dir Separator
     * @param string $ranges             The pages as a json encoded String. Suprted 
     * values examples: <pre>
     *    "[1, 4, 5]"  : pages 1; 4 and 5
     *    "['1-5']"    : pages 1 to 5
     *    "[1, '4-6']" : pages 1 and 4 to 6
     * </pre>
     * Optional.
     * @param string $contentDisposition The value for content Disposition header 
     * attribute optional, defaults to attachment. you could put 'inline'.
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function documentDownloadFractionsAction($relativePath, $ranges, $contentDisposition)
    {
        $this->checkContentDisposition($contentDisposition);
        
        $relativePath = strtr($relativePath, '~', '/');
        $pdf = self::getAbsoluteMediaPath($this->get('kernel'), $relativePath);
        $pdfStream = $this->handlePageRanges($pdf, json_decode($ranges));
        
        $response = new Response($pdfStream);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }
    
    /**
     * Handle PDF page Ranges requested for download.
     * 
     * @param type $param
     */
    private function handlePageRanges($pdfPath, array $ranges)
    {
        /* @var $pdfManager \DocDigital\Bundle\PdfBundle\Pdf\Fpdi\FpdiPdfManager */
        if (empty($ranges)) {
            return file_get_contents($pdfPath);
        }

        $pdfManager = $this->get('dd_pdf.pdf_manager');
        $supPdfs = array();
        foreach ($ranges as $range) {
            list($offset, $length) = explode('-', $range) + array(null, null);
            $length = (null === $length) ? 1 : $length - $offset + 1;
            $supPdfs[] = $pdfManager->getRange($pdfPath, $offset, $length);
        }
        return $pdfManager->merge($supPdfs)->render();
    }
    
    /**
     * Security issues might involve not checking allowed values here.
     * 
     * @param type $contentDisposition
     */
    private function checkContentDisposition($contentDisposition)
    {
        if (! in_array($contentDisposition, array('attachment', 'inline'))) {
            throw new \InvalidArgumentException(
                "Not Supported $contentDisposition. Supported $contentDisposition are ['attachment', 'inline']"
            );
        }
    }
}
