<?php

namespace App\Subscribers;

use App\Entity\Examen;
use App\Entity\AnneeAcademique;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserSubcriber implements EventSubscriberInterface
{
    private EntityManagerInterface $doctrine;
    private EntityManagerInterface $objectManager;

    public function __construct(EntityManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->doctrine = $objectManager;
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof User) {
            $user->setIsOnline(true);
        }
        
        $annee = $this->doctrine->getRepository(AnneeAcademique::class)->findOneBy(['isArchived'=>false]);

        if (!$annee) {
            die("Aucune Annee !");
        }

        $SN = $this->doctrine->getRepository(Examen::class)->findOneBy(['code'=>'SN', 'type'=>'E']);
        $SR = $this->doctrine->getRepository(Examen::class)->findOneBy(['code'=>'SR', 'type'=>'R']);
        if (!$SN || !$SR || !$SN->getPourcentageCC() || !$SR->getPourcentageCC() || !$SN->getPourcentage() || !$SR->getPourcentage()) {
            die("Verifier que les session d'examen existent dans votre base de données ainsi que leur pourcentage respectif.");
        }

        $this->objectManager->flush();
        $session = $event->getRequest()->getSession();
        $session->set('annee', $annee);
        $session->set('pourcentageCC', $SN->getPourcentageCC());
        $session->set('pourcentageCC2', $SR->getPourcentageCC());
        $session->set('pourcentageSN', $SN->getPourcentage());
        $session->set('pourcentageSR', $SR->getPourcentage());
    }

    public static function getSubscribedEvents() {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => [['onLogin', 15]],
        ];
    }
}
