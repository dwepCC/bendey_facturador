<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* invoice2.1.xml.twig */
class __TwigTemplate_cbafca055332cd2fefdf084489407442 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        $_v0 = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            // line 2
            yield "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<Invoice xmlns=\"urn:oasis:names:specification:ubl:schema:xsd:Invoice-2\" xmlns:cac=\"urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2\" xmlns:cbc=\"urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2\" xmlns:ds=\"http://www.w3.org/2000/09/xmldsig#\" xmlns:ext=\"urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2\">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    ";
            // line 9
            $context["emp"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "company", [], "any", false, false, false, 9);
            // line 10
            yield "    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>";
            // line 12
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "serie", [], "any", false, false, false, 12);
            yield "-";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "correlativo", [], "any", false, false, false, 12);
            yield "</cbc:ID>
    <cbc:IssueDate>";
            // line 13
            yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "fechaEmision", [], "any", false, false, false, 13), "Y-m-d");
            yield "</cbc:IssueDate>
    <cbc:IssueTime>";
            // line 14
            yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "fechaEmision", [], "any", false, false, false, 14), "H:i:s");
            yield "</cbc:IssueTime>
    ";
            // line 15
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "fecVencimiento", [], "any", false, false, false, 15)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 16
                yield "    <cbc:DueDate>";
                yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "fecVencimiento", [], "any", false, false, false, 16), "Y-m-d");
                yield "</cbc:DueDate>
    ";
            }
            // line 18
            yield "    <cbc:InvoiceTypeCode listID=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoOperacion", [], "any", false, false, false, 18);
            yield "\">";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoDoc", [], "any", false, false, false, 18);
            yield "</cbc:InvoiceTypeCode>
    ";
            // line 19
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "legends", [], "any", false, false, false, 19));
            foreach ($context['_seq'] as $context["_key"] => $context["leg"]) {
                // line 20
                yield "    <cbc:Note languageLocaleID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["leg"], "code", [], "any", false, false, false, 20);
                yield "\"><![CDATA[";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["leg"], "value", [], "any", false, false, false, 20);
                yield "]]></cbc:Note>
    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['leg'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 22
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "observacion", [], "any", false, false, false, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 23
                yield "    <cbc:Note><![CDATA[";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "observacion", [], "any", false, false, false, 23);
                yield "]]></cbc:Note>
    ";
            }
            // line 25
            yield "    <cbc:DocumentCurrencyCode>";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 25);
            yield "</cbc:DocumentCurrencyCode>
    ";
            // line 26
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "compra", [], "any", false, false, false, 26)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 27
                yield "    <cac:OrderReference>
        <cbc:ID>";
                // line 28
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "compra", [], "any", false, false, false, 28);
                yield "</cbc:ID>
    </cac:OrderReference>
    ";
            }
            // line 31
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "guias", [], "any", false, false, false, 31)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 32
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "guias", [], "any", false, false, false, 32));
                foreach ($context['_seq'] as $context["_key"] => $context["guia"]) {
                    // line 33
                    yield "    <cac:DespatchDocumentReference>
        <cbc:ID>";
                    // line 34
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["guia"], "nroDoc", [], "any", false, false, false, 34);
                    yield "</cbc:ID>
        <cbc:DocumentTypeCode>";
                    // line 35
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["guia"], "tipoDoc", [], "any", false, false, false, 35);
                    yield "</cbc:DocumentTypeCode>
    </cac:DespatchDocumentReference>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['guia'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 38
                yield "    ";
            }
            // line 39
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "relDocs", [], "any", false, false, false, 39)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 40
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "relDocs", [], "any", false, false, false, 40));
                foreach ($context['_seq'] as $context["_key"] => $context["rel"]) {
                    // line 41
                    yield "    <cac:AdditionalDocumentReference>
        <cbc:ID>";
                    // line 42
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["rel"], "nroDoc", [], "any", false, false, false, 42);
                    yield "</cbc:ID>
        <cbc:DocumentTypeCode>";
                    // line 43
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["rel"], "tipoDoc", [], "any", false, false, false, 43);
                    yield "</cbc:DocumentTypeCode>
    </cac:AdditionalDocumentReference>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['rel'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 46
                yield "    ";
            }
            // line 47
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "anticipos", [], "any", false, false, false, 47)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 48
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "anticipos", [], "any", false, false, false, 48));
                $context['loop'] = [
                  'parent' => $context['_parent'],
                  'index0' => 0,
                  'index'  => 1,
                  'first'  => true,
                ];
                if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                    $length = count($context['_seq']);
                    $context['loop']['revindex0'] = $length - 1;
                    $context['loop']['revindex'] = $length;
                    $context['loop']['length'] = $length;
                    $context['loop']['last'] = 1 === $length;
                }
                foreach ($context['_seq'] as $context["_key"] => $context["ant"]) {
                    // line 49
                    yield "    <cac:AdditionalDocumentReference>
        <cbc:ID>";
                    // line 50
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["ant"], "nroDocRel", [], "any", false, false, false, 50);
                    yield "</cbc:ID>
        <cbc:DocumentTypeCode>";
                    // line 51
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["ant"], "tipoDocRel", [], "any", false, false, false, 51);
                    yield "</cbc:DocumentTypeCode>
        <cbc:DocumentStatusCode>";
                    // line 52
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 52);
                    yield "</cbc:DocumentStatusCode>
        <cac:IssuerParty>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"6\">";
                    // line 55
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "ruc", [], "any", false, false, false, 55);
                    yield "</cbc:ID>
            </cac:PartyIdentification>
        </cac:IssuerParty>
    </cac:AdditionalDocumentReference>
    ";
                    ++$context['loop']['index0'];
                    ++$context['loop']['index'];
                    $context['loop']['first'] = false;
                    if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                        --$context['loop']['revindex0'];
                        --$context['loop']['revindex'];
                        $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                    }
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['ant'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 60
                yield "    ";
            }
            // line 61
            yield "    <cac:Signature>
        <cbc:ID>SIGN";
            // line 62
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "ruc", [], "any", false, false, false, 62);
            yield "</cbc:ID>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>";
            // line 65
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "ruc", [], "any", false, false, false, 65);
            yield "</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[";
            // line 68
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "razonSocial", [], "any", false, false, false, 68);
            yield "]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#GREENTER-SIGN</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"6\">";
            // line 80
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "ruc", [], "any", false, false, false, 80);
            yield "</cbc:ID>
            </cac:PartyIdentification>
\t\t\t";
            // line 82
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "nombreComercial", [], "any", false, false, false, 82)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 83
                yield "            <cac:PartyName>
                <cbc:Name><![CDATA[";
                // line 84
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "nombreComercial", [], "any", false, false, false, 84);
                yield "]]></cbc:Name>
            </cac:PartyName>
\t\t\t";
            }
            // line 87
            yield "            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[";
            // line 88
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "razonSocial", [], "any", false, false, false, 88);
            yield "]]></cbc:RegistrationName>
                ";
            // line 89
            $context["addr"] = CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "address", [], "any", false, false, false, 89);
            // line 90
            yield "                <cac:RegistrationAddress>
                    <cbc:ID>";
            // line 91
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 91);
            yield "</cbc:ID>
                    <cbc:AddressTypeCode>";
            // line 92
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "codLocal", [], "any", false, false, false, 92);
            yield "</cbc:AddressTypeCode>
                    ";
            // line 93
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "urbanizacion", [], "any", false, false, false, 93)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 94
                yield "                    <cbc:CitySubdivisionName>";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "urbanizacion", [], "any", false, false, false, 94);
                yield "</cbc:CitySubdivisionName>
                    ";
            }
            // line 96
            yield "                    <cbc:CityName>";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "provincia", [], "any", false, false, false, 96);
            yield "</cbc:CityName>
                    <cbc:CountrySubentity>";
            // line 97
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "departamento", [], "any", false, false, false, 97);
            yield "</cbc:CountrySubentity>
                    <cbc:District>";
            // line 98
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "distrito", [], "any", false, false, false, 98);
            yield "</cbc:District>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[";
            // line 100
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "direccion", [], "any", false, false, false, 100);
            yield "]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>";
            // line 103
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "codigoPais", [], "any", false, false, false, 103);
            yield "</cbc:IdentificationCode>
                    </cac:Country>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
            ";
            // line 107
            if ((CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "email", [], "any", false, false, false, 107) || CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "telephone", [], "any", false, false, false, 107))) {
                // line 108
                yield "            <cac:Contact>
                ";
                // line 109
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "telephone", [], "any", false, false, false, 109)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 110
                    yield "                <cbc:Telephone>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "telephone", [], "any", false, false, false, 110);
                    yield "</cbc:Telephone>
                ";
                }
                // line 112
                yield "                ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "email", [], "any", false, false, false, 112)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 113
                    yield "                <cbc:ElectronicMail>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["emp"] ?? null), "email", [], "any", false, false, false, 113);
                    yield "</cbc:ElectronicMail>
                ";
                }
                // line 115
                yield "            </cac:Contact>
            ";
            }
            // line 117
            yield "        </cac:Party>
    </cac:AccountingSupplierParty>
    ";
            // line 119
            $context["client"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "client", [], "any", false, false, false, 119);
            // line 120
            yield "    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"";
            // line 123
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "tipoDoc", [], "any", false, false, false, 123);
            yield "\">";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "numDoc", [], "any", false, false, false, 123);
            yield "</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[";
            // line 126
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "rznSocial", [], "any", false, false, false, 126);
            yield "]]></cbc:RegistrationName>
                ";
            // line 127
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "address", [], "any", false, false, false, 127)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 128
                yield "                ";
                $context["addr"] = CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "address", [], "any", false, false, false, 128);
                // line 129
                yield "                <cac:RegistrationAddress>
                    ";
                // line 130
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 130)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 131
                    yield "                    <cbc:ID>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 131);
                    yield "</cbc:ID>
                    ";
                }
                // line 133
                yield "                    <cac:AddressLine>
                        <cbc:Line><![CDATA[";
                // line 134
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "direccion", [], "any", false, false, false, 134);
                yield "]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>";
                // line 137
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "codigoPais", [], "any", false, false, false, 137);
                yield "</cbc:IdentificationCode>
                    </cac:Country>
                </cac:RegistrationAddress>
                ";
            }
            // line 141
            yield "            </cac:PartyLegalEntity>
            ";
            // line 142
            if ((CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "email", [], "any", false, false, false, 142) || CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "telephone", [], "any", false, false, false, 142))) {
                // line 143
                yield "            <cac:Contact>
                ";
                // line 144
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "telephone", [], "any", false, false, false, 144)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 145
                    yield "                <cbc:Telephone>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "telephone", [], "any", false, false, false, 145);
                    yield "</cbc:Telephone>
                ";
                }
                // line 147
                yield "                ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "email", [], "any", false, false, false, 147)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 148
                    yield "                <cbc:ElectronicMail>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["client"] ?? null), "email", [], "any", false, false, false, 148);
                    yield "</cbc:ElectronicMail>
                ";
                }
                // line 150
                yield "            </cac:Contact>
            ";
            }
            // line 152
            yield "        </cac:Party>
    </cac:AccountingCustomerParty>
    ";
            // line 154
            $context["seller"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "seller", [], "any", false, false, false, 154);
            // line 155
            yield "    ";
            if ((($tmp = ($context["seller"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 156
                yield "    <cac:SellerSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"";
                // line 159
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "tipoDoc", [], "any", false, false, false, 159);
                yield "\">";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "numDoc", [], "any", false, false, false, 159);
                yield "</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[";
                // line 162
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "rznSocial", [], "any", false, false, false, 162);
                yield "]]></cbc:RegistrationName>
                ";
                // line 163
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "address", [], "any", false, false, false, 163)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 164
                    yield "                ";
                    $context["addr"] = CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "address", [], "any", false, false, false, 164);
                    // line 165
                    yield "                <cac:RegistrationAddress>
                    ";
                    // line 166
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 166)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 167
                        yield "                    <cbc:ID>";
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 167);
                        yield "</cbc:ID>
                    ";
                    }
                    // line 169
                    yield "                    <cac:AddressLine>
                        <cbc:Line><![CDATA[";
                    // line 170
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "direccion", [], "any", false, false, false, 170);
                    yield "]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>";
                    // line 173
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "codigoPais", [], "any", false, false, false, 173);
                    yield "</cbc:IdentificationCode>
                    </cac:Country>
                </cac:RegistrationAddress>
                ";
                }
                // line 177
                yield "            </cac:PartyLegalEntity>
            ";
                // line 178
                if ((CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "email", [], "any", false, false, false, 178) || CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "telephone", [], "any", false, false, false, 178))) {
                    // line 179
                    yield "            <cac:Contact>
                ";
                    // line 180
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "telephone", [], "any", false, false, false, 180)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 181
                        yield "                <cbc:Telephone>";
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "telephone", [], "any", false, false, false, 181);
                        yield "</cbc:Telephone>
                ";
                    }
                    // line 183
                    yield "                ";
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "email", [], "any", false, false, false, 183)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 184
                        yield "                <cbc:ElectronicMail>";
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["seller"] ?? null), "email", [], "any", false, false, false, 184);
                        yield "</cbc:ElectronicMail>
                ";
                    }
                    // line 186
                    yield "            </cac:Contact>
            ";
                }
                // line 188
                yield "        </cac:Party>
    </cac:SellerSupplierParty>
    ";
            }
            // line 191
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "direccionEntrega", [], "any", false, false, false, 191)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 192
                yield "        ";
                $context["addr"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "direccionEntrega", [], "any", false, false, false, 192);
                // line 193
                yield "        <cac:Delivery>
            <cac:DeliveryLocation>
                <cac:Address>
                    <cbc:ID>";
                // line 196
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "ubigueo", [], "any", false, false, false, 196);
                yield "</cbc:ID>
                    ";
                // line 197
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "urbanizacion", [], "any", false, false, false, 197)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 198
                    yield "                    <cbc:CitySubdivisionName>";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "urbanizacion", [], "any", false, false, false, 198);
                    yield "</cbc:CitySubdivisionName>
                    ";
                }
                // line 200
                yield "                    <cbc:CityName>";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "provincia", [], "any", false, false, false, 200);
                yield "</cbc:CityName>
                    <cbc:CountrySubentity>";
                // line 201
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "departamento", [], "any", false, false, false, 201);
                yield "</cbc:CountrySubentity>
                    <cbc:District>";
                // line 202
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "distrito", [], "any", false, false, false, 202);
                yield "</cbc:District>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[";
                // line 204
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "direccion", [], "any", false, false, false, 204);
                yield "]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode listID=\"ISO 3166-1\" listAgencyName=\"United Nations Economic Commission for Europe\" listName=\"Country\">";
                // line 207
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["addr"] ?? null), "codigoPais", [], "any", false, false, false, 207);
                yield "</cbc:IdentificationCode>
                    </cac:Country>
                </cac:Address>
            </cac:DeliveryLocation>
        </cac:Delivery>
    ";
            }
            // line 213
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "detraccion", [], "any", false, false, false, 213)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 214
                yield "    ";
                $context["detr"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "detraccion", [], "any", false, false, false, 214);
                // line 215
                yield "    <cac:PaymentMeans>
        <cbc:ID>Detraccion</cbc:ID>
        <cbc:PaymentMeansCode>";
                // line 217
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["detr"] ?? null), "codMedioPago", [], "any", false, false, false, 217);
                yield "</cbc:PaymentMeansCode>
        <cac:PayeeFinancialAccount>
            <cbc:ID>";
                // line 219
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["detr"] ?? null), "ctaBanco", [], "any", false, false, false, 219);
                yield "</cbc:ID>
        </cac:PayeeFinancialAccount>
    </cac:PaymentMeans>
    <cac:PaymentTerms>
        <cbc:ID>Detraccion</cbc:ID>
        <cbc:PaymentMeansID>";
                // line 224
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["detr"] ?? null), "codBienDetraccion", [], "any", false, false, false, 224);
                yield "</cbc:PaymentMeansID>
        <cbc:PaymentPercent>";
                // line 225
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["detr"] ?? null), "percent", [], "any", false, false, false, 225);
                yield "</cbc:PaymentPercent>
        <cbc:Amount currencyID=\"PEN\">";
                // line 226
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["detr"] ?? null), "mount", [], "any", false, false, false, 226));
                yield "</cbc:Amount>
    </cac:PaymentTerms>
    ";
            }
            // line 229
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "perception", [], "any", false, false, false, 229)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 230
                yield "    <cac:PaymentTerms>
        <cbc:ID>Percepcion</cbc:ID>
        <cbc:Amount currencyID=\"PEN\">";
                // line 232
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "perception", [], "any", false, false, false, 232), "mtoTotal", [], "any", false, false, false, 232));
                yield "</cbc:Amount>
    </cac:PaymentTerms>
    ";
            }
            // line 235
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, false, false, 235)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 236
                yield "    <cac:PaymentTerms>
        <cbc:ID>FormaPago</cbc:ID>
        <cbc:PaymentMeansID>";
                // line 238
                yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, false, false, 238), "tipo", [], "any", false, false, false, 238);
                yield "</cbc:PaymentMeansID>
        ";
                // line 239
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, false, false, 239), "monto", [], "any", false, false, false, 239)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 240
                    yield "        <cbc:Amount currencyID=\"";
                    yield ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, true, false, 240), "moneda", [], "any", true, true, false, 240)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, false, false, 240), "moneda", [], "any", false, false, false, 240), CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 240))) : (CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 240)));
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "formaPago", [], "any", false, false, false, 240), "monto", [], "any", false, false, false, 240));
                    yield "</cbc:Amount>
        ";
                }
                // line 242
                yield "    </cac:PaymentTerms>
    ";
            }
            // line 244
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "cuotas", [], "any", false, false, false, 244)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 245
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "cuotas", [], "any", false, false, false, 245));
                $context['loop'] = [
                  'parent' => $context['_parent'],
                  'index0' => 0,
                  'index'  => 1,
                  'first'  => true,
                ];
                if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                    $length = count($context['_seq']);
                    $context['loop']['revindex0'] = $length - 1;
                    $context['loop']['revindex'] = $length;
                    $context['loop']['length'] = $length;
                    $context['loop']['last'] = 1 === $length;
                }
                foreach ($context['_seq'] as $context["_key"] => $context["cuota"]) {
                    // line 246
                    yield "    <cac:PaymentTerms>
        <cbc:ID>FormaPago</cbc:ID>
        <cbc:PaymentMeansID>Cuota";
                    // line 248
                    yield Twig\Extension\CoreExtension::sprintf("%03d", CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 248));
                    yield "</cbc:PaymentMeansID>
        <cbc:Amount currencyID=\"";
                    // line 249
                    yield ((CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "moneda", [], "any", true, true, false, 249)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "moneda", [], "any", false, false, false, 249), CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 249))) : (CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 249)));
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "monto", [], "any", false, false, false, 249));
                    yield "</cbc:Amount>
        <cbc:PaymentDueDate>";
                    // line 250
                    yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "fechaPago", [], "any", false, false, false, 250), "Y-m-d");
                    yield "</cbc:PaymentDueDate>
    </cac:PaymentTerms>
    ";
                    ++$context['loop']['index0'];
                    ++$context['loop']['index'];
                    $context['loop']['first'] = false;
                    if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                        --$context['loop']['revindex0'];
                        --$context['loop']['revindex'];
                        $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                    }
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['cuota'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 253
                yield "    ";
            }
            // line 254
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "anticipos", [], "any", false, false, false, 254)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 255
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "anticipos", [], "any", false, false, false, 255));
                $context['loop'] = [
                  'parent' => $context['_parent'],
                  'index0' => 0,
                  'index'  => 1,
                  'first'  => true,
                ];
                if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                    $length = count($context['_seq']);
                    $context['loop']['revindex0'] = $length - 1;
                    $context['loop']['revindex'] = $length;
                    $context['loop']['length'] = $length;
                    $context['loop']['last'] = 1 === $length;
                }
                foreach ($context['_seq'] as $context["_key"] => $context["ant"]) {
                    // line 256
                    yield "    <cac:PrepaidPayment>
        <cbc:ID>";
                    // line 257
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 257);
                    yield "</cbc:ID>
        <cbc:PaidAmount currencyID=\"";
                    // line 258
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 258);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["ant"], "total", [], "any", false, false, false, 258));
                    yield "</cbc:PaidAmount>
    </cac:PrepaidPayment>
    ";
                    ++$context['loop']['index0'];
                    ++$context['loop']['index'];
                    $context['loop']['first'] = false;
                    if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                        --$context['loop']['revindex0'];
                        --$context['loop']['revindex'];
                        $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                    }
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['ant'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 261
                yield "    ";
            }
            // line 262
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "cargos", [], "any", false, false, false, 262)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 263
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "cargos", [], "any", false, false, false, 263));
                foreach ($context['_seq'] as $context["_key"] => $context["cargo"]) {
                    // line 264
                    yield "    <cac:AllowanceCharge>
        <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
        <cbc:AllowanceChargeReasonCode>";
                    // line 266
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "codTipo", [], "any", false, false, false, 266);
                    yield "</cbc:AllowanceChargeReasonCode>
        <cbc:MultiplierFactorNumeric>";
                    // line 267
                    yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "factor", [], "any", false, false, false, 267), 5);
                    yield "</cbc:MultiplierFactorNumeric>
        <cbc:Amount currencyID=\"";
                    // line 268
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 268);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "monto", [], "any", false, false, false, 268));
                    yield "</cbc:Amount>
        <cbc:BaseAmount currencyID=\"";
                    // line 269
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 269);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "montoBase", [], "any", false, false, false, 269));
                    yield "</cbc:BaseAmount>
    </cac:AllowanceCharge>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['cargo'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 272
                yield "    ";
            }
            // line 273
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "descuentos", [], "any", false, false, false, 273)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 274
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "descuentos", [], "any", false, false, false, 274));
                foreach ($context['_seq'] as $context["_key"] => $context["desc"]) {
                    // line 275
                    yield "    <cac:AllowanceCharge>
        <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
        <cbc:AllowanceChargeReasonCode>";
                    // line 277
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "codTipo", [], "any", false, false, false, 277);
                    yield "</cbc:AllowanceChargeReasonCode>
        ";
                    // line 278
                    if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "factor", [], "any", false, false, false, 278))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 279
                        yield "        <cbc:MultiplierFactorNumeric>";
                        yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "factor", [], "any", false, false, false, 279), 5);
                        yield "</cbc:MultiplierFactorNumeric>
        ";
                    }
                    // line 281
                    yield "        <cbc:Amount currencyID=\"";
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 281);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "monto", [], "any", false, false, false, 281));
                    yield "</cbc:Amount>
        <cbc:BaseAmount currencyID=\"";
                    // line 282
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 282);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "montoBase", [], "any", false, false, false, 282));
                    yield "</cbc:BaseAmount>
    </cac:AllowanceCharge>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['desc'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 285
                yield "    ";
            }
            // line 286
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "perception", [], "any", false, false, false, 286)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 287
                yield "    ";
                $context["perc"] = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "perception", [], "any", false, false, false, 287);
                // line 288
                yield "    <cac:AllowanceCharge>
        <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
        <cbc:AllowanceChargeReasonCode>";
                // line 290
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["perc"] ?? null), "codReg", [], "any", false, false, false, 290);
                yield "</cbc:AllowanceChargeReasonCode>
        <cbc:MultiplierFactorNumeric>";
                // line 291
                yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["perc"] ?? null), "porcentaje", [], "any", false, false, false, 291), 5);
                yield "</cbc:MultiplierFactorNumeric>
        <cbc:Amount currencyID=\"PEN\">";
                // line 292
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["perc"] ?? null), "mto", [], "any", false, false, false, 292));
                yield "</cbc:Amount>
        <cbc:BaseAmount currencyID=\"PEN\">";
                // line 293
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["perc"] ?? null), "mtoBase", [], "any", false, false, false, 293));
                yield "</cbc:BaseAmount>
    </cac:AllowanceCharge>
    ";
            }
            // line 296
            yield "    <cac:TaxTotal>
        <cbc:TaxAmount currencyID=\"";
            // line 297
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 297);
            yield "\">";
            yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "totalImpuestos", [], "any", false, false, false, 297));
            yield "</cbc:TaxAmount>
        ";
            // line 298
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoISC", [], "any", false, false, false, 298)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 299
                yield "        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID=\"";
                // line 300
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 300);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoBaseIsc", [], "any", false, false, false, 300));
                yield "</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID=\"";
                // line 301
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 301);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoISC", [], "any", false, false, false, 301));
                yield "</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>2000</cbc:ID>
                    <cbc:Name>ISC</cbc:Name>
                    <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
        ";
            }
            // line 311
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperGravadas", [], "any", false, false, false, 311))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 312
                yield "        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID=\"";
                // line 313
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 313);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperGravadas", [], "any", false, false, false, 313));
                yield "</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID=\"";
                // line 314
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 314);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoIGV", [], "any", false, false, false, 314));
                yield "</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>1000</cbc:ID>
                    <cbc:Name>IGV</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
        ";
            }
            // line 324
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperInafectas", [], "any", false, false, false, 324))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 325
                yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                // line 326
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 326);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperInafectas", [], "any", false, false, false, 326));
                yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                // line 327
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 327);
                yield "\">0</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9998</cbc:ID>
                        <cbc:Name>INA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        ";
            }
            // line 337
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperExoneradas", [], "any", false, false, false, 337))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 338
                yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                // line 339
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 339);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperExoneradas", [], "any", false, false, false, 339));
                yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                // line 340
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 340);
                yield "\">0</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9997</cbc:ID>
                        <cbc:Name>EXO</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        ";
            }
            // line 350
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperGratuitas", [], "any", false, false, false, 350))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 351
                yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                // line 352
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 352);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperGratuitas", [], "any", false, false, false, 352));
                yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                // line 353
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 353);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoIGVGratuitas", [], "any", false, false, false, 353));
                yield "</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9996</cbc:ID>
                        <cbc:Name>GRA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        ";
            }
            // line 363
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperExportacion", [], "any", false, false, false, 363))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 364
                yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                // line 365
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 365);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOperExportacion", [], "any", false, false, false, 365));
                yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                // line 366
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 366);
                yield "\">0</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9995</cbc:ID>
                        <cbc:Name>EXP</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        ";
            }
            // line 376
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoIvap", [], "any", false, false, false, 376)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 377
                yield "        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID=\"";
                // line 378
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 378);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoBaseIvap", [], "any", false, false, false, 378));
                yield "</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID=\"";
                // line 379
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 379);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoIvap", [], "any", false, false, false, 379));
                yield "</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>1016</cbc:ID>
                    <cbc:Name>IVAP</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
        ";
            }
            // line 389
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOtrosTributos", [], "any", false, false, false, 389)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 390
                yield "        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID=\"";
                // line 391
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 391);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoBaseOth", [], "any", false, false, false, 391));
                yield "</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID=\"";
                // line 392
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 392);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoOtrosTributos", [], "any", false, false, false, 392));
                yield "</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>9999</cbc:ID>
                    <cbc:Name>OTROS</cbc:Name>
                    <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
        ";
            }
            // line 402
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "icbper", [], "any", false, false, false, 402)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 403
                yield "            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID=\"";
                // line 404
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 404);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "icbper", [], "any", false, false, false, 404));
                yield "</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>7152</cbc:ID>
                        <cbc:Name>ICBPER</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        ";
            }
            // line 414
            yield "    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID=\"";
            // line 416
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 416);
            yield "\">";
            yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "valorVenta", [], "any", false, false, false, 416));
            yield "</cbc:LineExtensionAmount>
        ";
            // line 417
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "subTotal", [], "any", false, false, false, 417))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 418
                yield "        <cbc:TaxInclusiveAmount currencyID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 418);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "subTotal", [], "any", false, false, false, 418));
                yield "</cbc:TaxInclusiveAmount>
        ";
            }
            // line 420
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "sumOtrosDescuentos", [], "any", false, false, false, 420))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 421
                yield "        <cbc:AllowanceTotalAmount currencyID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 421);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "sumOtrosDescuentos", [], "any", false, false, false, 421));
                yield "</cbc:AllowanceTotalAmount>
        ";
            }
            // line 423
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "sumOtrosCargos", [], "any", false, false, false, 423))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 424
                yield "        <cbc:ChargeTotalAmount currencyID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 424);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "sumOtrosCargos", [], "any", false, false, false, 424));
                yield "</cbc:ChargeTotalAmount>
        ";
            }
            // line 426
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "totalAnticipos", [], "any", false, false, false, 426))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 427
                yield "        <cbc:PrepaidAmount currencyID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 427);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "totalAnticipos", [], "any", false, false, false, 427));
                yield "</cbc:PrepaidAmount>
        ";
            }
            // line 429
            yield "        ";
            if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "redondeo", [], "any", false, false, false, 429))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 430
                yield "        <cbc:PayableRoundingAmount currencyID=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 430);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "redondeo", [], "any", false, false, false, 430));
                yield "</cbc:PayableRoundingAmount>
        ";
            }
            // line 432
            yield "        <cbc:PayableAmount currencyID=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 432);
            yield "\">";
            yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "mtoImpVenta", [], "any", false, false, false, 432));
            yield "</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    ";
            // line 434
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "details", [], "any", false, false, false, 434));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["detail"]) {
                // line 435
                yield "    <cac:InvoiceLine>
        <cbc:ID>";
                // line 436
                yield CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 436);
                yield "</cbc:ID>
        <cbc:InvoicedQuantity unitCode=\"";
                // line 437
                yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "unidad", [], "any", false, false, false, 437);
                yield "\">";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "cantidad", [], "any", false, false, false, 437);
                yield "</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID=\"";
                // line 438
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 438);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoValorVenta", [], "any", false, false, false, 438));
                yield "</cbc:LineExtensionAmount>
        <cac:PricingReference>
            ";
                // line 440
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoValorGratuito", [], "any", false, false, false, 440)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 441
                    yield "            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID=\"";
                    // line 442
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 442);
                    yield "\">";
                    yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoValorGratuito", [], "any", false, false, false, 442), 10);
                    yield "</cbc:PriceAmount>
                <cbc:PriceTypeCode>02</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
            ";
                } else {
                    // line 446
                    yield "            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID=\"";
                    // line 447
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 447);
                    yield "\">";
                    yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoPrecioUnitario", [], "any", false, false, false, 447), 10);
                    yield "</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
            ";
                }
                // line 451
                yield "        </cac:PricingReference>
        ";
                // line 452
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "cargos", [], "any", false, false, false, 452)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 453
                    yield "        ";
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "cargos", [], "any", false, false, false, 453));
                    foreach ($context['_seq'] as $context["_key"] => $context["cargo"]) {
                        // line 454
                        yield "        <cac:AllowanceCharge>
            <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
            <cbc:AllowanceChargeReasonCode>";
                        // line 456
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "codTipo", [], "any", false, false, false, 456);
                        yield "</cbc:AllowanceChargeReasonCode>
            <cbc:MultiplierFactorNumeric>";
                        // line 457
                        yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "factor", [], "any", false, false, false, 457), 5);
                        yield "</cbc:MultiplierFactorNumeric>
            <cbc:Amount currencyID=\"";
                        // line 458
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 458);
                        yield "\">";
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "monto", [], "any", false, false, false, 458);
                        yield "</cbc:Amount>
            <cbc:BaseAmount currencyID=\"";
                        // line 459
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 459);
                        yield "\">";
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["cargo"], "montoBase", [], "any", false, false, false, 459);
                        yield "</cbc:BaseAmount>
        </cac:AllowanceCharge>
        ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['cargo'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 462
                    yield "        ";
                }
                // line 463
                yield "        ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "descuentos", [], "any", false, false, false, 463)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 464
                    yield "        ";
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "descuentos", [], "any", false, false, false, 464));
                    foreach ($context['_seq'] as $context["_key"] => $context["desc"]) {
                        // line 465
                        yield "        <cac:AllowanceCharge>
            <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
            <cbc:AllowanceChargeReasonCode>";
                        // line 467
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "codTipo", [], "any", false, false, false, 467);
                        yield "</cbc:AllowanceChargeReasonCode>
            <cbc:MultiplierFactorNumeric>";
                        // line 468
                        yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "factor", [], "any", false, false, false, 468), 5);
                        yield "</cbc:MultiplierFactorNumeric>
            <cbc:Amount currencyID=\"";
                        // line 469
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 469);
                        yield "\">";
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "monto", [], "any", false, false, false, 469);
                        yield "</cbc:Amount>
            <cbc:BaseAmount currencyID=\"";
                        // line 470
                        yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 470);
                        yield "\">";
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["desc"], "montoBase", [], "any", false, false, false, 470);
                        yield "</cbc:BaseAmount>
        </cac:AllowanceCharge>
        ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['desc'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 473
                    yield "        ";
                }
                // line 474
                yield "        <cac:TaxTotal>
            <cbc:TaxAmount currencyID=\"";
                // line 475
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 475);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "totalImpuestos", [], "any", false, false, false, 475));
                yield "</cbc:TaxAmount>
            ";
                // line 476
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "isc", [], "any", false, false, false, 476)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 477
                    yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                    // line 478
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 478);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoBaseIsc", [], "any", false, false, false, 478));
                    yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                    // line 479
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 479);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "isc", [], "any", false, false, false, 479));
                    yield "</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>";
                    // line 481
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "porcentajeIsc", [], "any", false, false, false, 481);
                    yield "</cbc:Percent>
                    <cbc:TierRange>";
                    // line 482
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "tipSisIsc", [], "any", false, false, false, 482);
                    yield "</cbc:TierRange>
                    <cac:TaxScheme>
                        <cbc:ID>2000</cbc:ID>
                        <cbc:Name>ISC</cbc:Name>
                        <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
            ";
                }
                // line 491
                yield "            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"";
                // line 492
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 492);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoBaseIgv", [], "any", false, false, false, 492));
                yield "</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"";
                // line 493
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 493);
                yield "\">";
                yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "igv", [], "any", false, false, false, 493));
                yield "</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>";
                // line 495
                yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "porcentajeIgv", [], "any", false, false, false, 495);
                yield "</cbc:Percent>
                    <cbc:TaxExemptionReasonCode>";
                // line 496
                yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "tipAfeIgv", [], "any", false, false, false, 496);
                yield "</cbc:TaxExemptionReasonCode>
                    ";
                // line 497
                $context["afect"] = Greenter\Xml\Filter\TributoFunction::getByAfectacion(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "tipAfeIgv", [], "any", false, false, false, 497));
                // line 498
                yield "                    <cac:TaxScheme>
                        <cbc:ID>";
                // line 499
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["afect"] ?? null), "id", [], "any", false, false, false, 499);
                yield "</cbc:ID>
                        <cbc:Name>";
                // line 500
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["afect"] ?? null), "name", [], "any", false, false, false, 500);
                yield "</cbc:Name>
                        <cbc:TaxTypeCode>";
                // line 501
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["afect"] ?? null), "code", [], "any", false, false, false, 501);
                yield "</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
            ";
                // line 505
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "otroTributo", [], "any", false, false, false, 505)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 506
                    yield "                <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID=\"";
                    // line 507
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 507);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoBaseOth", [], "any", false, false, false, 507));
                    yield "</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID=\"";
                    // line 508
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 508);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "otroTributo", [], "any", false, false, false, 508));
                    yield "</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cbc:Percent>";
                    // line 510
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "porcentajeOth", [], "any", false, false, false, 510);
                    yield "</cbc:Percent>
                        <cac:TaxScheme>
                            <cbc:ID>9999</cbc:ID>
                            <cbc:Name>OTROS</cbc:Name>
                            <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            ";
                }
                // line 519
                yield "            ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "icbper", [], "any", false, false, false, 519)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 520
                    yield "            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID=\"";
                    // line 521
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 521);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "icbper", [], "any", false, false, false, 521));
                    yield "</cbc:TaxAmount>
                <cbc:BaseUnitMeasure unitCode=\"NIU\">";
                    // line 522
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "cantidad", [], "any", false, false, false, 522);
                    yield "</cbc:BaseUnitMeasure>
                <cac:TaxCategory>
                    <cbc:PerUnitAmount currencyID=\"";
                    // line 524
                    yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 524);
                    yield "\">";
                    yield $this->env->getFilter('n_format')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "factorIcbper", [], "any", false, false, false, 524));
                    yield "</cbc:PerUnitAmount>
                    <cac:TaxScheme>
                        <cbc:ID>7152</cbc:ID>
                        <cbc:Name>ICBPER</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
            ";
                }
                // line 533
                yield "        </cac:TaxTotal>
        <cac:Item>
            <cbc:Description><![CDATA[";
                // line 535
                yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "descripcion", [], "any", false, false, false, 535);
                yield "]]></cbc:Description>
            ";
                // line 536
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProducto", [], "any", false, false, false, 536)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 537
                    yield "            <cac:SellersItemIdentification>
                <cbc:ID>";
                    // line 538
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProducto", [], "any", false, false, false, 538);
                    yield "</cbc:ID>
            </cac:SellersItemIdentification>
            ";
                }
                // line 541
                yield "            ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProdGS1", [], "any", false, false, false, 541)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 542
                    yield "            <cac:StandardItemIdentification>
                <cbc:ID>";
                    // line 543
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProdGS1", [], "any", false, false, false, 543);
                    yield "</cbc:ID>
            </cac:StandardItemIdentification>
            ";
                }
                // line 546
                yield "            ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProdSunat", [], "any", false, false, false, 546)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 547
                    yield "            <cac:CommodityClassification>
                <cbc:ItemClassificationCode>";
                    // line 548
                    yield CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "codProdSunat", [], "any", false, false, false, 548);
                    yield "</cbc:ItemClassificationCode>
            </cac:CommodityClassification>
            ";
                }
                // line 551
                yield "            ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "atributos", [], "any", false, false, false, 551)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 552
                    yield "                ";
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "atributos", [], "any", false, false, false, 552));
                    foreach ($context['_seq'] as $context["_key"] => $context["atr"]) {
                        // line 553
                        yield "                    <cac:AdditionalItemProperty >
                        <cbc:Name>";
                        // line 554
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "name", [], "any", false, false, false, 554);
                        yield "</cbc:Name>
                        <cbc:NameCode>";
                        // line 555
                        yield CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "code", [], "any", false, false, false, 555);
                        yield "</cbc:NameCode>
                        ";
                        // line 556
                        if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "value", [], "any", false, false, false, 556))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            // line 557
                            yield "                        <cbc:Value>";
                            yield CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "value", [], "any", false, false, false, 557);
                            yield "</cbc:Value>
                        ";
                        }
                        // line 559
                        yield "                        ";
                        if (((CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecInicio", [], "any", false, false, false, 559) || CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecFin", [], "any", false, false, false, 559)) || CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "duracion", [], "any", false, false, false, 559))) {
                            // line 560
                            yield "                            <cac:UsabilityPeriod>
                                ";
                            // line 561
                            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecInicio", [], "any", false, false, false, 561)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                                // line 562
                                yield "                                <cbc:StartDate>";
                                yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecInicio", [], "any", false, false, false, 562), "Y-m-d");
                                yield "</cbc:StartDate>
                                ";
                            }
                            // line 564
                            yield "                                ";
                            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecFin", [], "any", false, false, false, 564)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                                // line 565
                                yield "                                <cbc:EndDate>";
                                yield $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "fecFin", [], "any", false, false, false, 565), "Y-m-d");
                                yield "</cbc:EndDate>
                                ";
                            }
                            // line 567
                            yield "                                ";
                            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "duracion", [], "any", false, false, false, 567)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                                // line 568
                                yield "                                <cbc:DurationMeasure unitCode=\"DAY\">";
                                yield CoreExtension::getAttribute($this->env, $this->source, $context["atr"], "duracion", [], "any", false, false, false, 568);
                                yield "</cbc:DurationMeasure>
                                ";
                            }
                            // line 570
                            yield "                            </cac:UsabilityPeriod>
                        ";
                        }
                        // line 572
                        yield "                    </cac:AdditionalItemProperty>
                ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['atr'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 574
                    yield "            ";
                }
                // line 575
                yield "        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID=\"";
                // line 577
                yield CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "tipoMoneda", [], "any", false, false, false, 577);
                yield "\">";
                yield $this->env->getFilter('n_format_limit')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, $context["detail"], "mtoValorUnitario", [], "any", false, false, false, 577), 10);
                yield "</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>
    ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['detail'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 581
            yield "</Invoice>
";
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        yield Twig\Extension\CoreExtension::spaceless($_v0);
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "invoice2.1.xml.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  1639 => 1,  1634 => 581,  1614 => 577,  1610 => 575,  1607 => 574,  1600 => 572,  1596 => 570,  1590 => 568,  1587 => 567,  1581 => 565,  1578 => 564,  1572 => 562,  1570 => 561,  1567 => 560,  1564 => 559,  1558 => 557,  1556 => 556,  1552 => 555,  1548 => 554,  1545 => 553,  1540 => 552,  1537 => 551,  1531 => 548,  1528 => 547,  1525 => 546,  1519 => 543,  1516 => 542,  1513 => 541,  1507 => 538,  1504 => 537,  1502 => 536,  1498 => 535,  1494 => 533,  1480 => 524,  1475 => 522,  1469 => 521,  1466 => 520,  1463 => 519,  1451 => 510,  1444 => 508,  1438 => 507,  1435 => 506,  1433 => 505,  1426 => 501,  1422 => 500,  1418 => 499,  1415 => 498,  1413 => 497,  1409 => 496,  1405 => 495,  1398 => 493,  1392 => 492,  1389 => 491,  1377 => 482,  1373 => 481,  1366 => 479,  1360 => 478,  1357 => 477,  1355 => 476,  1349 => 475,  1346 => 474,  1343 => 473,  1332 => 470,  1326 => 469,  1322 => 468,  1318 => 467,  1314 => 465,  1309 => 464,  1306 => 463,  1303 => 462,  1292 => 459,  1286 => 458,  1282 => 457,  1278 => 456,  1274 => 454,  1269 => 453,  1267 => 452,  1264 => 451,  1255 => 447,  1252 => 446,  1243 => 442,  1240 => 441,  1238 => 440,  1231 => 438,  1225 => 437,  1221 => 436,  1218 => 435,  1201 => 434,  1193 => 432,  1185 => 430,  1182 => 429,  1174 => 427,  1171 => 426,  1163 => 424,  1160 => 423,  1152 => 421,  1149 => 420,  1141 => 418,  1139 => 417,  1133 => 416,  1129 => 414,  1114 => 404,  1111 => 403,  1108 => 402,  1093 => 392,  1087 => 391,  1084 => 390,  1081 => 389,  1066 => 379,  1060 => 378,  1057 => 377,  1054 => 376,  1041 => 366,  1035 => 365,  1032 => 364,  1029 => 363,  1014 => 353,  1008 => 352,  1005 => 351,  1002 => 350,  989 => 340,  983 => 339,  980 => 338,  977 => 337,  964 => 327,  958 => 326,  955 => 325,  952 => 324,  937 => 314,  931 => 313,  928 => 312,  925 => 311,  910 => 301,  904 => 300,  901 => 299,  899 => 298,  893 => 297,  890 => 296,  884 => 293,  880 => 292,  876 => 291,  872 => 290,  868 => 288,  865 => 287,  862 => 286,  859 => 285,  848 => 282,  841 => 281,  835 => 279,  833 => 278,  829 => 277,  825 => 275,  820 => 274,  817 => 273,  814 => 272,  803 => 269,  797 => 268,  793 => 267,  789 => 266,  785 => 264,  780 => 263,  777 => 262,  774 => 261,  755 => 258,  751 => 257,  748 => 256,  730 => 255,  727 => 254,  724 => 253,  707 => 250,  701 => 249,  697 => 248,  693 => 246,  675 => 245,  672 => 244,  668 => 242,  660 => 240,  658 => 239,  654 => 238,  650 => 236,  647 => 235,  641 => 232,  637 => 230,  634 => 229,  628 => 226,  624 => 225,  620 => 224,  612 => 219,  607 => 217,  603 => 215,  600 => 214,  597 => 213,  588 => 207,  582 => 204,  577 => 202,  573 => 201,  568 => 200,  562 => 198,  560 => 197,  556 => 196,  551 => 193,  548 => 192,  545 => 191,  540 => 188,  536 => 186,  530 => 184,  527 => 183,  521 => 181,  519 => 180,  516 => 179,  514 => 178,  511 => 177,  504 => 173,  498 => 170,  495 => 169,  489 => 167,  487 => 166,  484 => 165,  481 => 164,  479 => 163,  475 => 162,  467 => 159,  462 => 156,  459 => 155,  457 => 154,  453 => 152,  449 => 150,  443 => 148,  440 => 147,  434 => 145,  432 => 144,  429 => 143,  427 => 142,  424 => 141,  417 => 137,  411 => 134,  408 => 133,  402 => 131,  400 => 130,  397 => 129,  394 => 128,  392 => 127,  388 => 126,  380 => 123,  375 => 120,  373 => 119,  369 => 117,  365 => 115,  359 => 113,  356 => 112,  350 => 110,  348 => 109,  345 => 108,  343 => 107,  336 => 103,  330 => 100,  325 => 98,  321 => 97,  316 => 96,  310 => 94,  308 => 93,  304 => 92,  300 => 91,  297 => 90,  295 => 89,  291 => 88,  288 => 87,  282 => 84,  279 => 83,  277 => 82,  272 => 80,  257 => 68,  251 => 65,  245 => 62,  242 => 61,  239 => 60,  220 => 55,  214 => 52,  210 => 51,  206 => 50,  203 => 49,  185 => 48,  182 => 47,  179 => 46,  170 => 43,  166 => 42,  163 => 41,  158 => 40,  155 => 39,  152 => 38,  143 => 35,  139 => 34,  136 => 33,  131 => 32,  128 => 31,  122 => 28,  119 => 27,  117 => 26,  112 => 25,  106 => 23,  103 => 22,  92 => 20,  88 => 19,  81 => 18,  75 => 16,  73 => 15,  69 => 14,  65 => 13,  59 => 12,  55 => 10,  53 => 9,  44 => 2,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "invoice2.1.xml.twig", "E:\\tukifac\\tukifac_premium\\facturador_lycet\\vendor\\greenter\\xml\\src\\Xml\\Templates\\invoice2.1.xml.twig");
    }
}
