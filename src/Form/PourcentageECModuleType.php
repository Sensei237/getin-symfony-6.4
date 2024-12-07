<?php

namespace App\Form;

use App\Entity\PourcentageECModule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PourcentageECModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pourcentageCC', NumberType::class, [
                'label' => "Pourcentage pour le CC",
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('pourcentageTPE', NumberType::class, [
                'label' => "Pourcentage pour le TPE",
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('pourcentageTP', NumberType::class, [
                'label' => "Pourcentage pour le TP",
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('pourcentageExam', NumberType::class, [
                'label' => "Pourcentage pour l'examen",
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PourcentageECModule::class,
        ]);
    }
}
