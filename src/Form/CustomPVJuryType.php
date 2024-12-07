<?php

namespace App\Form;

use App\Entity\Classe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomPVJuryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ec', ChoiceType::class, [
                'choices' => [
                    'Année Académique' => 'ANNEE',
                    'Note de Travaux Pratiques' => 'TP',
                    'Note de Travail Personnel de l\'Etudiant' => 'TPE',
                    'Note de Controle Continu' => 'CC',
                    'Note d\'Examen' => 'EX',
                    'Moyenne'  => 'MOY',
                    'Note sur 4' => 'NOTE',
                    'Crédit' => 'CR',
                    'Grade' => 'GRA',
                    'Décision' => 'DEC',
                    'Session de validation' => 'SV',
                ],
                'label' => 'Sélectionner les champs pour chaque EC',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('module', ChoiceType::class, [
                'choices' => [
                    'Moyenne'  => 'MOY',
                    'Note sur 4' => 'NOTE',
                    'Crédit' => 'CA',
                    'Grade' => 'GRA',
                    'Décision' => 'DEC',
                ],
                'label' => 'Sélectionner les champs pour chaque synthèse du module (Si rien n\'est coché, les Synthèses modulaire ne figureront pas dans le PV)',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('synthese', ChoiceType::class, [
                'label' => 'Sélectionner les champs pour chaque synthèse finale',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Moyenne'  => 'MOY',
                    'Note sur 4' => 'NOTE',
                    'Crédit' => 'CA',
                    'Grade' => 'GRA',
                    'Décision' => 'DEC',
                ],
            ])
            ->add('semestre', ChoiceType::class, [
                'label' => 'Sélectionner le semestre',
                'required' => false,
                'choices' => [
                    'Semestre 1' => '1',
                    'Semestre 2' => '2',
                ],
                'placeholder' => 'Tous les semestre',
            ])
            ->add('classes', EntityType::class, [
                'label' => 'Sélectionner les classes concernées',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'nom',
                'class' => Classe::class,
            ])
            ->add('disposition', ChoiceType::class, [
                'label' => 'Choisissez la disposition des éléments à afficher',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'Horizontalement' => 'H',
                    'Verticalement' => 'V'
                ],
                'placeholder' => 'Sélectionner une disposition'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
