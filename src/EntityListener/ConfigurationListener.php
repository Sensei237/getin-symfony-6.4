<?php 
namespace App\EntityListener;

use App\Entity\Configuration;
use App\Service\Utils;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ConfigurationListener
{
    private $imagesDirectory;
    private $publicDistImg;

    public function __construct($imagesDirectory, $publicDistImg)
    {
        $this->imagesDirectory = $imagesDirectory;
        $this->publicDistImg = $publicDistImg;
    }

    public function postLoad(Configuration $configuration, LifecycleEventArgs $args) {
        if ($configuration && $configuration->getLogo()) {
            $logoBase64 = Utils::imageToBase64($this->imagesDirectory . "/" . $configuration->getLogo());
            $configuration->setLogoEcoleBase64($logoBase64);
        }
        
        if ($configuration && $configuration->getLogoUniversity()) {
            $logoBase64 = Utils::imageToBase64($this->imagesDirectory . "/" . $configuration->getLogoUniversity());
            $configuration->setLogoUniversityBase64($logoBase64);
        }

        $filigraneBase64 = Utils::imageToBase64($this->publicDistImg . "/filigrane.jpeg");
        $configuration->setFiligrane($filigraneBase64);

    }
}
