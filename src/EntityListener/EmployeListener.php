<?php
namespace App\EntityListener;

use App\Entity\Employe;
use App\Service\FileUploader;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class EmployeListener
{
    /**
     * @var Package
     */
    private $assetsHelper;
    
    /**
     * @var FileUploader
     */
    private $fileUploader;

    private $imageUploadPath;
    private $employe;

    // public function __construct($assetsHelper, FileUploader $fileUploader, $imageUploadPath)
    // {
    //     $this->assetsHelper = $assetsHelper;
    //     $this->fileUploader = $fileUploader;
    //     $this->imageUploadPath = $imageUploadPath.'/employe/';
    // }
    
    public function prePersist(Employe $employe, LifecycleEventArgs $args)
    {   
        $this->employe = $employe;
        $ref = time().'-'.str_replace([' ', '@', '$', '%', '&', '(', ')', '!', '?', '^', '#'], '', str_replace(['é', 'è', 'ê'], 'e', mb_strtolower($employe->getNom().'-'.$employe->getPrenom())));
        $employe->setReference($ref);

        // if ($employe->file !== null) {
        //     $filename = $this->fileUploader->upload($this->employe->file, $this->imageUploadPath);
        //     if (trim($filename) != "") {
        //         $this->employe->setPhoto($filename);
        //     }
        // }
    }


    public function preUpdate(Employe $employe, LifecycleEventArgs $args)
    {
        $this->employe = $employe;
        $ref = time().'-'.str_replace([' ', '@', '$', '%', '&', '(', ')', '!', '?', '^', '#'], '', str_replace(['é', 'è', 'ê'], 'e', $employe->getNom().'-'.$employe->getPrenom().'-'.$employe->getId()));
        $this->employe->setReference($ref);

        // if ($employe->file !== null) {
        //     $filename = $this->fileUploader->upload($this->employe->file, $this->imageUploadPath);
        //     if (trim($filename) != "") {
        //         $this->employe->setPhoto($filename);
        //     }
        // }
    }

    // public function preRemove(Employe $employe, LifecycleEventArgs $args)
    // {
    //     $this->employe = $args->getEntity();
    //     $file = $this->imageUploadPath . $this->employe->getPhoto();
    //     if ($this->employe->getPhoto() && file_exists($file)) {
    //         unlink($file);
    //     }
    // }
}