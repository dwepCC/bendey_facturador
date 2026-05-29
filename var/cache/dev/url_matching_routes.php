<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/change-password' => [[['_route' => 'admin_change_password', '_controller' => 'App\\Controller\\AccountController::changePassword'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/dashboard' => [[['_route' => 'fiscal_dashboard', '_controller' => 'App\\Controller\\FiscalDashboardController::index'], null, null, null, false, false, null]],
        '/dashboard/empresas' => [[['_route' => 'fiscal_empresas', '_controller' => 'App\\Controller\\FiscalEmpresasController::index'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_home_index', '_controller' => 'App\\Controller\\HomeController::index'], null, null, null, false, false, null]],
        '/swagger' => [[['_route' => 'app_home_swagger', '_controller' => 'App\\Controller\\HomeController::swagger'], null, null, null, false, false, null]],
        '/login' => [[['_route' => 'admin_login', '_controller' => 'App\\Controller\\SecurityController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'admin_logout', '_controller' => 'App\\Controller\\SecurityController::logout'], null, null, null, false, false, null]],
        '/api/v1/configuration' => [[['_route' => 'app_v1_configuration_config', '_controller' => 'App\\Controller\\v1\\ConfigurationController::config'], null, ['POST' => 0], null, true, false, null]],
        '/api/v1/despatch/send' => [[['_route' => 'app_v1_despatch_send', '_controller' => 'App\\Controller\\v1\\DespatchController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/xml' => [[['_route' => 'app_v1_despatch_xml', '_controller' => 'App\\Controller\\v1\\DespatchController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/pdf' => [[['_route' => 'app_v1_despatch_pdf', '_controller' => 'App\\Controller\\v1\\DespatchController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/status' => [[['_route' => 'app_v1_despatch_status', '_controller' => 'App\\Controller\\v1\\DespatchController::status'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/empresas' => [
            [['_route' => 'app_v1_empresas_list', '_controller' => 'App\\Controller\\v1\\EmpresasController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'app_v1_empresas_createorupdate', '_controller' => 'App\\Controller\\v1\\EmpresasController::createOrUpdate'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/v1/fiscal/emit' => [[['_route' => 'app_v1_fiscal_emit', '_controller' => 'App\\Controller\\v1\\FiscalController::emit'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/fiscal/company-sync' => [[['_route' => 'app_v1_fiscal_companysync', '_controller' => 'App\\Controller\\v1\\FiscalController::companySync'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/fiscal/test-connection' => [[['_route' => 'app_v1_fiscal_testconnection', '_controller' => 'App\\Controller\\v1\\FiscalController::testConnection'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/fiscal/companies' => [[['_route' => 'app_v1_fiscal_companies', '_controller' => 'App\\Controller\\v1\\FiscalController::companies'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/stats' => [[['_route' => 'app_v1_fiscal_stats', '_controller' => 'App\\Controller\\v1\\FiscalController::stats'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/documents' => [[['_route' => 'app_v1_fiscal_list', '_controller' => 'App\\Controller\\v1\\FiscalController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/health' => [[['_route' => 'app_v1_fiscaloperations_health', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::health'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/operations/summary' => [[['_route' => 'app_v1_fiscaloperations_summary', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::summary'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/operations/tenants' => [[['_route' => 'app_v1_fiscaloperations_tenants', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::tenants'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/operations/queue' => [[['_route' => 'app_v1_fiscaloperations_queue', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::queue'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/fiscal/alerts' => [[['_route' => 'app_v1_fiscaloperations_alertslist', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::alertsList'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/invoice/send' => [[['_route' => 'app_v1_invoice_send', '_controller' => 'App\\Controller\\v1\\InvoiceController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/invoice/xml' => [[['_route' => 'app_v1_invoice_xml', '_controller' => 'App\\Controller\\v1\\InvoiceController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/invoice/pdf' => [[['_route' => 'app_v1_invoice_pdf', '_controller' => 'App\\Controller\\v1\\InvoiceController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/invoice/status' => [[['_route' => 'app_v1_invoice_status', '_controller' => 'App\\Controller\\v1\\InvoiceController::status'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/note/send' => [[['_route' => 'app_v1_note_send', '_controller' => 'App\\Controller\\v1\\NoteController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/note/xml' => [[['_route' => 'app_v1_note_xml', '_controller' => 'App\\Controller\\v1\\NoteController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/note/pdf' => [[['_route' => 'app_v1_note_pdf', '_controller' => 'App\\Controller\\v1\\NoteController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/perception/send' => [[['_route' => 'app_v1_perception_send', '_controller' => 'App\\Controller\\v1\\PerceptionController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/perception/xml' => [[['_route' => 'app_v1_perception_xml', '_controller' => 'App\\Controller\\v1\\PerceptionController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/perception/pdf' => [[['_route' => 'app_v1_perception_pdf', '_controller' => 'App\\Controller\\v1\\PerceptionController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/retention/send' => [[['_route' => 'app_v1_retention_send', '_controller' => 'App\\Controller\\v1\\RetentionController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/retention/xml' => [[['_route' => 'app_v1_retention_xml', '_controller' => 'App\\Controller\\v1\\RetentionController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/retention/pdf' => [[['_route' => 'app_v1_retention_pdf', '_controller' => 'App\\Controller\\v1\\RetentionController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/reversion/send' => [[['_route' => 'app_v1_reversion_send', '_controller' => 'App\\Controller\\v1\\ReversionController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/reversion/xml' => [[['_route' => 'app_v1_reversion_xml', '_controller' => 'App\\Controller\\v1\\ReversionController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/reversion/pdf' => [[['_route' => 'app_v1_reversion_pdf', '_controller' => 'App\\Controller\\v1\\ReversionController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/reversion/status' => [[['_route' => 'app_v1_reversion_status', '_controller' => 'App\\Controller\\v1\\ReversionController::status'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/sale/qr' => [[['_route' => 'app_v1_sale_qr', '_controller' => 'App\\Controller\\v1\\SaleController::qr'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/summary/send' => [[['_route' => 'app_v1_summary_send', '_controller' => 'App\\Controller\\v1\\SummaryController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/summary/xml' => [[['_route' => 'app_v1_summary_xml', '_controller' => 'App\\Controller\\v1\\SummaryController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/summary/pdf' => [[['_route' => 'app_v1_summary_pdf', '_controller' => 'App\\Controller\\v1\\SummaryController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/summary/status' => [[['_route' => 'app_v1_summary_status', '_controller' => 'App\\Controller\\v1\\SummaryController::status'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/voided/send' => [[['_route' => 'app_v1_voided_send', '_controller' => 'App\\Controller\\v1\\VoidedController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/voided/xml' => [[['_route' => 'app_v1_voided_xml', '_controller' => 'App\\Controller\\v1\\VoidedController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/voided/pdf' => [[['_route' => 'app_v1_voided_pdf', '_controller' => 'App\\Controller\\v1\\VoidedController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/voided/status' => [[['_route' => 'app_v1_voided_status', '_controller' => 'App\\Controller\\v1\\VoidedController::status'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/fiscal\\-files/(.+)(*:26)'
                .'|/api/v1/(?'
                    .'|empresas/(\\d{11})(?'
                        .'|(*:64)'
                        .'|/(?'
                            .'|status(*:81)'
                            .'|ambiente(*:96)'
                        .')'
                    .')'
                    .'|fiscal/documents/(?'
                        .'|bulk/(send|retry|force|email|poll)(*:159)'
                        .'|([^/]++)(?'
                            .'|(*:178)'
                            .'|/(?'
                                .'|send(*:194)'
                                .'|retry(*:207)'
                                .'|force(*:220)'
                                .'|email(*:233)'
                                .'|poll(*:245)'
                                .'|generate\\-pdf(*:266)'
                                .'|download/(xml|signed_xml|cdr|pdf|unsigned_xml)(*:320)'
                                .'|audit\\-timeline(*:343)'
                                .'|cancel(*:357)'
                            .')'
                        .')'
                    .')'
                .')'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:397)'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        26 => [[['_route' => 'app_fiscalfiles_serve', '_controller' => 'App\\Controller\\FiscalFilesController::serve'], ['path'], ['GET' => 0], null, false, true, null]],
        64 => [[['_route' => 'app_v1_empresas_getone', '_controller' => 'App\\Controller\\v1\\EmpresasController::getOne'], ['ruc'], ['GET' => 0], null, false, true, null]],
        81 => [[['_route' => 'app_v1_empresas_status', '_controller' => 'App\\Controller\\v1\\EmpresasController::status'], ['ruc'], ['GET' => 0], null, false, false, null]],
        96 => [[['_route' => 'app_v1_empresas_updateambiente', '_controller' => 'App\\Controller\\v1\\EmpresasController::updateAmbiente'], ['ruc'], ['PATCH' => 0], null, false, false, null]],
        159 => [[['_route' => 'app_v1_fiscal_bulk', '_controller' => 'App\\Controller\\v1\\FiscalController::bulk'], ['action'], ['POST' => 0], null, false, true, null]],
        178 => [[['_route' => 'app_v1_fiscal_detail', '_controller' => 'App\\Controller\\v1\\FiscalController::detail'], ['uuid'], ['GET' => 0], null, false, true, null]],
        194 => [[['_route' => 'app_v1_fiscal_sendmanual', '_controller' => 'App\\Controller\\v1\\FiscalController::sendManual'], ['uuid'], ['POST' => 0], null, false, false, null]],
        207 => [[['_route' => 'app_v1_fiscal_retry', '_controller' => 'App\\Controller\\v1\\FiscalController::retry'], ['uuid'], ['POST' => 0], null, false, false, null]],
        220 => [[['_route' => 'app_v1_fiscal_forcesend', '_controller' => 'App\\Controller\\v1\\FiscalController::forceSend'], ['uuid'], ['POST' => 0], null, false, false, null]],
        233 => [[['_route' => 'app_v1_fiscal_resendemail', '_controller' => 'App\\Controller\\v1\\FiscalController::resendEmail'], ['uuid'], ['POST' => 0], null, false, false, null]],
        245 => [[['_route' => 'app_v1_fiscal_pollticket', '_controller' => 'App\\Controller\\v1\\FiscalController::pollTicket'], ['uuid'], ['POST' => 0], null, false, false, null]],
        266 => [[['_route' => 'app_v1_fiscal_generatepdf', '_controller' => 'App\\Controller\\v1\\FiscalController::generatePdf'], ['uuid'], ['POST' => 0], null, false, false, null]],
        320 => [[['_route' => 'app_v1_fiscal_download', '_controller' => 'App\\Controller\\v1\\FiscalController::download'], ['uuid', 'type'], ['GET' => 0], null, false, true, null]],
        343 => [[['_route' => 'app_v1_fiscaloperations_audittimeline', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::auditTimeline'], ['uuid'], ['GET' => 0], null, false, false, null]],
        357 => [[['_route' => 'app_v1_fiscaloperations_cancel', '_controller' => 'App\\Controller\\v1\\FiscalOperationsController::cancel'], ['uuid'], ['POST' => 0], null, false, false, null]],
        397 => [
            [['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
