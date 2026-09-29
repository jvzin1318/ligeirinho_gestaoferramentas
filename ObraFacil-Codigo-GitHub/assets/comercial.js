(() => {
 document.querySelectorAll('[data-company-form]').forEach(form => {
  const input=form.querySelector('[data-cnpj]'),button=form.querySelector('[data-cnpj-search]'),status=form.querySelector('[data-cnpj-status]'),type=form.querySelector('[data-client-type]');
  let controller=null,version=0;
  const normalize=()=>input.value.toUpperCase().replace(/[.\/\-\s]/g,'');
  const reset=()=>{version++;controller?.abort();status.textContent='Confira o documento e consulte novamente, se necessário.';};
  input.addEventListener('input',reset);type?.addEventListener('change',reset);
  button.addEventListener('click',async()=>{
   if(type&&type.value!=='PJ'){status.textContent='Selecione Empresa para consultar o CNPJ.';return;}
   const cnpj=normalize();if(!/^[0-9]{14}$/.test(cnpj)){status.textContent='A consulta automática aceita CNPJ numérico de 14 dígitos. Para CNPJ alfanumérico, preencha manualmente.';return;}
   controller?.abort();const current=++version;controller=new AbortController();const active=controller;
   const selectors=['name','email','phone','address'];const nodes=selectors.map(k=>form.querySelector('[data-company-'+k+']'));const initial=nodes.map(n=>n?.value);
   status.textContent='Consultando CNPJ…';const timeout=setTimeout(()=>active.abort(),10000);
   try{
    const response=await fetch('https://brasilapi.com.br/api/cnpj/v1/'+cnpj,{signal:active.signal,credentials:'omit',referrerPolicy:'no-referrer'});
    if(!response.ok)throw new Error('api');const data=await response.json();
    if(current!==version||normalize()!==cnpj)return;
    if(typeof data.razao_social!=='string')throw new Error('api');
    const address=[data.logradouro,data.numero,data.bairro,data.municipio,data.uf,data.cep].filter(Boolean).join(', ');
    const values=[data.razao_social,data.email||'',data.ddd_telefone_1||'',address];
    nodes.forEach((node,i)=>{if(node&&node.value===initial[i])node.value=String(values[i]).slice(0,node.maxLength>0?node.maxLength:255);});
    status.textContent='Dados encontrados. Confira e complete os campos antes de salvar.';
   }catch(error){if(current===version)status.textContent='Não foi possível consultar. Confira o CNPJ ou preencha os dados manualmente.';}
   finally{clearTimeout(timeout);}
  });
 });
 const form=document.querySelector('#order-form');if(form){const fields=[...form.querySelectorAll('[data-item-cents]')];const total=()=>{const cents=fields.reduce((sum,f)=>sum+Number(f.dataset.itemCents)*Math.max(0,Number(f.value)||0),0);document.querySelector('#cart-total').textContent=(cents/100).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});};fields.forEach(f=>f.addEventListener('input',total));total();}
})();

(() => {
 const search=document.querySelector('#cash-search-id');
 if(search){const rows=[...document.querySelectorAll('[data-cash-id]')],status=document.querySelector('#cash-search-status');const filter=()=>{const raw=search.value.trim(),valid=/^[0-9]+$/.test(raw),id=valid?String(Number(raw)):raw;let found=0;for(const row of rows){const match=raw===''||(valid&&row.dataset.cashId===id);row.hidden=!match;if(match)found++;}status.textContent=raw===''?'Todas as ferramentas disponíveis. Itens selecionados continuam no total.':(found?'Ferramenta encontrada. Informe a quantidade.':'ID não encontrado entre as ferramentas com estoque.');};search.addEventListener('input',filter);search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();filter();document.querySelector('[data-cash-id]:not([hidden]) input')?.focus();}});document.querySelector('#cash-search-clear').addEventListener('click',()=>{search.value='';filter();search.focus();});}
 const purchase=document.querySelector('#purchase-form');
 if(purchase){const supplier=purchase.querySelector('[name=fornecedor_id]'),rows=[...purchase.querySelectorAll('[data-supplier]')];const update=()=>{let total=0;for(const row of rows){const active=row.dataset.supplier===supplier.value;row.hidden=!active;const [q,c]=row.querySelectorAll('input');q.disabled=c.disabled=!active;if(active)total+=(Number(q.value)||0)*(Number(c.value.replace(',','.'))||0);}document.querySelector('#purchase-total').textContent=total.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});};supplier.addEventListener('change',update);purchase.addEventListener('input',update);update();}
 const returns=document.querySelector('#return-form');
 if(returns){const update=()=>{const exchange=returns.querySelector('[name=tipo]').value==='Troca';returns.querySelector('[data-exchange-items]').hidden=!exchange;let back=0,next=0;returns.querySelectorAll('[data-return-cents]').forEach(f=>back+=(Number(f.value)||0)*Number(f.dataset.returnCents));returns.querySelectorAll('[data-new-cents]').forEach(f=>{f.disabled=!exchange;if(exchange)next+=(Number(f.value)||0)*Number(f.dataset.newCents);});const money=n=>(n/100).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});document.querySelector('#return-total').textContent='Devolvido: '+money(back)+' · Novos itens: '+money(next)+' · Diferença: '+money(next-back);};returns.addEventListener('input',update);returns.addEventListener('change',update);update();}
})();
