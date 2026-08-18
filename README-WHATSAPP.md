# WhatsApp Cloud API en Go Creative

Este módulo está pensado para PHP 8.1+, MySQL 5.7/MariaDB 10.4 y hosting
compartido con HTTPS y cURL. La automatización incluida no consume una API de
IA de pago: utiliza un flujo de calificación y una base de conocimiento editable.

Documentación oficial: [primeros pasos con WhatsApp Cloud API](https://developers.facebook.com/documentation/business-messaging/whatsapp/get-started)
y [versiones de Graph API](https://developers.facebook.com/docs/graph-api/changelog/versions/).

## 1. Preparar la base de datos

En una instalación existente, abre phpMyAdmin, selecciona la base real e
importa `database/migrations/2026_08_15_whatsapp.sql`. En una instalación nueva
basta importar `database/gocreative.sql`.

## 2. Preparar Meta

1. En Meta for Developers crea o utiliza una aplicación de tipo Business.
2. Agrega el producto WhatsApp y conecta la cuenta comercial.
3. Crea un usuario del sistema en Business Manager y genera un token permanente
   con permisos `whatsapp_business_messaging` y `whatsapp_business_management`.
4. Obtén el **Phone Number ID**, el **WhatsApp Business Account ID** y el
   **App Secret**. No copies el número telefónico en el campo Phone Number ID.
5. En Webhooks configura `https://gocreative.cl/whatsapp/webhook.php`, utiliza el
   mismo token de verificación que guardarás en el panel y suscribe `messages`.
6. Antes de activar producción, agrega el número destinatario de prueba o
   completa la verificación comercial exigida por Meta.

Si el número ya se usa en WhatsApp Business, confirma en el alta de Meta que la
cuenta sea elegible para **Coexistence**. No migres ni elimines el número sin
verificar primero el historial y el respaldo de la aplicación.

## 3. Configurar el sitio

Entra a **Panel → Configurar WhatsApp** y completa:

- Graph API: `v26.0` (editable para futuras versiones).
- ID del número y, opcionalmente, ID WABA.
- Token permanente, token de verificación y App Secret.
- Zona horaria, días y horario de atención.
- Textos de saludo, fuera de horario, derivación y mensaje no entendido.

Guarda y ejecuta **Probar conexión con Meta**. El panel debe mostrar el número y
el nombre verificado. Finalmente activa **Respuestas automáticas**.

También se puede configurar por variables del hosting:

```text
GC_WHATSAPP_ENABLED=true
GC_WHATSAPP_GRAPH_VERSION=v26.0
GC_WHATSAPP_PHONE_NUMBER_ID=...
GC_WHATSAPP_BUSINESS_ACCOUNT_ID=...
GC_WHATSAPP_ACCESS_TOKEN=...
GC_WHATSAPP_VERIFY_TOKEN=...
GC_WHATSAPP_APP_SECRET=...
GC_WHATSAPP_TIMEZONE=America/Santiago
GC_WHATSAPP_BUSINESS_START=09:00
GC_WHATSAPP_BUSINESS_END=18:00
```

## 4. Probar de extremo a extremo

1. Escribe desde un teléfono distinto al WhatsApp conectado.
2. Confirma que el saludo llegue una sola vez y completa las cuatro preguntas.
3. Revisa **Panel → WhatsApp → Oportunidades**.
4. Abre la conversación, toma el control y envía una respuesta.
5. Escribe `asesor`, `salir` e `iniciar` para probar derivación, baja y reactivación.
6. En Meta revisa que el webhook responda HTTP 200 y no tenga reintentos.

## Seguridad y operación

- El webhook rechaza cargas sin `X-Hub-Signature-256` válida.
- `meta_message_id` es único; los reintentos de Meta no duplican respuestas.
- Los secretos no se renderizan de vuelta y los archivos locales están
  excluidos de Git.
- El panel aplica autenticación, permisos, CSRF, auditoría y límite de 24 horas
  para mensajes libres enviados por asesores.
- Para iniciar una conversación fuera de esa ventana se necesita una plantilla
  aprobada por Meta. Este módulo no la envía automáticamente para evitar cargos
  o comunicaciones sin consentimiento.
- Revisa periódicamente la vigencia del token y actualiza la versión de Graph
  API antes de su fecha de retiro.
