<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Entity\OutboundEmailLog;
use App\Repository\FiscalDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Envía correo fiscal (PDF+XML+CDR) usando email del snapshot — sin consultar tenant DB.
 */
class FiscalEmailProcessor
{
    private EntityManagerInterface $em;
    private FiscalDocumentRepository $repo;
    private FiscalQueueService $queue;
    private FiscalMailerService $mailer;
    private LoggerInterface $logger;
    private string $mailFrom;

    public function __construct(
        EntityManagerInterface $em,
        FiscalDocumentRepository $repo,
        FiscalQueueService $queue,
        FiscalMailerService $mailer,
        LoggerInterface $logger,
        string $mailFrom = 'noreply@tukifac.com'
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->queue = $queue;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->mailFrom = $mailFrom;
    }

    public function processByUuid(string $documentUuid): void
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $documentUuid]);
        if ($doc === null) {
            return;
        }

        $email = $this->resolveEmailFromSnapshot($doc);
        if ($email === null || $email === '') {
            $doc->setEmailStatus('skipped');
            $this->em->flush();
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $log = new OutboundEmailLog();
            $log->setDocumentUuid($documentUuid);
            $log->setRecipientEmail($email);
            $log->setStatus(OutboundEmailLog::STATUS_FAILED);
            $log->setErrorMessage('email inválido');
            $log->setAttempts(1);
            $this->em->persist($log);
            $doc->setEmailStatus('invalid');
            $this->em->flush();
            return;
        }

        $log = new OutboundEmailLog();
        $log->setDocumentUuid($documentUuid);
        $log->setRecipientEmail($email);
        $this->em->persist($log);

        try {
            $this->sendMail($doc, $email, $log);
            $log->setStatus(OutboundEmailLog::STATUS_SENT);
            $log->setSentAt(new \DateTimeImmutable());
            $doc->setEmailStatus('sent');
        } catch (\Throwable $e) {
            $log->setStatus(OutboundEmailLog::STATUS_FAILED);
            $log->setErrorMessage($e->getMessage());
            $log->setAttempts($log->getAttempts() + 1);
            $doc->setEmailStatus('failed');
            if ($log->getAttempts() < 5) {
                $this->queue->scheduleRetry($documentUuid, min(3600, 60 * $log->getAttempts()), FiscalQueueService::QUEUE_EMAIL);
            } elseif (strpos(strtolower($e->getMessage()), 'invalid') !== false) {
                $doc->setEmailStatus('invalid');
            }
            $this->logger->error('fiscal_email_failed', ['uuid' => $documentUuid, 'error' => $e->getMessage()]);
        }

        $this->em->flush();
    }

    private function resolveEmailFromSnapshot(FiscalDocument $doc): ?string
    {
        if ($doc->getCustomerEmail()) {
            return trim($doc->getCustomerEmail());
        }
        $data = json_decode($doc->getSnapshotJson(), true);
        if (!is_array($data)) {
            return null;
        }
        foreach (['customer', 'client'] as $key) {
            if (isset($data[$key]['email']) && is_string($data[$key]['email']) && trim($data[$key]['email']) !== '') {
                return trim($data[$key]['email']);
            }
        }
        return null;
    }

    private function sendMail(FiscalDocument $doc, string $to, OutboundEmailLog $log): void
    {
        $response = $this->mailer->sendFiscalDocument($doc, $to);
        $log->setAttempts($log->getAttempts() + 1);
        $log->setProviderResponse($response);
    }
}
