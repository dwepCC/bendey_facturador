<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:41
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Sale\Note;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/note')]
class NoteController extends AbstractController
{
    /**
     * @var DocumentRequestInterface
     */
    private $document;

    /**
     * InvoiceController constructor.
     * @param DocumentRequestInterface $document
     */
    public function __construct(DocumentRequestInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @return Response
     */
    #[Route('/send', methods: ['POST'])]
    public function send(): Response
    {
        return $this->document->send(Note::class);
    }

    /**
     * @return Response
     */
    #[Route('/xml', methods: ['POST'])]
    public function xml(): Response
    {
        return $this->document->xml(Note::class);
    }

    /**
     * @return Response
     */
    #[Route('/pdf', methods: ['POST'])]
    public function pdf(): Response
    {
        return $this->document->pdf(Note::class);
    }
}
