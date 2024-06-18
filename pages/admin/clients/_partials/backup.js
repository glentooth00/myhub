F1.deferred.push(function initClientBackupPageView(app) {

  console.log('initClientBackupPageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;  
  const Utils = F1.lib.Utils;  


  const form = Utils.getEl('restore-form');
  const backupIcon = Utils.getEl('backup-icon', app.el.appMain);
  const restoreIcon = Utils.getEl('restore-icon', app.el.appMain);


  function afterClose() { app.redirect(); }

  function spin(el) { if (el) el.classList.add('spin'); }
  function spinOff(el) { if (el) el.classList.remove('spin'); }


  app.backupData = function(event) {
    console.log('app.backupData()', event);
    spin(backupIcon);
    Ajax.post(location.href, { action: 'backupData' })
    .then(function (resp) {
      spinOff(backupIcon);
      if (!resp.success) return app.handleAjaxError(resp, 'client.backupData');     
      console.log('client.backupData.success:', resp);
      const message = resp.message || 'No message from server.';
      app.toast({ message, afterClose, theme: 'success' });
    })
    .catch(function (resp) {
      spinOff(backupIcon);
      return app.handleAjaxError(resp, 'client.backupData');
    });
  };


  form.onsubmit = function (event) {
    event.preventDefault();
    console.log('app.restoreData(), onSubmit start...', event);
    spin(restoreIcon);
    /* submit form */
    Ajax.submit( form, { extraData: { action: 'restoreData' } } )
    .then(function (resp) {
      spinOff(restoreIcon);
      if (!resp.success) return app.handleAjaxError(resp, 'client.restoreData');      
      console.log('client.restoreData.success:', resp);
      const message = resp.message || 'No message from server.';
      app.toast({ message, afterClose, theme: 'success' });
    })
    .catch(function (resp) {
      spinOff(restoreIcon);
      return app.handleAjaxError(resp, 'client.restoreData');
    });
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  tippy('[data-tippy-content]', { allowHTML: true });

});  