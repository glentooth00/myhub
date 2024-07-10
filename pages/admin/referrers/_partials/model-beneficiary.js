/* global F1 */

F1.deferred.push(function initModelBeneficiaryEditPageView(app) {

    console.log('initModelBeneficiaryEditPageView()');
  
    const Ajax = F1.lib.Ajax;
    const Utils = F1.lib.Utils;
  
  
    app.el.form = Utils.getEl('model-beneficiary-form');
  
    app.el.form.onsubmit = function (e) {
      e.preventDefault();
      const form = e.target;
      console.log('onSubmit start...');
      app.showBusy(app.el.content);
      Ajax.submit(form, { extraData: { action: 'saveModel' } })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.model');
          app.redirect(resp.goto, 'submit.model.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.model'));
    };
  
  
    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
  
  }); // END: initReferrerEditPageView