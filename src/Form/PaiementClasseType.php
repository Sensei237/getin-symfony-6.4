<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\PaiementClasse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PaiementClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('classes', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner les classes concernées',
                'multiple' => true,
                'attr' => [
                    'class' => 'select2 paiement_de_classe',
                ]
            ])
            ->add('montant', NumberType::class, [
                'label' => "Montant total à payer"
            ])
            ->add('isObligatoire', CheckboxType::class, [
                'label' => "C'est un paiement obligatoire",
                'required' => false
            ])
            ->add('tranches', CollectionType::class, [
                'entry_type' => TrancheType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaiementClasse::class,
            'label' => false,
        ]);
    }
}
