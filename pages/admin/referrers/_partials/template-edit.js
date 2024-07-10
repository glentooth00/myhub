/* global F1 */

F1.deferred.push(function initTemplateEditPageView(app) {

    console.log('initTemplateEditPageView()');
  
    const Ajax = F1.lib.Ajax;
    const Utils = F1.lib.Utils;
  
  
    app.el.form = Utils.getEl('template-form');
  
    app.el.form.onsubmit = function (e) {
      e.preventDefault();
      const form = e.target;
      console.log('onSubmit start...');
      app.showBusy(app.el.content);
      Ajax.submit(form, { extraData: { action: 'saveTemplate' } })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.template');
          app.redirect(resp.goto, 'submit.template.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.template'));
    };
  
  
    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
  
  }); // END: initTemplateEditPageView