<?php

namespace App\Form;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\Etudiant;
use App\Entity\Departement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'placeholder' => 'Votre nom...'
                ]
            ])
            ->add('prenom', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Votre prenom..."
                ]
            ])
            ->add('dateDeNaissance', DateType::class, [
                'years' => range(1970, date('Y')-10)
            ])
            ->add('lieuDeNaissance', TextType::class, [
                'attr' => [
                    'placeholder' => "Né(e) à..."
                ],
                'required' => false,
            ])
            ->add('sexe', ChoiceType::class, [
                'choices' => ['Masculin'=>'M', 'Feminin'=>'F'],
                'placeholder' => "Sélectionner un élément dans la liste",
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('image', FileType::class, [
                'attr' => [
                    'placeholder' => 'Sélectionner une image',
                    'accept' => '.png, .jpg, .PNG, .JPG'
                ],
                'required' => false,
            ])
            ->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner un élément dans la liste',
                'required' => true,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner un élément dans la liste',
                'required' => true,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('departement', EntityType::class, [
                'class' => Departement::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner un élément dans la liste',
                'required' => true,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('telephone1', TextType::class, [
                'attr' => [
                    'placeholder' => "Votre numéro téléphone"
                ],
                'label' => "Numéro de téléphone 1",
                'required' => false,
            ])
            ->add('telephone2', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Votre numéro téléphone"
                ],
                'label' => "Numéro de téléphone 2"
            ])
            ->add('adresseEmail', EmailType::class, [
                'attr' => [
                    'placeholder' => "Votre adresse e-mail..."
                ],
                'required' => false,
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Votre adresse',
                'required' => false,
            ])
            ->add('nombreDEnfants', NumberType::class, [
                'attr' => [
                    'min' => 0,
                ],
                'label' => "Nombre d'enfants",
                'required' => false,
            ])
            ->add('situationMatrimoniale', ChoiceType::class, [
                'choices' => [
                    'Celibataire' => 'Celibataire',
                    'Marié' => 'Marié',
                    'Veuf/Veuve' => 'Veuf'
                ],
                'placeholder' => "Situation matrimoniale...",
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('civilite', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'Monsieur',
                    'Madame' => 'Madame',
                    'Mademoiselle' => 'Mademoiselle',
                ],
                'placeholder' => "Sélectionner un élément dans la liste",
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('diplomeAcademiqueMax', ChoiceType::class, [
                'choices' => [
                    'Baccalaureat' => 'Baccalaureat',
                    'Licence' => 'Licence',
                    'Master 2' => 'Master 2',
                    'Autre' => '0'
                ],
                'label' => "Diplôme académique le plus élévé",
                'placeholder' => 'Sélectionner un élément dans la liste',
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('autreDiplomeMax', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => "Nom du diplôme..."
                ]
            ])
            ->add('anneeObtentionDiplomeAcademiqueMax', IntegerType::class, [
                'label' => "Année d'obtention",
                'attr' => [
                    'placeholder' => "Saisissez l'année uniquement",
                    'min' => '1980',
                    'max' => date_format(new \DateTime(), 'Y'),
                ],
                'required' => false,
            ])
            ->add('diplomeDEntre', choiceType::class, [
                'choices' => [
                    'Baccalaureat' => 'Baccalaureat',
                    'BTS' => 'BTS',
                    'Licence' => 'Licence',
                    'Autre' => '0',
                ],
                'label' => "Diplôme d'entré",
                'placeholder' => 'Sélectionner un élément dans la liste',
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('autreDiplomeEntre', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => "Nom du diplôme..."
                ]
            ])
            ->add('anneeObtentionDiplomeEntre', IntegerType::class, [
                'label' => "Année d'obtention",
                'attr' => [
                    'placeholder' => "Saisissez l'année uniquement",
                    'min' => '1980',
                    'max' => date_format(new \DateTime(), 'Y'),
                ],
                'required' => false,
            ])
            ->add('autreFormation', TextareaType::class, [
                'label' => "Autres formations suivit",
                'required' => false,
                'attr' => [
                    'placeholder' => 'Pas obligatoire'
                ]
            ])
            ->add('skills', TextareaType::class, [
                'label' => "Centres d'interêt",
                'required' => false,
                'attr' => [
                    'placeholder' => 'Pas obligatoire'
                ]
            ])
            ->add('nomDuPere', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom de votre père'
                ]
            ])
            ->add('numeroDeTelephoneDuPere', TextType::class, [
                'required' => false,
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Numéro de téléphone de votre père'
                ]
            ])
            ->add('professionDuPere', TextType::class, [
                'required' => false,
                'label' => 'Profession',
                'attr' => [
                    'placeholder' => 'Profession de votre père'
                ]
            ])
            ->add('nomDeLaMere', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom de votre mère'
                ],
                'required' => false,
            ])
            ->add('numeroDeTelephoneDeLaMere', TextType::class, [
                'required' => false,
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Numéro de téléphone de votre mère'
                ],
                'required' => false,
            ])
            ->add('professionDeLaMere', TextType::class, [
                'label' => 'Profession',
                'attr' => [
                    'placeholder' => 'Profession de votre mère'
                ],
                'required' => false,
            ])
            ->add('adresseDesParents', TextType::class, [
                'attr' => [
                    'placeholder' => 'Adresse des parents'
                ],
                'required' => false,
            ])
            ->add('personneAContacterEnCasDeProbleme', TextType::class, [
                'label' => "Personne à contacter en cas de problème",
                'attr' => [
                    'placeholder' => "Nom de la personne (Tuteur par exemple)"
                ],
                'required' => false,
            ])
            ->add('numeroDUrgence', TextType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Numéro de téléphone du tuteur'
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Etudiant::class,
            'label' => false,
        ]);
    }
}
