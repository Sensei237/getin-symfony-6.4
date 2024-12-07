<?php

namespace App\Form;

use App\Entity\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomEcole', TextType::class, [
                'label'=>"Nom de l'ecole en français",
                
            ])
            ->add('nomEcoleEn', TextType::class, [
                'label'=>"Nom de l'ecole en anglais",

            ])
            ->add('initiale', TextType::class, [
                'label'=>"Initiale",
                
            ])
            ->add('slogan')
            ->add('logo1', FileType::class, [
                'label' => "Logo de l'ecole",
                'required' => false,
            ])
            ->add('logo2', FileType::class, [
                'label' => "Logo de l'université",
                'required' => false,
            ])
            ->add('ville')
            ->add('email', EmailType::class)
            ->add('telephone')
            ->add('adresse')
            ->add('boitePostale')
            ->add('notePourValiderUneMatiere')
            ->add('isValidationModulaire', CheckboxType::class, [
                'label' => 'proceder à la validation par module',
                'required' => false,
            ])
            ->add('notePourValiderUnModule')
            ->add('noteEliminatoire')
            ->add('isRattrapageSurToutesLesMatieres', CheckboxType::class, [
                'label' => "Autoriser les étudiants à ameliorer leurs notes",
                'required' => false,
            ])
            ->add('isSREcraseSN', CheckboxType::class, [
                'label' => 'Session de rattrappage écrase la session normale'
            ])
            ->add('pourcentageECForADC', NumberType::class, [
                'label' => "Pourcentage des ecs qu'il faut valider pour etre admis en classe supérieure"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'label' => false
        ]);
    }
}
