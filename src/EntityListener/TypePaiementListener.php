<?php
namespace App\EntityListener;

use App\Entity\TypeDePaiement;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TypePaiementListener
{
    public function prePersist(TypeDePaiement $tp, LifecycleEventArgs $args)
    {   
        $tp->setSlug($tp->getDenomination());
    }
}