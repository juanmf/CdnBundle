<?php

namespace DocDigital\Bundle\CdnBundle\Upload;

// Need this include here, as it fails if I add it in the Trait.
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Description of Document
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Document implements CdnFileDocumentInterface
{
    use CdnFileDocumentTrait;
    
    /**
     * The Client must tell us which class name to use, as here we don't have access
     * to DocumentType metadata.
     * @Assert\NotBlank(groups={"creation", "creationAjax"})
     * @var string
     */
    private $typeClassName = '';
}
