<?php

namespace App\Form;

use App\Entity\ECModule;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ECModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('credit', FloatType::class, [
                'attr' => ['min'=>'1', 'step'=>'0.25']
            ])
            ->add('semestre')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ECModule::class,
        ]);
    }
}
