<?php
require __DIR__.'/app/bootstrap.php';
page('Pagamento em processamento',false);
echo '<section class="card"><p>Acompanhe o resultado no Mercado Pago. A loja confirma o pagamento pela consulta ao provedor; voltar a esta página não comprova pagamento.</p><p>Se você emitiu um boleto ou Pix, aguarde a confirmação após pagar.</p></section>';
endpage();
