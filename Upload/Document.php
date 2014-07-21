<?php

namespace DocDigital\Bundle\CdnBundle\Upload;

// Need this include here, as it fails if I add it in the Trait.
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Description of Document
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Document implements CdnFileDocumentInterface
{
    use CdnFileDocumentTrait;
}
