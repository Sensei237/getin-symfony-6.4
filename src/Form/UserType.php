<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Employe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Sélectionner un personnel',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'attr' => [
                    'placeholder' => "username"
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => "Mot de passe",
                'attr' => [
                    'placeholder' => "password"
                ],
            ])
            ->add('droits', ChoiceType::class, [
                'choices' => [
                    'SUPER ADMINISTRATEUR' => 'ROLE_SUPER_USER',
                    'PEUT AJOUTER UN ETUDIANT' => 'ROLE_STUDENT_MANAGER',
                    'PEUT CONFIGURER LE LOGICIEL' => 'ROLE_CONFIG_MANAGER', 
                    'PEUT GERER LES CONTRATS ACADEMIQUES' => 'ROLE_CONTRATS_MANAGER', 
                    'PEUT CREER ' => 'ROLE_CREATION_MANAGER', 
                    'PEUT GERER LE PERSONNEL' => 'ROLE_EMPLOYE_MANAGER', 
                    'PEUT GERER LES UTILISATEURS' => 'ROLE_USER_MANAGER', 
                    'PEUT GERER LES PV' => 'ROLE_PV_MANAGER', 
                    'PEUT GERER LES PROGRAMMES ACADEMIQUES' => 'ROLE_PROGRAMACADEMIQUE_MANAGER', 
                    'PEUT GERER LES NOTES' => 'ROLE_NOTE_MANAGER',
                    'PEUT SAISIR LES NOTES' => 'ROLE_SAISIE_NOTES'
                ],
                'attr' => [
                    'class' => 'select2',
                ],
                'multiple' => true,
                'label' => "Roles",
                'placeholder' => "Sélectionner les roles correspondant",
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'label' => 'Ajouter un nouvel utilisateur',
        ]);
    }
}
