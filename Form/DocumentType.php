<?php

namespace DocDigital\Bundle\CdnBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * This is an incomplete Document Form, used to handle only File Upload related 
 * stuff
 * 
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class DocumentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', 'file')
            ->add('typeClassName')
            ->add('pdfPath', 'hidden', array('required' => false))
            ->add('insertOptions', 'docdigital_pdfinsert', array('mapped' => false, 'required' => false));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'        => 'DocDigital\Bundle\CdnBundle\Upload\Document',
            'csrf_protection'   => false,
            'validation_groups' => array('creation'),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'docdigital_bundle_cdnbundle_document';
    }
}