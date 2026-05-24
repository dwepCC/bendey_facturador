<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/' => [[['_route' => 'app_home_index', '_controller' => 'App\\Controller\\HomeController::index'], null, null, null, false, false, null]],
        '/swagger' => [[['_route' => 'app_home_swagger', '_controller' => 'App\\Controller\\HomeController::swagger'], null, null, null, false, false, null]],
        '/api/v1/configuration' => [[['_route' => 'app_v1_configuration_config', '_controller' => 'App\\Controller\\v1\\ConfigurationController::config'], null, ['POST' => 0], null, true, false, null]],
        '/api/v1/despatch/send' => [[['_route' => 'app_v1_despatch_send', '_controller' => 'App\\Controller\\v1\\DespatchController::send'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/xml' => [[['_route' => 'app_v1_despatch_xml', '_controller' => 'App\\Controller\\v1\\DespatchController::xml'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/pdf' => [[['_route' => 'app_v1_despatch_pdf', '_controller' => 'App\\Controller\\v1\\DespatchController::pdf'], null, ['POST' => 0], null, false, false, null]],
        '/api/v1/despatch/status' => [[['_route' => 'app_v1_despatch_status', '_controller' => 'App\\Controller\\v1\\DespatchController::status'], null, ['GET' => 0], null, false, false, null]],
        '/api/v1/empresas' => [
            [['_route' => 'app_v1_empresas_list', '_controller' => 'App\\Controller\\v1\\EmpresasController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'app_v1_empresas_createorupdate', '_controller' => 'App\\Controller\\v1\\EmpresasController::createOrUpdate'], null, ['POST' => 0], null, false, false, null],
        ],
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
                .'|/api/v1/empresas/(\\d{11})(?'
                    .'|(*:35)'
                    .'|/ambiente(*:51)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => 'app_v1_empresas_getone', '_controller' => 'App\\Controller\\v1\\EmpresasController::getOne'], ['ruc'], ['GET' => 0], null, false, true, null]],
        51 => [
            [['_route' => 'app_v1_empresas_updateambiente', '_controller' => 'App\\Controller\\v1\\EmpresasController::updateAmbiente'], ['ruc'], ['PATCH' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
