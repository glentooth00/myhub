/* global F1 */

F1.deferred.push(function initRevenueEditPageView(app) {

    console.log('initRevenueEditPageView()');
  
    const Ajax = F1.lib.Ajax;
    const Utils = F1.lib.Utils;
  
  
    app.el.form = Utils.getEl('revenue-form');
  
    app.el.form.onsubmit = function (e) {
      e.preventDefault();
      const form = e.target;
      console.log('onSubmit start...');
      app.showBusy(app.el.content);
      Ajax.submit(form, { extraData: { action: 'saveRevenue' } })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.revenue');
          app.redirect(resp.goto, 'submit.revenue.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.revenue'));
    };
  
  
    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
  
  }); // END: initRevenueEditPageView