<?php
namespace App\Service;


class Utils
{
    public static function imageToBase64(string $path) {
        if (!file_exists($path)) {
            return null;
        }
        
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        if (!$data) {
            return null;
        }
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
