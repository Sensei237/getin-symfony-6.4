<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Filiere;
use App\Entity\ECModule;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Entity\MatiereASaisir;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class OperateurSaisieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner un utilisateur',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2',
                ],
                'label' => "Sélectionner un utilisateur",
                'required' => true,
            ])
            ->add('formation', EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner une formation',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2 formations',
                    'disabled' => false,
                ],
                'label' => "Sélectionner une formation",
                'required' => true,
            ])
            ->add('filiere', EntityType::class, [
                'class' => Filiere::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner une filiere',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2 filieres',
                    'disabled' => true,
                ],
                'label' => "Sélectionner une filiere",
                'required' => true,
            ])
            ->add('specialite', EntityType::class, [
                'class' => Specialite::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner une specialité',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2 specialites',
                    'disabled' => true,
                ],
                'label' => "Sélectionner une specialité",
                'required' => false,
            ])
            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une classe',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2 classes',
                    'disabled' => true,
                ],
                'label' => "Sélectionner une classe",
                'required' => false
            ])
            ->add('ecsModule', EntityType::class, [
                'class' => ECModule::class,
                'choice_label' => 'nom',
                'placeholder' => 'Matières à saisir',
                'multiple' => true,
                'attr' => [
                    'class' => 'select2 matieresASaisirs',
                ],
                'label' => 'Sélectionner les matières à saisir',
                'required' => false,
            ])
            ->add('examen', EntityType::class, [
                'class' => Examen::class,
                'choice_label' => 'intitule',
                'placeholder' => 'Sélectionner un examen',
                'multiple' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'years' => range(date('Y'), date('Y')+1),
            ])
            ->add('isSaisiAnonym', CheckboxType::class, [
                'label' => "Cocher cette case si la saisie est anonyme",
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatiereASaisir::class,
            'label' => 'Ajouter un opérateur de saisie des notes'
        ]);
    }
}
