<?php

namespace Genessis\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
// use Symfony\Component\Form\Extension\Core\Type\EntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TaskType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)

            ->add('user', EntityType::class, array(
                'class'=>'GenessisUserBundle:User',
                'query_builder'=>function(EntityRepository $er){
                    return $er->createQueryBuilder('u')
                        ->where('u.role = :only')
                        ->setParameter('only', 'ROLE_USER');
                },
                'choice_label'=> 'getFullName',
                'placeholder' => 'Choose an user'
            ))

            ->add('save', SubmitType::class, array('label'=>'Save task'))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Genessis\UserBundle\Entity\Task'
        ));
    }
}
