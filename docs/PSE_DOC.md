Utiliza esta API para firmar y enviar comprobantes electrónicos a SUNAT. Requiere el Token de Acceso de cada empresa.

Autenticación
Authorization: Bearer TOKEN_ACCESO
Cada empresa tiene su propio token_acceso. Lo encuentras al crear/editar una empresa en la sección "Credenciales API".
Firmar XML
POST
https://app.validapse.com/api/cpe/generar

{
  "nombre_archivo": "20123456789-01-F001-1",
  "contenido_archivo": "PD94bWwgdmVyc2lvbj0i..."
}
nombre_archivo: RUC-TIPO-SERIE-NUMERO (sin extensión .xml)
contenido_archivo: XML en Base64
Nota: Solo para usuarios en ambiente PRODUCCIÓN
Firmar y Enviar XML
POST
https://app.validapse.com/api/cpe/generarenviar

{
  "nombre_archivo": "20123456789-01-F001-1",
  "contenido_archivo": "PD94bWwgdmVyc2lvbj0i..."
}
Firma el XML y lo envía directamente a SUNAT/OSE en una sola operación.
Enviar XML (ya firmado)
POST
https://app.validapse.com/api/cpe/enviar

{
  "nombre_xml_firmado": "20123456789-01-F001-1",
  "contenido_xml_firmado": "PD94bWwgdmVyc2lvbj0i..."
}
Para enviar un XML que ya fue firmado previamente.
Guías de Remisión: Series que empiecen con T o V usan endpoint GRE.
Consultar / Recuperar CDR
GET
https://app.validapse.com/api/cpe/consultar/{nombre_archivo}

nombre_archivo: RUC-TIPO-SERIE-NUMERO (sin extensión .xml)
Recupera la Constancia de Recepción (CDR) de SUNAT.
Endpoints DEMO
Para usuarios en ambiente DEMO, usa los siguientes endpoints:
POST /api/cpe/generar-demo
POST /api/cpe/generarenviar-demo
POST /api/cpe/enviar-demo
GET  /api/cpe/consultar-demo/{nombre_archivo}
Respuesta Exitosa (ejemplo)
{
  "isSuccess": true,
  "estado": 200,
  "codigo_hash": "abc123...",
  "mensaje": "XML firmado correctamente",
  "xml": "PD94bWwgdmVyc2lvbj0i...",
  "cdr": "PD94bWwgdmVyc2lvbj0i...",
  "external_id": "sha256hash..."
}
Nota CDR: el campo `cdr` viene en **Base64** y contiene el XML **ApplicationResponse** de SUNAT (no un ZIP). El facturador lo empaqueta automáticamente en ZIP SUNAT estándar (`R-{RUC}-{TIPO}-{SERIE}-{NUM}.xml` dentro del archivo) al guardarlo y al descargarlo.
Respuesta Error (ejemplo)
{
  "isSuccess": false,
  "estado": 400,
  "message": "El ruc ingresado no se encuentra registrado",
  "errors": "El ruc ingresado no se encuentra registrado"
}