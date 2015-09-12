<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Form
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Gibilogic\CrudBundle\Form\DataTransformer\EntityToIdTransformer;

/**
 * EntityHiddenType class.
 *
 * @see \Symfony\Component\Form\AbstractType
 */
class EntityHiddenType extends AbstractType
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * Constructor.
     * 
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new EntityToIdTransformer($this->objectManager, $options['class']);
        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array('class'))
            ->setDefaults(array('invalid_message' => "Missing mandatory option 'class' for the 'entity_hidden' type"))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'entity_hidden';
    }
}
