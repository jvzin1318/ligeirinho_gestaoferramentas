<?php
// Configure no servidor. Nunca coloque tokens no JavaScript ou envie por mensagem.
return [
 'base_url'=>getenv('OBRA_BASE_URL') ?: '', // HTTPS da hospedagem PHP, sem barra final.
 'mp_token'=>getenv('MP_ACCESS_TOKEN') ?: '',
 'mp_webhook_secret'=>getenv('MP_WEBHOOK_SECRET') ?: '',
 'mp_collector_id'=>getenv('MP_COLLECTOR_ID') ?: '',
 'mp_test'=>getenv('MP_PRODUCTION') !== '1',
];
