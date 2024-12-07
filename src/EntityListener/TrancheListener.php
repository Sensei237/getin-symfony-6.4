<?php
namespace App\EntityListener;

use App\Entity\Tranche;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TrancheListener
{

    public function prePersist(Tranche $tranche, LifecycleEventArgs $args)
    {   
        $tranche->setSlug($tranche->getDenomination());
    }
}