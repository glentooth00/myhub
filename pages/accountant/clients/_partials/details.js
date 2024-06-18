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


  app.updateAndSync = function(event, options = {}) {
    console.log('app.updateAndSync()', event, options);
    const actionButton = event.currentTarget;
    const title = 'Updating client...';
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
  Utils.removeFrom(app.el.toolbar, '.tool');


  tippy('[data-tippy-content]', { allowHTML: true });

});