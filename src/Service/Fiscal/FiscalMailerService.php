<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Envío de correo fiscal vía Symfony Mailer con adjuntos local/S3/R2.
 */
class FiscalMailerService
{
    private MailerInterface $mailer;
    private FiscalFileFetcher $fileFetcher;
    private string $fromAddress;
    private string $fromName;

    public function __construct(
        MailerInterface $mailer,
        FiscalFileFetcher $fileFetcher,
        string $fromAddress,
        string $fromName
    ) {
        $this->mailer = $mailer;
        $this->fileFetcher = $fileFetcher;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function sendFiscalDocument(FiscalDocument $doc, string $to): string
    {
        if (!FiscalCustomerEmailNormalizer::isDeliverable($to)) {
            throw new \InvalidArgumentException('email inválido o no disponible');
        }

        $subject = sprintf(
            'Comprobante electrónico %s-%s-%s',
            $doc->getDocumentType(),
            $doc->getSeries(),
            $doc->getNumber()
        );

        $html = '<p>Adjuntamos su comprobante electrónico SUNAT.</p>';
        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->html($html)
            ->text('Adjuntamos su comprobante electrónico SUNAT.');

        $attached = 0;
        $attached += $this->attachFromUrl($email, $doc->getPdfUrl(), 'comprobante.pdf') ? 1 : 0;
        $attached += $this->attachFromUrl($email, $doc->getXmlSignedUrl(), 'comprobante.xml') ? 1 : 0;
        $attached += $this->attachFromUrl($email, $doc->getCdrUrl(), 'cdr.zip') ? 1 : 0;

        if ($attached === 0) {
            $html .= '<ul>';
            foreach (['pdf' => $doc->getPdfUrl(), 'xml' => $doc->getXmlSignedUrl(), 'cdr' => $doc->getCdrUrl()] as $label => $url) {
                if ($url) {
                    $html .= '<li>' . $label . ': <a href="' . htmlspecialchars($url) . '">descargar</a></li>';
                }
            }
            $html .= '</ul>';
            $email->html($html);
        }

        $this->mailer->send($email);
        return 'symfony_mailer=sent attachments=' . $attached;
    }

    private function attachFromUrl(Email $email, ?string $publicUrl, string $filename): bool
    {
        $fetched = $this->fileFetcher->fetch($publicUrl);
        if ($fetched === null) {
            return false;
        }
        $email->attach($fetched['content'], $filename, $fetched['mime']);
        return true;
    }
}
