<?php

namespace App\Form;

use App\Entity\Classe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use App\Entity\Specialite;
use Doctrine\ORM\EntityRepository;
use App\Entity\Formation;

class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('formation',  EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner la filiere'
            ])
            ->add('nom')
            ->add('code')
            ->add('niveau')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Classe::class,
            'filiere' => false,
        ]);
    }
}
