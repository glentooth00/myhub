/* global F1 */

F1.deferred.push(function initTradesEditPageView(app) {

  console.log('initTradesEditPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;

  app.el.form = Utils.getEl('trade-form');

  app.el.form.onsubmit = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy(app.el.content);
    /* do some validation */
    const dateInput = Utils.getEl('date');
    if (!dateInput.value) {
      app.removeBusy(app.el.content);
      app.alert({ message: 'Trade Date is required.', theme: 'error' });
      dateInput.focus();
      return;
    }
    /* submit form */
    Ajax.submit(form, { extraData: { action: 'saveTrade' } })
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) {
          console.log('submit.trade.fail:', resp);
          let message = resp.errors || resp.message || resp;
          if ( typeof message == 'object' ) message = Object.values(message).join('<br>');
          app.alert({ message, theme: 'error' });
          return;
        }        
        console.log('submit.trade.success:', resp);
        if (resp.goto === 'back') history.back();
        else if (resp.goto) window.location.href = resp.goto;
        else window.location.reload();
      })
      .catch(function (error) {
        console.log('submit.trade.fail says Hi!');
        app.removeBusy(app.el.content);
        let message = error.errors || error.message || error;
        if ( typeof message == 'object' ) message = Object.values(message).join('<br>');
        app.alert({ message, theme: 'error' });
      });
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* initialize select inputs */
  app.el.clientInput = Utils.getEl('client_id');
  app.el.clientInput.value = app.el.clientInput.dataset.value;  
  
  app.el.sdafiaInput = Utils.getEl('sda_fia');
  app.el.sdafiaInput.value = app.el.sdafiaInput.dataset.value;

  app.el.otcInput = Utils.getEl('otc');
  app.el.otcInput.value = app.el.otcInput.dataset.value;  

});