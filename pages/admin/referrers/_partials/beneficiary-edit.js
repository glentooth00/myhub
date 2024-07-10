/* global F1 */

F1.deferred.push(function initBeneficiaryEditPageView(app) {

    console.log('initBeneficiaryEditPageView()');
  
    const Ajax = F1.lib.Ajax;
    const Utils = F1.lib.Utils;
  
  
    app.el.form = Utils.getEl('beneficiary-form');
  
    app.el.form.onsubmit = function (e) {
      e.preventDefault();
      const form = e.target;
      console.log('onSubmit start...');
      app.showBusy(app.el.content);
      Ajax.submit(form, { extraData: { action: 'saveBeneficiary' } })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.beneficiary');
          app.redirect(resp.goto, 'submit.beneficiary.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.beneficiary'));
    };
  
  
    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
  
  }); // END: initReferrerEditPageView