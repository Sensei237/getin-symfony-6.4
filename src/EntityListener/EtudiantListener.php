<?php 
namespace App\EntityListener;

use App\Entity\Etudiant;
use App\Service\Utils;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class EtudiantListener
{
    private $etudiantProfilDirectory;
    private $publicDistImg;

    public function __construct($etudiantProfilDirectory, $publicDistImg)
    {
        $this->etudiantProfilDirectory = $etudiantProfilDirectory;
    }

    public function postLoad(Etudiant $etudiant, LifecycleEventArgs $args) {
        if ($etudiant && $etudiant->getPhoto()) {
            $logoBase64 = Utils::imageToBase64($this->etudiantProfilDirectory . "/" . $etudiant->getPhoto());
            $etudiant->setPhotoBase64($logoBase64);
        }
        else{
            $logoBase64 = Utils::imageToBase64($this->publicDistImg . "/avatar.png");
            $etudiant->setPhotoBase64($logoBase64);
        }

    }
}
