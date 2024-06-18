/* global F1 */

F1.deferred.push(function initProfileDetailsPageView(app) {

  console.log('initProfileDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;

  app.formTemplate = Utils.getEl('tplChangePasswordForm');

  app.showChangePasswordForm = function() {
    app.modal = new Popup({
      type: 'modal',
      content: app.formTemplate.innerHTML,
      position: 'center',
    });
    app.modal.show();
  };

  app.submitChangePassword = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy();
    Ajax.submit( form, { extraData: { action: 'changePassword' } } )
      .then(function (resp) {
        app.modal.close();
        if (!resp.success) return app.handleAjaxError(resp, 'submit.cpw');
        app.removeBusy();
        console.log('submit.cpw.success:', resp);
        app.toast({ message: resp.message, afterClose: () => app.redirect(resp.goto) });        
      })
      .catch((err) => app.handleAjaxError(err, 'submit.cpw')); 
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

});