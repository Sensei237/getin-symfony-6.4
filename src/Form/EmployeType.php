<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => "Nom"
            ])
            ->add('prenom', TextType::class, [
                'required' => false,
                'label' => 'Prenom'
            ])
            ->add('dateDeNaissance', BirthdayType::class, [
                'label' => 'Date de naissance',
                'years' => range(1950, date('Y')-10)
            ])
            ->add('lieuDeNaissance', TextType::class, [
                'label' => 'Lieu de naissance'
            ])
            ->add('sexe', ChoiceType::class, [
                'choices' => [
                    'Masculin' => 'M',
                    'Feminin' => 'F'
                ],
                'placeholder' => 'Sélectionner le sexe',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'attr' => [
                    'accept' => '.jpg, .png, .JPG, .JPEG, .PNG, .jpeg'
                ],
                'label' => 'Photo'
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Télephone 1',
            ])
            ->add('telephone2', TextType::class, [
                'label' => 'Télephone 2',
                'required' => false,
            ])
            ->add('adresseEmail', EmailType::class, [
                'label' => 'Adresse e-mail',
            ])
            ->add('grade', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'Mr',
                    'Docteur' => 'Dr',
                    'Professeur' => 'Pr',
                ],
                'placeholder' => 'Sélectionner un titre',
                'label' => 'Titre',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('situationMatrimoniale', ChoiceType::class, [
                'choices' => [
                    'Célibatire' => 'Célibatire',
                    'Marié(e)' => 'Marié',
                    'Veuf (Veuve)' => 'Veuf',
                ],
                'placeholder' => 'Sélectionner un élément',
                'label' => 'Titre',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('nombreDenfants', NumberType::class, [
                'label' => "Nombre d'enfant"
            ])
            ->add('nomConjoint', TextType::class, [
                'label' => 'Nom du conjoint (de la conjointe)',
                'required' => false,
            ])
            ->add('telephoneConjoint', NumberType::class, [
                'label' => 'Numéro de télephone du conjoint (de la conjointe)',
                'required' => false
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner le service',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
            'label' => false,
        ]);
    }
}
