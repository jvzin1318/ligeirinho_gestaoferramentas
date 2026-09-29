document.querySelectorAll('form[data-confirm]').forEach(form => {
 form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); });
});
const product = document.getElementById('sale-product');
const quantity = document.getElementById('sale-qty');
function updateTotal() {
 const option = product.selectedOptions[0];
 const price = Number(option?.dataset.price || 0);
 quantity.max = Math.min(10000, Number(option?.dataset.stock || 10000));
 const cents = Math.round(price * 100) * Math.max(0, Number(quantity.value) || 0);
 document.getElementById('sale-total').textContent = (cents / 100).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
}
if (product) { product.addEventListener('change', updateTotal); quantity.addEventListener('input', updateTotal); updateTotal(); }
document.getElementById('print-report')?.addEventListener('click', () => window.print());
