/* global F1 */

F1.deferred.push(function initAdminProcessClientRolloversView(app) {

  console.log('initAdminProcessClientRolloversView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const CsvExporter = F1.lib.CsvExporter;

  function clientRowUID(tr) {
    return tr.children[1].innerHTML;
  }

  function clientRowDesc(tr) {
    return tr.children[2].innerHTML;
  }

  function findClientRow(client) {
    return Array.from(clients).find(tr => tr.id === client.li.dataset.clientRow);
  }

  function itemUID(li) {
    return li.children[0].children[1].children[0].innerText;
  }

  function itemDesc(li) {
    return li.children[0].children[1].innerText;
  }

  function checkItem(li, checked) {
    const cb = li.children[0].children[0];
    cb.checked = checked ?? (!cb.checked);
  }

  function groupItem(li, group) {
    const cb = li.children[0].children[0];
    li.classList.toggle('grouped', group);
  }

  function hideIfUnselected(li, hide) {
    const cb = li.children[0].children[0];
    if (hide) {
      li.classList.toggle('hidden', !cb.checked);
    } else {
      li.classList.remove('hidden');
    }
  }

  function itemChecked(li) {
    const cb = li.children[0].children[0];
    return cb.checked;
  }

  function createClientListItem(clientRow) {
    const itemDesc = clientRowDesc(clientRow);
    const uid = clientRowUID(clientRow); 
    const itemHTML = `
      <label class="flex-row flex-gap align-center">
        <input type="checkbox" name="selected[]">
        <span>${itemDesc} <i><small>${uid}</small></i></span>
      </label>
    `;
    const li = Utils.newEl('li', '', { innerHTML: itemHTML });
    li.dataset.clientRow = clientRow.id;
    return li;
  }


  /* main */

  // Let's fetch all the rows in table id="clients-report"'s tbody.
  // The 2nd td in each tbody tr contains the client's UID.
  // The last td in each row has a string No or Yes.

  const reportTable = app.currentPage.find('#report-table');
  const selectAll = app.currentPage.find('#selectAll');
  const checklist = app.currentPage.find('#checklist');
  const clients = reportTable.querySelectorAll('tbody > tr');

  console.log({ reportTable, selectAll, checklist, clients })


  checklist.innerHTML = '';

  let count = 0;
  const maxItems = 2000;
  Array.from(clients).forEach(clientRow => {
    if (count > maxItems) return false;
    const li = createClientListItem(clientRow);
    checklist.append(li);
    count++;
  })


  app.exportToCsv = function(tableName) {
   const tableExporter = new CsvExporter('TableElement', reportTable);
   const timestamp = new Date().toISOString().slice(0, -5).replace(/[-:]/g, '');
   const data = tableExporter.dataParser.getData();
   const headers = data[0];
   const rows = data.slice(1, data.length - 1); // Drop header and summary rows
   console.log('app.exportToCsv()', {tableName, data, headers, rows});
   tableExporter.downloadCSV(`${tableName}_export_${timestamp}.csv`, [headers, ...rows]);
  }


  app.selectAllT5s = function(e) {
    const checked = e.target.checked;
    console.log('Select All T5s...', e.target, checked);
    Array.from(checklist.children).forEach(li => checkItem(li, false));
    const t5s = Array.from(clients).filter(tr => tr.classList.contains('t5'));
    console.log('t5s:', t5s);
    // Check all checklist items with the same data-client-row the same as the t5 row's id
    let t5Count = 0;
    t5s.forEach(t5 => {
      const li = Array.from(checklist.children).find(li => li.dataset.clientRow === t5.id);
      checkItem(li, checked);
      t5Count++;
    });
    console.log('t5Count:', t5Count);
    // Append t5Count to the e.target parent
    const parent = e.target.parentElement;
    const countSpan = parent.querySelector('.count');
    if (countSpan) countSpan.innerText = t5Count;
    else parent.append(Utils.newEl('span', 'count', { innerText: t5Count }));
  };


  app.selectAll = function(e) {
    const checked = e.target.checked;
    console.log('Select All...', e.target, checked);
    Array.from(checklist.children).forEach(li => checkItem(li, checked));
  };


  app.hideUnselected = function(e) {
    const checked = e.target.checked;
    console.log('Hide unselected...', e.target, checked);
    Array.from(checklist.children).forEach(li => hideIfUnselected(li, checked));
  };


  app.onChecklistClick = function(e) {
    const checked = e.target.checked;
    console.log('On checklist click...', e);
    if (e.target?.type === 'checkbox') {
      if (e.ctrlKey) {
        e.preventDefault = true;
        e.stopImmediatePropagation = true;
        e.target.classList.toggle('grouped');
      } else {
        Array.from(checklist.children).forEach(li => groupItem(li, false));
      }
    }
  };


  app.startProcessing = async function(e) {
    console.log('startProcessing, event:', e);
    e.target.hidden = true;
    e.target.nextElementSibling.hidden = false;

    document.getElementById('selectAll').checked = false;

    const updateMode = document.getElementById('selectHighlighted').checked;

    app.stopProcessingFlag = false;

    // Wait a while for the UI to update...
    await new Promise(resolve => setTimeout(resolve, 0));

    const selected = Array.from(checklist.children).filter(li => itemChecked(li));
    const clients = selected.map(li => { return { uid: itemUID(li), name: itemDesc(li), li }; });

    console.log('selected:', selected);
    console.log('clients:', clients);

    for (const client of clients) {
      console.log('client:', client);
      try {
        if ( app.stopProcessingFlag ) {
          e.target.hidden = null;
          e.target.nextElementSibling.hidden = true;
          return console.log('stopProcessingFlag is set, aborting...');
        }
        const data = {
          action: 'processClient',
          uid: client.uid,
          name: client.name,
          mode: updateMode ? 'update' : 'test',
        };
        const req = await Ajax.post(window.location, data, { responseType: 'promise' });
        const resp = await req.json();
        console.log('ajax resp:', resp);

        const row = findClientRow(client);
        console.log('client row:', row);

        if (!resp.success) return alert(resp?.message||resp);

        if (updateMode) {
          // e.target.hidden = null;
          // e.target.nextElementSibling.hidden = true;
          row.classList.add('processed');         
          console.log('updateMode = on, skipping feedack... We\'re DONE!');
          checkItem(client.li, false);
          continue;
        }

        const rolloverAmount = resp.data.stats.rollover_amount;
        const totalCoverAmount = resp.data.stats.rollins_amount + resp.data.stats.tccs_amount;
        const totalCoverRemaining = totalCoverAmount - resp.data.stats.trades_fia_amount;
        const sdaMandRemaining = resp.data.annual.sda_mandate - resp.data.stats.trades_sda_amount;
        const fiaMandRemaining = resp.data.annual.fia_mandate - resp.data.stats.trades_fia_amount;
        const coverUnassigned = rolloverAmount ? totalCoverRemaining - rolloverAmount : 0;

        let col = 4;
        row.children[col++].innerHTML = resp.data.annual.trading_capital;
        row.children[col++].innerHTML = resp.data.annual.sda_mandate;
        row.children[col++].innerHTML = resp.data.annual.fia_mandate;
        row.children[col++].innerHTML = resp.data.stats.trades_sda_amount;
        row.children[col++].innerHTML = resp.data.stats.trades_fia_amount;
        row.children[col++].innerHTML = sdaMandRemaining;
        row.children[col++].innerHTML = fiaMandRemaining;
        row.children[col++].innerHTML = resp.data.stats.SDA;
        row.children[col++].innerHTML = resp.data.stats.FIA;
        row.children[col++].innerHTML = resp.data.stats.SFA;
        row.children[col++].innerHTML = resp.data.stats.trades;
        row.children[col++].innerHTML = resp.data.stats.trades_amount;
        row.children[col++].innerHTML = resp.data.stats.RIs;
        row.children[col++].innerHTML = resp.data.stats.TCCs;
        row.children[col++].innerHTML = resp.data.stats.rollins_amount;
        row.children[col++].innerHTML = resp.data.stats.tccs_amount;
        row.children[col++].innerHTML = totalCoverAmount;
        row.children[col++].innerHTML = totalCoverRemaining;
        row.children[col++].innerHTML = resp.data.stats.ROs;
        row.children[col++].innerHTML = resp.data.stats.rollover_amount;
        row.children[col++].innerHTML = coverUnassigned;

        const t1 = resp.data.stats.trades_w_cover_mismatch;
        const t2 = resp.data.stats.trades_w_alloc_mismatch;
        const t3 = totalCoverRemaining < 0 ? 1 : 0;
        const t4 = coverUnassigned > 0 ? 1 : 0;
        const t5 = coverUnassigned < 0 ? 1 : 0;

        row.children[col++].innerHTML = t1;
        row.children[col++].innerHTML = t2;
        row.children[col++].innerHTML = t3;
        row.children[col++].innerHTML = t4;
        row.children[col++].innerHTML = t5;

        row.classList.toggle('t5', ( t1 || t2 || t3 || t4 || t5 ) );
        row.classList.add('processed');

        checkItem(client.li, false);
      } catch (err) {
        console.error('Error processing client:', err);
        alert(err?.message||err);
      }
    }

    e.target.hidden = null;
    e.target.nextElementSibling.hidden = true;

  }


  app.stopProcessing = function(e) {
    console.log('stopProcessing, event:', e);
    e.target.previousElementSibling.hidden = null;
    e.target.hidden = true;
    app.stopProcessingFlag = true;
  }


  app.updateMandates = function(e) {
    Ajax.post(window.location, { action: 'updateMandates' })
    .then(resp => {
      console.log(resp);
      alert(resp?.message||resp);
    })
  }


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  tippy('[data-tippy-content]', { allowHTML: true });

});
