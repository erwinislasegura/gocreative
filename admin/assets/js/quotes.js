const quoteForm = document.querySelector('[data-quote-form]');

if (quoteForm) {
  const itemsContainer = quoteForm.querySelector('[data-quote-items]');
  const template = document.getElementById('quote-item-template');
  const catalog = document.getElementById('quote_catalog');
  const money = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });

  const recalculate = () => {
    let subtotal = 0;
    itemsContainer.querySelectorAll('[data-quote-item]').forEach((item) => {
      const quantity = Number(item.querySelector('[name="quantity[]"]')?.value || 0);
      const price = Number(item.querySelector('[name="unit_price[]"]')?.value || 0);
      subtotal += Math.max(0, quantity) * Math.max(0, price);
    });
    const discount = Number(document.getElementById('discount_amount')?.value || 0);
    const tax = Number(document.getElementById('tax_percent')?.value || 0);
    const net = Math.max(0, subtotal - Math.max(0, discount));
    const total = Math.round(net + (net * tax / 100));
    const output = quoteForm.querySelector('[data-quote-total]');
    if (output) output.textContent = money.format(total);
  };

  const bindItem = (item) => {
    item.querySelector('[data-remove-item]')?.addEventListener('click', () => {
      if (itemsContainer.querySelectorAll('[data-quote-item]').length > 1) item.remove();
      recalculate();
    });
    item.querySelectorAll('input, select, textarea').forEach((input) => input.addEventListener('input', recalculate));
  };

  const addItem = (data = {}) => {
    const fragment = template.content.cloneNode(true);
    const item = fragment.querySelector('[data-quote-item]');
    item.querySelector('[name="item_type[]"]').value = data.type || 'service';
    item.querySelector('[name="name[]"]').value = data.name || '';
    item.querySelector('[name="description[]"]').value = data.description || '';
    item.querySelector('[name="quantity[]"]').value = data.quantity || '1';
    item.querySelector('[name="unit_price[]"]').value = data.price || '0';
    bindItem(item);
    itemsContainer.appendChild(fragment);
    recalculate();
    item.querySelector('[name="name[]"]').focus();
  };

  itemsContainer.querySelectorAll('[data-quote-item]').forEach(bindItem);
  quoteForm.querySelector('[data-add-empty]')?.addEventListener('click', () => addItem());
  quoteForm.querySelector('[data-add-catalog]')?.addEventListener('click', () => {
    const option = catalog?.selectedOptions[0];
    if (!option || !option.value) return;
    addItem({ type: option.dataset.type, name: option.dataset.name, description: option.dataset.description, price: option.dataset.price });
    catalog.value = '';
  });
  document.getElementById('discount_amount')?.addEventListener('input', recalculate);
  document.getElementById('tax_percent')?.addEventListener('change', recalculate);
  recalculate();
}
