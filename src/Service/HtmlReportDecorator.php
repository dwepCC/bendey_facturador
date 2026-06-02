<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 16/02/2019
 * Time: 22:18
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Greenter\Report\HtmlReport;
use Greenter\Report\ReportInterface;
use Greenter\Report\Resolver\TemplateResolverInterface;
use App\Service\Fiscal\FiscalCustomerEmailNormalizer;
use App\Twig\SafeImageTwigExtension;

class HtmlReportDecorator implements ReportInterface
{
    /**
     * @var HtmlReport
     */
    private $htmlReport;

    /**
     * @var TemplateResolverInterface
     */
    private $resolver;

    /**
     * HtmlReportDecorator constructor.
     * @param HtmlReport $htmlReport
     * @param TemplateResolverInterface $resolver
     */
    public function __construct(HtmlReport $htmlReport, TemplateResolverInterface $resolver)
    {
        $this->htmlReport = $htmlReport;
        $this->resolver = $resolver;
        $twig = $htmlReport->getTwig();
        if ($twig !== null) {
            $twig->addExtension(new SafeImageTwigExtension());
        }
    }

    /**
     * @param DocumentInterface $document
     * @param array $parameters
     *
     * @return string|null
     */
    public function render(DocumentInterface $document, array $parameters = []): ?string
    {
        FiscalCustomerEmailNormalizer::applyPdfSafeEmails($document);

        $template = $this->resolver->getTemplate($document);

        $this->htmlReport->setTemplate($template);

        return $this->htmlReport->render($document, $parameters);
    }
}