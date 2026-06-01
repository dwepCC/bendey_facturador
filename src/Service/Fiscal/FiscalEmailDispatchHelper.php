<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Encola envío de email fiscal solo si hay dirección entregable.
 */
final class FiscalEmailDispatchHelper
{
    public static function enqueueOrMarkUnavailable(
        FiscalDocument $doc,
        Empresa $empresa,
        FiscalQueueService $queue
    ): void {
        if (!$empresa->isEmailEnabled()) {
            return;
        }

        $email = FiscalCustomerEmailNormalizer::normalize($doc->getCustomerEmail())
            ?? FiscalCustomerEmailNormalizer::extractFromSnapshotJson($doc->getSnapshotJson());

        if (!FiscalCustomerEmailNormalizer::isDeliverable($email)) {
            $doc->setEmailStatus(FiscalCustomerEmailNormalizer::STATUS_NOT_AVAILABLE);

            return;
        }

        $queue->push(FiscalQueueService::QUEUE_EMAIL, [
            'document_uuid' => $doc->getDocumentUuid(),
        ]);
    }
}
