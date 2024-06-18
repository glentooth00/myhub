/* global F1 */

F1.deferred.push(function initAdminBatchProcessTradesView(app) {

  console.log('initAdminBatchProcessTradesView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const CsvExporter = F1.lib.CsvExporter;

  const checklist = Utils.getEl('checklist');
  const resultsTable = Utils.getEl('resultsTable');
  const resultsTableBody = resultsTable.querySelector('tbody');


  function setButtonState(button, busy, title) {
    if (busy) {
      button.disabled = true;
      title = title || 'Please wait...';
      button.classList.add('btn-busy');
      button.classList.add('btn-danger');
      button.classList.remove('btn-secondary');
      button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + title;
    } else {
      button.disabled = false;
      title = title || 'Process Selected';
      button.classList.remove('btn-busy');
      button.classList.add('btn-secondary');
      button.classList.remove('btn-danger');
      button.innerHTML = title;
    }
  }

  function checkItem(li, checked) {
    const cb = li.children[0].children[0];
    cb.checked = checked ?? (!cb.checked);
  }

  function itemChecked(li) {
    const cb = li.children[0].children[0];
    return cb.checked;
  }

  function itemValue(li) {
    const cb = li.children[0].children[0];
    return cb.value;
  }

  function itemValueIn(li, list) {
    const cb = li.children[0].children[0];
    return list.includes(cb.value);
  }

  function clearResultsTableBodyRows() {
    console.log('clearResultsTableBodyRows()');
    resultsTableBody.innerHTML = '';
  }

  function generateResultTableRow(result) {
    const row = document.createElement('tr');
    const clientUidCell = document.createElement('td');
    const clientCell = document.createElement('td');
    const operationCell = document.createElement('td');
    const resultCell = document.createElement('td');
    const timeCell = document.createElement('td');
    clientUidCell.innerHTML = result.client_uid;
    clientCell.innerHTML = result.client_name;
    operationCell.innerHTML = result.operation;
    resultCell.innerHTML = result.result;
    timeCell.innerHTML = result.time;
    row.appendChild(clientCell);
    row.appendChild(clientUidCell);
    row.appendChild(operationCell);
    row.appendChild(resultCell);
    row.appendChild(timeCell);
    return row;
  }


  /* alert */
  app.alert = function ({ message = '', title = '', theme = '', ...rest }) {
    const popup = new F1.modules.Popup({
      theme,
      type: 'alert',
      content: message,
      title: title || utils.capitalizeFirstChar(theme),
      buttons: [{ text: 'OK', className: 'btn--primary' }],
      size: message.length > 255 ? 'large' : message.length > 140 ? 'medium' : 'small',
      position: 'center',
      ...rest
    });
    popup.show();
  };


  app.exportToCsv = function(tableName) {
   const tableElement = document.getElementById(tableName);
   const tableExporter = new F1.modules.CsvExporter('TableElement', tableElement);
   const timestamp = new Date().toISOString().slice(0, -5).replace(/[-:]/g, '');
   const data = tableExporter.dataParser.getData();
   const headers = data[0];
   const rows = data.slice(1, data.length - 1); // Drop header and summary rows
   console.log('app.exportToCsv()', {tableName, data, headers, rows});
   tableExporter.downloadCSV(`${tableName}_export_${timestamp}.csv`, [headers, ...rows]);
  }


  app.selectAll = function(e) {
    const checked = e.target.checked;
    console.log('Select All...', e.target, checked);
    Array.from(checklist.children).forEach(li => checkItem(li, checked));
  };


  app.toggleUnselectedVisiblity = function(e) {
    const hideUnselected = e.target;
    const checked = hideUnselected.checked;
    console.log('Toggle Hide Unselected...', e.target, checked);
    const currentlyUnselected = Array.from(checklist.children).filter(li => !itemChecked(li));
    currentlyUnselected.forEach(li => li.hidden = checked);
  }


  app.selectFromList = function(e) {
    console.log('Select From List...', e.target);
    const customListTextarea = document.getElementById('customSelectList');
    const customClientUids = customListTextarea.value.split('\n').map(uid => uid.trim());
    console.log('customClientUids:', customClientUids);
    const checklistItems = Array.from(checklist.children);
    const unselectExisting = document.getElementById('unselectExisting').checked;
    const hideUnselected = document.getElementById('hideUnselected').checked;

    if (unselectExisting) {
      console.log('unselecting existing...');
      checklistItems.forEach(li => checkItem(li, false));
    }

    checklistItems.forEach(li => {
      const itemChecked = itemValueIn(li, customClientUids);
      if (hideUnselected) { li.hidden = !itemChecked;  }
      else li.hidden = false;
      checkItem(li, itemChecked);
    });

    const toast = new F1.modules.Popup({
      type: 'toast',
      title: 'Excellet!',
      content: 'Your selection was applied successfully.<br>Scoll down if you can\'t see them.',
      theme: 'success',
      timer: 2500,
    });
    toast.show();
  };


  app.startProcessing = async function(e) {

    console.log('startProcessing, event:', e);

    const action = e.target.value.trim();

    document.getElementById('selectAll').checked = false;

    setButtonState(e.target, 'busy');

    app.stopProcessingFlag = false;

    clearResultsTableBodyRows();


    // Wait a while for the UI to update...
    await new Promise(resolve => setTimeout(resolve, 100));

    const form = document.getElementById('selectedClientsForm');
    const selected = Array.from(form.elements['clients[]']).filter(cb => cb.checked);
    console.log('selected:', selected.map(cb => cb.value));


    for (const clientItem of selected) {

      console.log('client:', clientItem.value, clientItem.dataset.client);

      try {

        if ( app.stopProcessingFlag ) {
          setButtonState(e.target, false, action);
          app.stopProcessingFlag = false;
          return console.log('stopProcessingFlag is set, aborting...');
        }

        const data = {
          action,
          client_uid: clientItem.value,
          client_name: clientItem.dataset.client,
        };

        const req = await ajax.post(window.location.href, data, { format: 'promise' });
        const resp = await req.json();
        console.log('ajax resp:', resp);

        if (!resp.success) return alert(resp?.message || resp);

        const tr = generateResultTableRow(resp.result);

        resultsTableBody.appendChild(tr);

      } catch (err) {
        console.error('Error processing client:', err);
        alert(err?.message || err);
      }

    } // for

    setButtonState(e.target, false, action);

  }


  app.stopProcessing = function(e, action) {
    console.log('stopProcessing, event:', e);
    setButtonState(e.target, false, action);
    app.stopProcessingFlag = true;
  }


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  tippy('[data-tippy-content]', { allowHTML: true });

});
