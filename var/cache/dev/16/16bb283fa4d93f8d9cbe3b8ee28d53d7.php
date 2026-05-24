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

/* invoice.html.twig */
class __TwigTemplate_7fe152534d66d1520a1164572113b37d extends Template
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
        yield "<html>
<head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <style type=\"text/css\">
        ";
        // line 5
        yield from $this->load("assets/style.css", 5)->unwrap()->yield($context);
        // line 6
        yield "    </style>
</head>
<body class=\"white-bg\">
";
        // line 9
        $context["cp"] = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 9, $this->source); })()), "company", [], "any", false, false, false, 9);
        // line 10
        $context["isInvoice"] = CoreExtension::inFilter(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 10, $this->source); })()), "tipoDoc", [], "any", false, false, false, 10), ["01", "03"]);
        // line 11
        $context["isNota"] = CoreExtension::inFilter(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 11, $this->source); })()), "tipoDoc", [], "any", false, false, false, 11), ["07", "08"]);
        // line 12
        $context["isAnticipo"] = (CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "totalAnticipos", [], "any", true, true, false, 12) && (CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 12, $this->source); })()), "totalAnticipos", [], "any", false, false, false, 12) > 0));
        // line 13
        $context["name"] = $this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 13, $this->source); })()), "tipoDoc", [], "any", false, false, false, 13), "01");
        // line 14
        yield "<table width=\"100%\">
    <tbody><tr>
        <td style=\"padding:30px; !important\">
            <table width=\"100%\" height=\"200px\" border=\"0\" aling=\"center\" cellpadding=\"0\" cellspacing=\"0\">
                <tbody><tr>
                    <td width=\"50%\" height=\"90\" align=\"center\">
                        <span><img src=\"";
        // line 20
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\ImageFilter')->toBase64(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 20, $this->source); })()), "system", [], "any", false, false, false, 20), "logo", [], "any", false, false, false, 20)), "html", null, true);
        yield "\" height=\"80\" style=\"text-align:center\" border=\"0\"></span>
                    </td>
                    <td width=\"5%\" height=\"40\" align=\"center\"></td>
                    <td width=\"45%\" rowspan=\"2\" valign=\"bottom\" style=\"padding-left:0\">
                        <div class=\"tabla_borde\">
                            <table width=\"100%\" border=\"0\" height=\"200\" cellpadding=\"6\" cellspacing=\"0\">
                                <tbody><tr>
                                    <td align=\"center\">
                                        <span style=\"font-family:Tahoma, Geneva, sans-serif; font-size:29px\" text-align=\"center\">";
        // line 28
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["name"]) || array_key_exists("name", $context) ? $context["name"] : (function () { throw new RuntimeError('Variable "name" does not exist.', 28, $this->source); })()), "html", null, true);
        yield "</span>
                                        <br>
                                        <span style=\"font-family:Tahoma, Geneva, sans-serif; font-size:19px\" text-align=\"center\">E L E C T R Ó N I C A</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align=\"center\">
                                        <span style=\"font-size:15px\" text-align=\"center\">R.U.C.: ";
        // line 35
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["cp"]) || array_key_exists("cp", $context) ? $context["cp"] : (function () { throw new RuntimeError('Variable "cp" does not exist.', 35, $this->source); })()), "ruc", [], "any", false, false, false, 35), "html", null, true);
        yield "</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align=\"center\">
                                        <span style=\"font-size:24px\">";
        // line 40
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 40, $this->source); })()), "serie", [], "any", false, false, false, 40), "html", null, true);
        yield "-";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 40, $this->source); })()), "correlativo", [], "any", false, false, false, 40), "html", null, true);
        yield "</span>
                                    </td>
                                </tr>
                                </tbody></table>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td valign=\"bottom\" style=\"padding-left:0\">
                        <div class=\"tabla_borde\">
                            <table width=\"96%\" height=\"100%\" border=\"0\" border-radius=\"\" cellpadding=\"9\" cellspacing=\"0\">
                                <tbody><tr>
                                    <td align=\"center\">
                                        <strong><span style=\"font-size:15px\">";
        // line 53
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["cp"]) || array_key_exists("cp", $context) ? $context["cp"] : (function () { throw new RuntimeError('Variable "cp" does not exist.', 53, $this->source); })()), "razonSocial", [], "any", false, false, false, 53), "html", null, true);
        yield "</span></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td align=\"left\">
                                        <strong>Dirección: </strong>";
        // line 58
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["cp"]) || array_key_exists("cp", $context) ? $context["cp"] : (function () { throw new RuntimeError('Variable "cp" does not exist.', 58, $this->source); })()), "address", [], "any", false, false, false, 58), "direccion", [], "any", false, false, false, 58), "html", null, true);
        yield "
                                    </td>
                                </tr>
                                <tr>
                                    <td align=\"left\">
                                        ";
        // line 63
        yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 63, $this->source); })()), "user", [], "any", false, false, false, 63), "header", [], "any", false, false, false, 63);
        yield "
                                    </td>
                                </tr>
                                </tbody></table>
                        </div>
                    </td>
                </tr>
                </tbody></table>
            <div class=\"tabla_borde\">
                ";
        // line 72
        $context["cl"] = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 72, $this->source); })()), "client", [], "any", false, false, false, 72);
        // line 73
        yield "                <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                    <tbody><tr>
                        <td width=\"60%\" align=\"left\"><strong>Razón Social:</strong>  ";
        // line 75
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["cl"]) || array_key_exists("cl", $context) ? $context["cl"] : (function () { throw new RuntimeError('Variable "cl" does not exist.', 75, $this->source); })()), "rznSocial", [], "any", false, false, false, 75), "html", null, true);
        yield "</td>
                        <td width=\"40%\" align=\"left\"><strong>";
        // line 76
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, (isset($context["cl"]) || array_key_exists("cl", $context) ? $context["cl"] : (function () { throw new RuntimeError('Variable "cl" does not exist.', 76, $this->source); })()), "tipoDoc", [], "any", false, false, false, 76), "06"), "html", null, true);
        yield ":</strong>  ";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["cl"]) || array_key_exists("cl", $context) ? $context["cl"] : (function () { throw new RuntimeError('Variable "cl" does not exist.', 76, $this->source); })()), "numDoc", [], "any", false, false, false, 76), "html", null, true);
        yield "</td>
                    </tr>
                    <tr>
                        <td width=\"60%\" align=\"left\">
                            <strong>Fecha Emisión: </strong>  ";
        // line 80
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 80, $this->source); })()), "fechaEmision", [], "any", false, false, false, 80), "d/m/Y"), "html", null, true);
        yield "
                            ";
        // line 81
        if (($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 81, $this->source); })()), "fechaEmision", [], "any", false, false, false, 81), "H:i:s") != "00:00:00")) {
            yield " ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 81, $this->source); })()), "fechaEmision", [], "any", false, false, false, 81), "H:i:s"), "html", null, true);
            yield " ";
        }
        // line 82
        yield "                            ";
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "fecVencimiento", [], "any", true, true, false, 82) && CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 82, $this->source); })()), "fecVencimiento", [], "any", false, false, false, 82))) {
            // line 83
            yield "                            <br><br><strong>Fecha Vencimiento: </strong>  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 83, $this->source); })()), "fecVencimiento", [], "any", false, false, false, 83), "d/m/Y"), "html", null, true);
            yield "
                            ";
        }
        // line 85
        yield "                        </td>
                        <td width=\"40%\" align=\"left\"><strong>Dirección: </strong>  ";
        // line 86
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["cl"]) || array_key_exists("cl", $context) ? $context["cl"] : (function () { throw new RuntimeError('Variable "cl" does not exist.', 86, $this->source); })()), "address", [], "any", false, false, false, 86)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["cl"]) || array_key_exists("cl", $context) ? $context["cl"] : (function () { throw new RuntimeError('Variable "cl" does not exist.', 86, $this->source); })()), "address", [], "any", false, false, false, 86), "direccion", [], "any", false, false, false, 86), "html", null, true);
        }
        yield "</td>
                    </tr>
                    ";
        // line 88
        if ((($tmp = (isset($context["isNota"]) || array_key_exists("isNota", $context) ? $context["isNota"] : (function () { throw new RuntimeError('Variable "isNota" does not exist.', 88, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 89
            yield "                    <tr>
                        <td width=\"60%\" align=\"left\"><strong>Tipo Doc. Ref.: </strong>  ";
            // line 90
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 90, $this->source); })()), "tipDocAfectado", [], "any", false, false, false, 90), "01"), "html", null, true);
            yield "</td>
                        <td width=\"40%\" align=\"left\"><strong>Documento Ref.: </strong>  ";
            // line 91
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 91, $this->source); })()), "numDocfectado", [], "any", false, false, false, 91), "html", null, true);
            yield "</td>
                    </tr>
                    ";
        }
        // line 94
        yield "                    <tr>
                        <td width=\"60%\" align=\"left\"><strong>Tipo Moneda: </strong>  ";
        // line 95
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 95, $this->source); })()), "tipoMoneda", [], "any", false, false, false, 95), "021"), "html", null, true);
        yield " </td>
                        <td width=\"40%\" align=\"left\">";
        // line 96
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["doc"] ?? null), "compra", [], "any", true, true, false, 96) && CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 96, $this->source); })()), "compra", [], "any", false, false, false, 96))) {
            yield "<strong>O/C: </strong>  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 96, $this->source); })()), "compra", [], "any", false, false, false, 96), "html", null, true);
        }
        yield "</td>
                    </tr>
                    ";
        // line 98
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 98, $this->source); })()), "guias", [], "any", false, false, false, 98)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 99
            yield "                    <tr>
                        <td width=\"60%\" align=\"left\"><strong>Guias: </strong>
                        ";
            // line 101
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 101, $this->source); })()), "guias", [], "any", false, false, false, 101));
            foreach ($context['_seq'] as $context["_key"] => $context["guia"]) {
                // line 102
                yield "                            ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["guia"], "nroDoc", [], "any", false, false, false, 102), "html", null, true);
                yield "&nbsp;&nbsp;
                        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['guia'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 103
            yield "</td>
                        <td width=\"40%\"></td>
                    </tr>
                    ";
        }
        // line 107
        yield "                    </tbody></table>
            </div><br>
            ";
        // line 109
        $context["moneda"] = $this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 109, $this->source); })()), "tipoMoneda", [], "any", false, false, false, 109), "02");
        // line 110
        yield "            <div class=\"tabla_borde\">
                <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                    <tbody>
                        <tr>
                            <td align=\"center\" class=\"bold\">Cantidad</td>
                            <td align=\"center\" class=\"bold\">Código</td>
                            <td align=\"center\" class=\"bold\">Descripción</td>
                            <td align=\"center\" class=\"bold\">Valor Unitario</td>
                            <td align=\"center\" class=\"bold\">Valor Total</td>
                        </tr>
                        ";
        // line 120
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 120, $this->source); })()), "details", [], "any", false, false, false, 120));
        foreach ($context['_seq'] as $context["_key"] => $context["det"]) {
            // line 121
            yield "                        <tr class=\"border_top\">
                            <td align=\"center\">
                                ";
            // line 123
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "cantidad", [], "any", false, false, false, 123)), "html", null, true);
            yield "
                                ";
            // line 124
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "unidad", [], "any", false, false, false, 124), "html", null, true);
            yield "
                            </td>
                            <td align=\"center\">
                                ";
            // line 127
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "codProducto", [], "any", false, false, false, 127), "html", null, true);
            yield "
                            </td>
                            <td align=\"center\" width=\"300px\">
                                <span>";
            // line 130
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "descripcion", [], "any", false, false, false, 130), "html", null, true);
            yield "</span><br>
                            </td>
                            <td align=\"center\">
                                ";
            // line 133
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 133, $this->source); })()), "html", null, true);
            yield "
                                ";
            // line 134
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "mtoValorUnitario", [], "any", false, false, false, 134)), "html", null, true);
            yield "
                            </td>
                            <td align=\"center\">
                                ";
            // line 137
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 137, $this->source); })()), "html", null, true);
            yield "
                                ";
            // line 138
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, $context["det"], "mtoValorVenta", [], "any", false, false, false, 138)), "html", null, true);
            yield "
                            </td>
                        </tr>
                        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['det'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 142
        yield "                    </tbody>
                </table></div>
            <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                <tbody><tr>
                    <td width=\"50%\" valign=\"top\">
                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                            <tbody>
                            <tr>
                                <td colspan=\"4\">
                                    <br>
                                    <br>
                                    <span style=\"font-family:Tahoma, Geneva, sans-serif; font-size:12px\" text-align=\"center\"><strong>";
        // line 153
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\ResolveFilter')->getValueLegend(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 153, $this->source); })()), "legends", [], "any", false, false, false, 153), "1000"), "html", null, true);
        yield "</strong></span>
                                    <br>
                                    <br>
                                    <strong>Información Adicional</strong>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                            <tbody>
                            <tr class=\"border_top\">
                                <td width=\"30%\" style=\"font-size: 10px;\">
                                    LEYENDA:
                                </td>
                                <td width=\"70%\" style=\"font-size: 10px;\">
                                    <p>
                                        ";
        // line 169
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 169, $this->source); })()), "legends", [], "any", false, false, false, 169));
        foreach ($context['_seq'] as $context["_key"] => $context["leg"]) {
            // line 170
            yield "                                        ";
            if ((CoreExtension::getAttribute($this->env, $this->source, $context["leg"], "code", [], "any", false, false, false, 170) != "1000")) {
                // line 171
                yield "                                            ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["leg"], "value", [], "any", false, false, false, 171), "html", null, true);
                yield "<br>
                                        ";
            }
            // line 173
            yield "                                        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['leg'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 174
        yield "                                    </p>
                                </td>
                            </tr>
                            ";
        // line 177
        if (((isset($context["isInvoice"]) || array_key_exists("isInvoice", $context) ? $context["isInvoice"] : (function () { throw new RuntimeError('Variable "isInvoice" does not exist.', 177, $this->source); })()) && CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 177, $this->source); })()), "detraccion", [], "any", false, false, false, 177))) {
            // line 178
            yield "                            <tr class=\"border_top\">
                                <td width=\"30%\" style=\"font-size: 10px;\">
                                    TIPO DE DETRACCIÓN:
                                </td>
                                <td width=\"70%\" style=\"font-size: 10px;\">
                                    ";
            // line 183
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 183, $this->source); })()), "detraccion", [], "any", false, false, false, 183), "codBienDetraccion", [], "any", false, false, false, 183), "html", null, true);
            yield " - ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 183, $this->source); })()), "detraccion", [], "any", false, false, false, 183), "codBienDetraccion", [], "any", false, false, false, 183), "54"), "html", null, true);
            yield "
                                </td>
                            </tr>
                            <tr>
                                <td width=\"30%\" style=\"font-size: 10px;\">
                                    MEDIO DE PAGO:
                                </td>
                                <td width=\"70%\" style=\"font-size: 10px;\">
                                    ";
            // line 191
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 191, $this->source); })()), "detraccion", [], "any", false, false, false, 191), "codMedioPago", [], "any", false, false, false, 191), "html", null, true);
            yield " - ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 191, $this->source); })()), "detraccion", [], "any", false, false, false, 191), "codMedioPago", [], "any", false, false, false, 191), "59"), "html", null, true);
            yield "
                                </td>
                            </tr>
                            <tr>
                                <td width=\"30%\" style=\"font-size: 10px;\">
                                    N° CTA. BANCO NAC:
                                </td>
                                <td width=\"70%\" style=\"font-size: 10px;\">
                                    ";
            // line 199
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 199, $this->source); })()), "detraccion", [], "any", false, false, false, 199), "ctaBanco", [], "any", false, false, false, 199), "html", null, true);
            yield "
                                </td>
                            </tr>
                            ";
        }
        // line 203
        yield "                            ";
        if ((($tmp = (isset($context["isNota"]) || array_key_exists("isNota", $context) ? $context["isNota"] : (function () { throw new RuntimeError('Variable "isNota" does not exist.', 203, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 204
            yield "                            <tr class=\"border_top\">
                                <td width=\"30%\" style=\"font-size: 10px;\">
                                    MOTIVO DE EMISIÓN:
                                </td>
                                <td width=\"70%\" style=\"font-size: 10px;\">
                                    ";
            // line 209
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 209, $this->source); })()), "desMotivo", [], "any", false, false, false, 209), "html", null, true);
            yield "
                                </td>
                            </tr>
                            ";
        }
        // line 213
        yield "                            ";
        if (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["params"] ?? null), "user", [], "any", false, true, false, 213), "extras", [], "any", true, true, false, 213)) {
            // line 214
            yield "                                ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 214, $this->source); })()), "user", [], "any", false, false, false, 214), "extras", [], "any", false, false, false, 214));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 215
                yield "                                    <tr class=\"border_top\">
                                        <td width=\"30%\" style=\"font-size: 10px;\">
                                            ";
                // line 217
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(Twig\Extension\CoreExtension::upper($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "name", [], "any", false, false, false, 217)), "html", null, true);
                yield ":
                                        </td>
                                        <td width=\"70%\" style=\"font-size: 10px;\">
                                            ";
                // line 220
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "value", [], "any", false, false, false, 220), "html", null, true);
                yield "
                                        </td>
                                    </tr>
                                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 224
            yield "                            ";
        }
        // line 225
        yield "                            </tbody>
                        </table>
                        ";
        // line 227
        if ((($tmp = (isset($context["isAnticipo"]) || array_key_exists("isAnticipo", $context) ? $context["isAnticipo"] : (function () { throw new RuntimeError('Variable "isAnticipo" does not exist.', 227, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 228
            yield "                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                            <tbody>
                            <tr>
                                <td>
                                    <br>
                                    <strong>Anticipo</strong>
                                    <br>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" style=\"font-size: 10px;\">
                            <tbody>
                            <tr>
                                <td width=\"30%\"><b>Nro. Doc.</b></td>
                                <td width=\"70%\"><b>Total</b></td>
                            </tr>
                            ";
            // line 245
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 245, $this->source); })()), "anticipos", [], "any", false, false, false, 245));
            foreach ($context['_seq'] as $context["_key"] => $context["atp"]) {
                // line 246
                yield "                            <tr class=\"border_top\">
                                <td width=\"30%\">";
                // line 247
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["atp"], "nroDocRel", [], "any", false, false, false, 247), "html", null, true);
                yield "</td>
                                <td width=\"70%\">";
                // line 248
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 248, $this->source); })()), "html", null, true);
                yield " ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, $context["atp"], "total", [], "any", false, false, false, 248)), "html", null, true);
                yield "</td>
                            </tr>
                            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['atp'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 251
            yield "                            </tbody>
                        </table>
                        ";
        }
        // line 254
        yield "                        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 254, $this->source); })()), "cuotas", [], "any", false, false, false, 254)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 255
            yield "                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
                            <tbody>
                            <tr>
                                <td>
                                    <br>
                                    <strong>Cuotas</strong>
                                    <br>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" style=\"font-size: 10px;\">
                            <tbody>
                            <tr>
                                <td width=\"30%\"><b>Monto</b></td>
                                <td width=\"70%\"><b>Fecha Pago</b></td>
                            </tr>
                            ";
            // line 272
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 272, $this->source); })()), "cuotas", [], "any", false, false, false, 272));
            foreach ($context['_seq'] as $context["_key"] => $context["cuota"]) {
                // line 273
                yield "                            <tr class=\"border_top\">
                                <td width=\"30%\">";
                // line 274
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 274, $this->source); })()), "html", null, true);
                yield " ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "monto", [], "any", false, false, false, 274)), "html", null, true);
                yield "</td>
                                <td width=\"70%\">";
                // line 275
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, $context["cuota"], "fechaPago", [], "any", false, false, false, 275), "d/m/Y"), "html", null, true);
                yield "</td>
                            </tr>
                            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['cuota'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 278
            yield "                            </tbody>
                        </table>
                        ";
        }
        // line 281
        yield "                    </td>
                    <td width=\"50%\" valign=\"top\">
                        <br>
                        <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"table table-valores-totales\">
                            <tbody>
                            ";
        // line 286
        if ((($tmp = (isset($context["isAnticipo"]) || array_key_exists("isAnticipo", $context) ? $context["isAnticipo"] : (function () { throw new RuntimeError('Variable "isAnticipo" does not exist.', 286, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 287
            yield "                                <tr class=\"border_bottom\">
                                    <td align=\"right\"><strong>Total Anticipo:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 289
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 289, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 289, $this->source); })()), "totalAnticipos", [], "any", false, false, false, 289)), "html", null, true);
            yield "</span></td>
                                </tr>
                            ";
        }
        // line 292
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 292, $this->source); })()), "mtoOperGravadas", [], "any", false, false, false, 292)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 293
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Op. Gravadas:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 295
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 295, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 295, $this->source); })()), "mtoOperGravadas", [], "any", false, false, false, 295)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 298
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 298, $this->source); })()), "mtoOperInafectas", [], "any", false, false, false, 298)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 299
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Op. Inafectas:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 301
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 301, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 301, $this->source); })()), "mtoOperInafectas", [], "any", false, false, false, 301)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 304
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 304, $this->source); })()), "mtoOperExoneradas", [], "any", false, false, false, 304)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 305
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Op. Exoneradas:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 307
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 307, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 307, $this->source); })()), "mtoOperExoneradas", [], "any", false, false, false, 307)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 310
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 310, $this->source); })()), "mtoOperGratuitas", [], "any", false, false, false, 310)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 311
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Op. Gratuitas:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 313
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 313, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 313, $this->source); })()), "mtoOperGratuitas", [], "any", false, false, false, 313)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 316
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 316, $this->source); })()), "mtoOperExportacion", [], "any", false, false, false, 316)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 317
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Op. Exportación:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 319
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 319, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 319, $this->source); })()), "mtoOperExportacion", [], "any", false, false, false, 319)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 322
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 322, $this->source); })()), "mtoBaseIvap", [], "any", false, false, false, 322)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 323
            yield "                            <tr class=\"border_bottom\">
                                <td align=\"right\"><strong>Base IVAP:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 325
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 325, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 325, $this->source); })()), "mtoBaseIvap", [], "any", false, false, false, 325)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 328
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 328, $this->source); })()), "mtoIGVGratuitas", [], "any", false, false, false, 328)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 329
            yield "                            <tr>
                                <td align=\"right\"><strong>IGV Gratuito:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 331
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 331, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 331, $this->source); })()), "mtoIGVGratuitas", [], "any", false, false, false, 331)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 334
        yield "                            <tr>
                                <td align=\"right\"><strong>I.G.V.";
        // line 335
        if (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["params"] ?? null), "user", [], "any", false, true, false, 335), "numIGV", [], "any", true, true, false, 335)) {
            yield " ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 335, $this->source); })()), "user", [], "any", false, false, false, 335), "numIGV", [], "any", false, false, false, 335), "html", null, true);
            yield "%";
        }
        yield ":</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
        // line 336
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 336, $this->source); })()), "html", null, true);
        yield "  ";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 336, $this->source); })()), "mtoIGV", [], "any", false, false, false, 336)), "html", null, true);
        yield "</span></td>
                            </tr>
                            ";
        // line 338
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 338, $this->source); })()), "mtoIvap", [], "any", false, false, false, 338)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 339
            yield "                            <tr>
                                <td align=\"right\"><strong>IVAP:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 341
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 341, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 341, $this->source); })()), "mtoIvap", [], "any", false, false, false, 341)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 344
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 344, $this->source); })()), "icbper", [], "any", false, false, false, 344)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 345
            yield "                                <tr>
                                    <td align=\"right\"><strong>I.C.B.P.E.R.:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 347
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 347, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 347, $this->source); })()), "icbper", [], "any", false, false, false, 347)), "html", null, true);
            yield "</span></td>
                                </tr>
                            ";
        }
        // line 350
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 350, $this->source); })()), "mtoISC", [], "any", false, false, false, 350)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 351
            yield "                            <tr>
                                <td align=\"right\"><strong>I.S.C.:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 353
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 353, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 353, $this->source); })()), "mtoISC", [], "any", false, false, false, 353)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 356
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 356, $this->source); })()), "sumOtrosCargos", [], "any", false, false, false, 356)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 357
            yield "                                <tr>
                                    <td align=\"right\"><strong>Otros Cargos:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 359
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 359, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 359, $this->source); })()), "sumOtrosCargos", [], "any", false, false, false, 359)), "html", null, true);
            yield "</span></td>
                                </tr>
                            ";
        }
        // line 362
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 362, $this->source); })()), "mtoOtrosTributos", [], "any", false, false, false, 362)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 363
            yield "                                <tr>
                                    <td align=\"right\"><strong>Otros Tributos:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 365
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 365, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 365, $this->source); })()), "mtoOtrosTributos", [], "any", false, false, false, 365)), "html", null, true);
            yield "</span></td>
                                </tr>
                            ";
        }
        // line 368
        yield "                            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 368, $this->source); })()), "redondeo", [], "any", false, false, false, 368)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 369
            yield "                            <tr>
                                <td align=\"right\"><strong>Redondeo:</strong></td>
                                <td width=\"120\" align=\"right\"><span>";
            // line 371
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 371, $this->source); })()), "html", null, true);
            yield " ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 371, $this->source); })()), "redondeo", [], "any", false, false, false, 371)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 374
        yield "                            <tr>
                                <td align=\"right\"><strong>Precio Venta:</strong></td>
                                <td width=\"120\" align=\"right\"><span id=\"ride-importeTotal\" class=\"ride-importeTotal\">";
        // line 376
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 376, $this->source); })()), "html", null, true);
        yield "  ";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 376, $this->source); })()), "mtoImpVenta", [], "any", false, false, false, 376)), "html", null, true);
        yield "</span></td>
                            </tr>
                            ";
        // line 378
        if ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 378, $this->source); })()), "perception", [], "any", false, false, false, 378) && CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 378, $this->source); })()), "perception", [], "any", false, false, false, 378), "mto", [], "any", false, false, false, 378))) {
            // line 379
            yield "                                ";
            $context["perc"] = CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 379, $this->source); })()), "perception", [], "any", false, false, false, 379);
            // line 380
            yield "                                ";
            $context["soles"] = $this->env->getRuntime('Greenter\Report\Filter\DocumentFilter')->getValueCatalog("PEN", "02");
            // line 381
            yield "                                <tr>
                                    <td align=\"right\"><strong>Percepción:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 383
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["soles"]) || array_key_exists("soles", $context) ? $context["soles"] : (function () { throw new RuntimeError('Variable "soles" does not exist.', 383, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["perc"]) || array_key_exists("perc", $context) ? $context["perc"] : (function () { throw new RuntimeError('Variable "perc" does not exist.', 383, $this->source); })()), "mto", [], "any", false, false, false, 383)), "html", null, true);
            yield "</span></td>
                                </tr>
                                <tr>
                                    <td align=\"right\"><strong>Total a Pagar:</strong></td>
                                    <td width=\"120\" align=\"right\"><span>";
            // line 387
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["soles"]) || array_key_exists("soles", $context) ? $context["soles"] : (function () { throw new RuntimeError('Variable "soles" does not exist.', 387, $this->source); })()), "html", null, true);
            yield " ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, (isset($context["perc"]) || array_key_exists("perc", $context) ? $context["perc"] : (function () { throw new RuntimeError('Variable "perc" does not exist.', 387, $this->source); })()), "mtoTotal", [], "any", false, false, false, 387)), "html", null, true);
            yield "</span></td>
                                </tr>
                            ";
        }
        // line 390
        yield "                            ";
        if (((isset($context["isInvoice"]) || array_key_exists("isInvoice", $context) ? $context["isInvoice"] : (function () { throw new RuntimeError('Variable "isInvoice" does not exist.', 390, $this->source); })()) && CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 390, $this->source); })()), "detraccion", [], "any", false, false, false, 390))) {
            // line 391
            yield "                            <tr>
                                <td align=\"right\"><strong>Detracción (";
            // line 392
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 392, $this->source); })()), "detraccion", [], "any", false, false, false, 392), "percent", [], "any", false, false, false, 392), "html", null, true);
            yield "%):</strong></td>
                                <td width=\"120\" align=\"right\"><span id=\"ride-importeTotal\" class=\"ride-importeTotal\">";
            // line 393
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["moneda"]) || array_key_exists("moneda", $context) ? $context["moneda"] : (function () { throw new RuntimeError('Variable "moneda" does not exist.', 393, $this->source); })()), "html", null, true);
            yield "  ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\FormatFilter')->number(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 393, $this->source); })()), "detraccion", [], "any", false, false, false, 393), "mount", [], "any", false, false, false, 393)), "html", null, true);
            yield "</span></td>
                            </tr>
                            ";
        }
        // line 396
        yield "                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody></table>
            <br>
            <br>
            ";
        // line 403
        if ((array_key_exists("max_items", $context) && (Twig\Extension\CoreExtension::length($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, (isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 403, $this->source); })()), "details", [], "any", false, false, false, 403)) > (isset($context["max_items"]) || array_key_exists("max_items", $context) ? $context["max_items"] : (function () { throw new RuntimeError('Variable "max_items" does not exist.', 403, $this->source); })())))) {
            // line 404
            yield "                <div style=\"page-break-after:always;\"></div>
            ";
        }
        // line 406
        yield "            <div>
                <hr style=\"display: block; height: 1px; border: 0; border-top: 1px solid #666; margin: 20px 0; padding: 0;\">
                <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                    <tbody>
                    <tr>
                        <td width=\"85%\">
                            <blockquote>
                                ";
        // line 413
        if (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["params"] ?? null), "user", [], "any", false, true, false, 413), "footer", [], "any", true, true, false, 413)) {
            // line 414
            yield "                                    ";
            yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 414, $this->source); })()), "user", [], "any", false, false, false, 414), "footer", [], "any", false, false, false, 414);
            yield "
                                ";
        }
        // line 416
        yield "                                ";
        if ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["params"] ?? null), "system", [], "any", false, true, false, 416), "hash", [], "any", true, true, false, 416) && CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 416, $this->source); })()), "system", [], "any", false, false, false, 416), "hash", [], "any", false, false, false, 416))) {
            // line 417
            yield "                                    <strong>Resumen:</strong>   ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["params"]) || array_key_exists("params", $context) ? $context["params"] : (function () { throw new RuntimeError('Variable "params" does not exist.', 417, $this->source); })()), "system", [], "any", false, false, false, 417), "hash", [], "any", false, false, false, 417), "html", null, true);
            yield "<br>
                                ";
        }
        // line 419
        yield "                                <span>Representación Impresa de la ";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["name"]) || array_key_exists("name", $context) ? $context["name"] : (function () { throw new RuntimeError('Variable "name" does not exist.', 419, $this->source); })()), "html", null, true);
        yield " ELECTRÓNICA.</span>
                            </blockquote>
                        </td>
                        <td width=\"15%\" align=\"right\">
                            <img src=\"";
        // line 423
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Greenter\Report\Filter\ImageFilter')->toBase64($this->env->getRuntime('Greenter\Report\Render\QrRender')->getImage((isset($context["doc"]) || array_key_exists("doc", $context) ? $context["doc"] : (function () { throw new RuntimeError('Variable "doc" does not exist.', 423, $this->source); })())), "svg+xml"), "html", null, true);
        yield "\" alt=\"Qr Image\">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
    </tbody></table>
</body></html>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "invoice.html.twig";
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
        return array (  910 => 423,  902 => 419,  896 => 417,  893 => 416,  887 => 414,  885 => 413,  876 => 406,  872 => 404,  870 => 403,  861 => 396,  853 => 393,  849 => 392,  846 => 391,  843 => 390,  835 => 387,  826 => 383,  822 => 381,  819 => 380,  816 => 379,  814 => 378,  807 => 376,  803 => 374,  795 => 371,  791 => 369,  788 => 368,  780 => 365,  776 => 363,  773 => 362,  765 => 359,  761 => 357,  758 => 356,  750 => 353,  746 => 351,  743 => 350,  735 => 347,  731 => 345,  728 => 344,  720 => 341,  716 => 339,  714 => 338,  707 => 336,  699 => 335,  696 => 334,  688 => 331,  684 => 329,  681 => 328,  673 => 325,  669 => 323,  666 => 322,  658 => 319,  654 => 317,  651 => 316,  643 => 313,  639 => 311,  636 => 310,  628 => 307,  624 => 305,  621 => 304,  613 => 301,  609 => 299,  606 => 298,  598 => 295,  594 => 293,  591 => 292,  583 => 289,  579 => 287,  577 => 286,  570 => 281,  565 => 278,  556 => 275,  550 => 274,  547 => 273,  543 => 272,  524 => 255,  521 => 254,  516 => 251,  505 => 248,  501 => 247,  498 => 246,  494 => 245,  475 => 228,  473 => 227,  469 => 225,  466 => 224,  456 => 220,  450 => 217,  446 => 215,  441 => 214,  438 => 213,  431 => 209,  424 => 204,  421 => 203,  414 => 199,  401 => 191,  388 => 183,  381 => 178,  379 => 177,  374 => 174,  368 => 173,  362 => 171,  359 => 170,  355 => 169,  336 => 153,  323 => 142,  313 => 138,  309 => 137,  303 => 134,  299 => 133,  293 => 130,  287 => 127,  281 => 124,  277 => 123,  273 => 121,  269 => 120,  257 => 110,  255 => 109,  251 => 107,  245 => 103,  236 => 102,  232 => 101,  228 => 99,  226 => 98,  218 => 96,  214 => 95,  211 => 94,  205 => 91,  201 => 90,  198 => 89,  196 => 88,  189 => 86,  186 => 85,  180 => 83,  177 => 82,  171 => 81,  167 => 80,  158 => 76,  154 => 75,  150 => 73,  148 => 72,  136 => 63,  128 => 58,  120 => 53,  102 => 40,  94 => 35,  84 => 28,  73 => 20,  65 => 14,  63 => 13,  61 => 12,  59 => 11,  57 => 10,  55 => 9,  50 => 6,  48 => 5,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "invoice.html.twig", "E:\\tukifac\\tukifac_premium\\facturador_lycet\\vendor\\greenter\\report\\src\\Report\\Templates\\invoice.html.twig");
    }
}
