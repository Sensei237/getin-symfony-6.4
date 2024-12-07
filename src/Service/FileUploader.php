<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Permet l'upload de fichier
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class FileUploader
{
    private $targetPath;

    public function __construct($uploadPath)
    {
        $this->targetPath = $uploadPath;
    }

    public function upload(UploadedFile $file, $path = null, $filename = null): string
    {
        if (null === $path) {
            $path = $this->targetPath;
        }
        if (!$filename) {
            $filename = $this->generateUniqueName($file);
        }
        # Fix the missing image/svg mimetype in Symfony
        // if ($file->getMimeType() === "image/svg") {
        //     $filename .= "svg";
        // }
        $file->move($path, $filename);
        return $filename;
    }
    
    public function generateUniqueName(UploadedFile $file): string
    {
        return md5(uniqid()).".".$file->guessExtension();
    }
}