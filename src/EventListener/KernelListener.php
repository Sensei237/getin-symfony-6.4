<?php

namespace App\EventListener;

use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function PHPUnit\Framework\throwException;

final class KernelListener
{
    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $now = new \DateTimeImmutable();
        $now_format = date_format($now, "d-m-Y");

        $expiredAt = "01-07-2025";


        if ($now_format === $expiredAt) {
            throw new Exception("Votre licence a expiré. Veuillez contacter votre fournisseur pour une eventuelle mise à jour de la licence");
        }
    }
}
