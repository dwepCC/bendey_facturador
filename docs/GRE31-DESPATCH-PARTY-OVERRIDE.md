# GRE31 — Override plantilla `cac:DespatchParty` (error SUNAT 3383)

## Motivo

SUNAT rechaza la GRE **Transportista (tipoDoc 31)** con código **3383**:

> Debe consignar el Numero de documento de identidad del Remitente

Greenter (vendor) genera el remitente en `cac:SellerSupplierParty` vía `doc.tercero`, pero **no incluye** el nodo obligatorio para tipo 31:

```
/DespatchAdvice/cac:Shipment/cac:Delivery/cac:Despatch/cac:DespatchParty/cac:PartyIdentification/cbc:ID
```

Referencia upstream pendiente de merge: [Greenter PR #236](https://github.com/thegreenter/greenter/pull/236).

## Archivo de override

| Archivo | Rol |
|---------|-----|
| `templates/greenter/xml/despatch2022.xml.twig` | Plantilla UBL 2.0 Despatch con bloque `DespatchParty` para GRE31 |
| `src/Greenter/Xml/Builder/DespatchBuilder.php` | Builder que usa ChainLoader (override → vendor) |
| `src/Greenter/Factory/XmlBuilderResolver.php` | Resuelve `DespatchBuilder` custom para `Despatch::class` |
| `src/Greenter/Api.php` / `src/Greenter/See.php` | Integración con emisión REST y SOAP legacy |

**No se modifica** `vendor/greenter/`.

## Bloque añadido (tipoDoc 31 + tercero)

Ubicación en el XML:

```
cac:Shipment → cac:Delivery → cac:Despatch → cac:DespatchParty
```

Datos tomados de `doc.tercero` (remitente): `tipoDoc`, `numDoc`, `rznSocial`.

## Mantenimiento

Al actualizar `greenter/xml`, comparar `vendor/greenter/xml/src/Xml/Templates/despatch2022.xml.twig` con este override y fusionar cambios upstream. El ChainLoader sigue usando el vendor como fallback para el resto de plantillas.

## Payload ERP

El backend Go debe enviar `tercero` en el JSON Despatch cuando `tipoDoc = 31`. Greenter ya mapea `tercero` → `SellerSupplierParty`; este override completa `DespatchParty`.
