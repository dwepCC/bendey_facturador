<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sirve archivos fiscales almacenados localmente.
 */
#[Route('/fiscal-files')]
class FiscalFilesController
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    #[Route('/{path}', requirements: ['path' => '.+'], methods: ['GET'])]
    public function serve(string $path): Response
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $full = $this->storagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (!is_file($full)) {
            return new Response('Not found', 404);
        }
        $response = new BinaryFileResponse($full);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($full));
        return $response;
    }
}
