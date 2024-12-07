<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DeliberationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('session', ChoiceType::class, [
                
            ])
            ->add('semestre')
            ->add('formation')
            ->add('niveau')
            ->add('filiere')
            ->add('specialite')
            ->add('classe')
            ->add('etudiants')
            ->add('ecsModules')
            ->add('noteMin')
            ->add('noteMax')
            ->add('newNote')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
