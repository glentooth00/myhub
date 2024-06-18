F1.deferred.push(function initClientDetailsPageView(app) {

  console.log('initClientDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const CsvExporter = F1.lib.CsvExporter;


  const deletedAt = Utils.getEl('deletedAt');
  const status = Utils.getEl('status');


  function spin(el) { if (el) el.classList.add('spin'); }
  function spinOff(el) { if (el) el.classList.remove('spin'); }


  app.exportToCsv = function(tableName) {
   const tableElement = document.querySelector(`.related-${tableName} > table`);
   const tableExporter = new CsvExporter('TableElement', tableElement);
   const timestamp = new Date().toISOString().slice(0, -5).replace(/[-:]/g, '');
   const data = tableExporter.dataParser.getData();
   const headers = data[0];
   const rows = data.slice(1, data.length - 1); // Drop header and summary rows
   console.log('app.exportToCsv()', {tableName, data, headers, rows});
   tableExporter.downloadCSV(`${tableName}_export_${timestamp}.csv`, [headers, ...rows]);
  }


  app.undeleteClient = function() {
    console.log('undeleteClient()');
    const id = new URLSearchParams(window.location.search).get('id');
    if ( ! confirm(`Undelete Client ID: ${id}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log('Undelete Client start...');

    Ajax.post( location.href, { action: 'unSoftDeleteClient' } )
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) return app.handleAjaxError(resp, 'undelete.tcc');
        app.redirect(resp.goto, 'undelete.tcc.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'undelete.tcc'));
  };


  app.deleteClient = function() { 
    console.log('deleteClient()');

    const deleteType = trashTip;
    const id = new URLSearchParams(window.location.search).get('id');
    if ( ! confirm(`${deleteType} Client ID: ${id}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log(deleteType + ' Client start...');

    Ajax.post( location.href, { action: 'deleteClient', deleteType } )
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'delete.tcc');
        app.redirect(resp.goto, 'delete.tcc.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'delete.tcc'));
  };


  app.sendStatementLink = function(event) {
    console.log('app.sendStatementLink()', event);
    const actionButton = event.currentTarget;
    const year = new Date().getFullYear();
    const title = 'Sending statement link...';
    spin(actionButton);
    const syncPopup = new Popup({
      title,
      content: 'This can take a minute. Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
    });    
    syncPopup.show();
    const payload = { action: 'sendStatementLink', year };
    Ajax.post(location.href, payload)
    .then(function (resp) {
      syncPopup.close();
      spinOff(actionButton);
      if (!resp.success) return app.handleAjaxError(resp, 'client.sendStatementLink');
      console.log('client.sendStatementLink.success', resp);
      app.toast({ message: resp.message });
    })
    .catch(function (resp) {
      syncPopup.close();
      spinOff(actionButton);
      return app.handleAjaxError(resp, 'client.sendStatementLink');
    });
  };


  app.updateAndSync = function(event, options = {}) {
    console.log('app.updateAndSync()', event, options);
    let title;
    const year = options.year || new Date().getFullYear();
    const actionButton = event.currentTarget;
    if ( options.redo ) {
      if ( ! confirm(`Redo all Client allocations for ${year}.  Are you sure!?`) ) return;
      title = `Redoing all Client allocations for ${year}...`;
    } else {
      title = 'Updating client...';
    }
    spin(actionButton);
    const syncPopup = new Popup({
      title,
      content: 'This can take a minute. Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
    });    
    syncPopup.show();
    const payload = { action: 'updateAndSync' };
    if ( options.year ) payload.year = options.year;
    if ( options.redo ) payload.redoAllocations = true;
    if ( options.noSync ) payload.noSync = true;
    Ajax.post(location.href, payload)
    .then(function (resp) {
      syncPopup.close();
      spinOff(actionButton);
      if (!resp.success) return app.handleAjaxError(resp, 'client.updateAndSync');
      console.log('client.updateAndSync.success', resp);
      app.toast({ message: resp.message, afterClose: () => app.redirect() });
    })
    .catch(function (resp) {
      syncPopup.close();
      spinOff(actionButton);
      return app.handleAjaxError(resp, 'client.updateAndSync');
    });
  };


  /* top nav */
  if (deletedAt) status.classList.add('deleted');
  if (status.children[1].innerText === 'Inactive') { status.classList.add('inactive'); }
  const trashTip = deletedAt ? 'Permanently Delete' : 'Soft Delete';
  const trashClass = 'icon btn-round tool' + ( deletedAt ? ' permanent-delete' : '' );
  const trashIcon = Utils.newEl('button', trashClass, { type: 'button' });
  trashIcon.innerHTML = `<i class="fa fa-trash-o fa-lg"></i>`;
  trashIcon.setAttribute('data-tippy-content', trashTip);
  trashIcon.onclick = app.deleteClient;

  Utils.removeFrom(app.el.toolbar, '.tool');
  app.el.toolbar.prepend(trashIcon);


  tippy('[data-tippy-content]', { allowHTML: true });

});