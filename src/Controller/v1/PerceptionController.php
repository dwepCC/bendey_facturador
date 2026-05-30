<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:50
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Perception\Perception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/perception')]
class PerceptionController extends AbstractController
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
        return $this->document->send(Perception::class);
    }

    /**
     * @return Response
     */
    #[Route('/xml', methods: ['POST'])]
    public function xml(): Response
    {
        return $this->document->xml(Perception::class);
    }

    /**
     * @return Response
     */
    #[Route('/pdf', methods: ['POST'])]
    public function pdf(): Response
    {
        return $this->document->pdf(Perception::class);
    }
}
