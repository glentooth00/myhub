/* global F1 */

F1.deferred.push(function initAdminBatchProcessClientsView(app) {

  console.log('initAdminBatchProcessClientsView()');

  const Ajax = F1.lib.Ajax;
  // const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  // const CsvExporter = F1.lib.CsvExporter;

  const clientsForm = Utils.getEl('clientsForm');
  const clientsContainer = document.querySelector('#clients tbody');
  const resultsContainer = document.querySelector('#results tbody');
  const templateHtml = document.getElementById('client-row-template').innerHTML;


  function renderClients(data) {
    clientsContainer.innerHTML = ''; // Clear existing entries
    data.forEach((client, index) => {
      let row = templateHtml
        .replace('{client.id}', client.id || '-')
        .replace('{client.id}', client.id || '-')
        .replace('{index}', index + 1)
        .replace('{client.client_id}', client.client_id || '-')
        .replace('{client.name}', client.name || '-')
        .replace('{client.status}', client.status || '-');
      clientsContainer.innerHTML += row;
    });
  }  


  /* NOTE: The following is a good example of updating the page with Ajax and HTML templates. */

  app.fetchClients = function(event)
  {
    event.preventDefault();

    const query = event.target.previousElementSibling.value;
    const url = window.location.href;

    console.log('fetchClients()', url, query);

    Ajax.post(url, { action: 'fetchClients', query })
      .then(response => {
        console.log(response);
        if (response.success) {
          renderClients(response.data);
        }
      })
      .catch(error => {
        console.error(error);
      });
  };


  app.toggleSelectAll = function(event) {
    const checked = event.target.checked;
    console.log('Toggle Select All...', event.target, checked);
    const clientsForm = Utils.getEl('clientsForm');
    const checkboxes = clientsForm.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = checked);
  };


  /* NOTE: The following is a good example of using Ajax with async/await. */

  app.updateClientState = async function() {
    const selectedClients = Array.from(clientsForm.querySelectorAll('tbody input[type="checkbox"]:checked'))
                                .map(cb => cb.value);
    console.log('Updating client state for:', selectedClients);

    if (selectedClients.length) resultsContainer.innerHTML = ''; // Clear existing entries

    // We can't use forEach with async/await!
    for (let clientId of selectedClients) {
      try {
        const response = await Ajax.post(window.location.href, { action: 'updateClientState', clientId });
        console.log('Response for client', clientId, ':', response);
        resultsContainer.innerHTML += `<tr>
          <td>${response.success ? response.message : 'Failed'}</td>
          <td>${new Date().toLocaleTimeString()}</td>
        </tr>`;
      } catch (error) {
        console.error('Error updating client state for', clientId, ':', error);
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
