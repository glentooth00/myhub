/* global F1 */

F1.deferred.push(function initAdminBatchProcessTradesView(app) {

  console.log('initAdminBatchProcessTradesView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const CsvExporter = F1.lib.CsvExporter;

  const tradesForm = Utils.getEl('tradesForm');
  const tradesContainer = document.querySelector('#trades tbody');
  const resultsContainer = document.querySelector('#results tbody');
  const templateHtml = document.getElementById('trade-row-template').innerHTML;


  function renderTrades(data) {
    tradesContainer.innerHTML = ''; // Clear existing entries
    data.forEach((trade, index) => {
      let row = templateHtml
        .replace('{trade.id}', trade.id || '-')
        .replace('{trade.id}', trade.id || '-')
        .replace('{index}', index + 1)
        .replace('{trade.trade_id}', trade.trade_id || '-')
        .replace('{trade.client_name}', trade.client_name || '-')
        .replace('{trade.sda_fia}', trade.sda_fia || '-')
        .replace('{trade.date}', trade.date || '-')
        .replace('{trade.zar_sent}', trade.zar_sent ? `R${trade.zar_sent}` : '-')
        .replace('{trade.usd_bought}', trade.usd_bought ? `$${trade.usd_bought}` : '-')
        .replace('{trade.forex_rate}', trade.forex_rate ? `R${trade.forex_rate}` : '-')
        .replace('{trade.zar_profit}', trade.zar_profit ? `R${trade.zar_profit}` : '-')
        .replace('{trade.percent_return}', trade.percent_return ? `${trade.percent_return}%` : '-')
        .replace('{trade.amount_covered}', trade.amount_covered ? `R${trade.amount_covered}` : '-')
        .replace('{trade.allocated_pins}', trade.allocated_pins || '-');
      tradesContainer.innerHTML += row;
    });
  }  


  /* NOTE: The following is a good example of updating the page with Ajax and HTML templates. */

  app.fetchTrades = function(event)
  {
    event.preventDefault();

    const query = event.target.previousElementSibling.value;
    const url = window.location.href;

    console.log('fetchTrades()', url, query);

    Ajax.post(url, { action: 'fetchTrades', query })
      .then(response => {
        console.log(response);
        if (response.success) {
          renderTrades(response.data);
        }
      })
      .catch(error => {
        console.error(error);
      });
  }


  app.toggleSelectAll = function(event) {
    const checked = event.target.checked;
    console.log('Toggle Select All...', event.target, checked);
    const tradesForm = Utils.getEl('tradesForm');
    const checkboxes = tradesForm.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = checked);
  };


  /* NOTE: The following is a good example of using Ajax with async/await. */

  app.fixR500Issue = async function() {
    const selectedTrades = Array.from(tradesForm.querySelectorAll('tbody input[type="checkbox"]:checked'))
                                .map(cb => cb.value);
    console.log('Fixing R500 Issue with trades:', selectedTrades);

    if (selectedTrades.length) resultsContainer.innerHTML = ''; // Clear existing entries

    // We can't use forEach with async/await!
    for (let tradeId of selectedTrades) {
      try {
        const response = await Ajax.post(window.location.href, { action: 'fixR500Issue', tradeId });
        console.log('Response for trade', tradeId, ':', response);
        resultsContainer.innerHTML += `<tr>
          <td>${response.success ? response.message : 'Failed'}</td>
          <td>${new Date().toLocaleTimeString()}</td>
        </tr>`;
      } catch (error) {
        console.error('Error fixing R500 Issue for trade', tradeId, ':', error);
        resultsContainer.innerHTML += `<tr>
          <td>Fatal Error</td>
          <td>${new Date().toLocaleTimeString()}</td>
        </tr>`;
      }
    }
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  tippy('[data-tippy-content]', { allowHTML: true });

});
