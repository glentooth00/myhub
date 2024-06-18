/* global F1 */

F1.deferred.push(function initReferrerEditPageView(app) {

  console.log('initReferrerEditPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;


  app.el.form = Utils.getEl('referrer-form');

  app.el.form.onsubmit = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy(app.el.content);
    Ajax.submit(form, { extraData: { action: 'saveReferrer' } })
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'submit.referrer');
        app.redirect(resp.goto, 'submit.referrer.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'submit.referrer'));
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

}); // END: initReferrerEditPageView