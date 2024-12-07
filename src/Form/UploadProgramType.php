<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UploadProgramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fichier', FileType::class, [
                'attr' => [
                    'accept' => '.xlsx, .csv',
                    'placeholder' => "Cliquez pour sélectionner un fichier",
                ],
                'label' => "Sélectionner un fichier excel (extension .xlsx)",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
            'label' => false,
        ]);
    }
}
