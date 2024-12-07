<?php

namespace App\Form;

use App\Entity\EtudiantInscris;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EtudiantInscrisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('etudiant', EtudiantType::class)
            ->add('isRedoublant', CheckboxType::class, [
                'required' => false,
                'label' => "Je redouble cette classe",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EtudiantInscris::class,
            'label' => false,
        ]);
    }
}
