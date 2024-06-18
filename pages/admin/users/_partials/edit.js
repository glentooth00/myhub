/* global F1 */

F1.deferred.push(function initUsersEditPageView(app) {

  console.log('initUsersEditPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;


  app.el.form = Utils.getEl('user-form');

  app.el.form.onsubmit = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy(app.el.content);
    Ajax.submit(form, { extraData: { action: 'saveUser' } })
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'submit.user');
        app.redirect(resp.goto, 'submit.user.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'submit.user'));
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');
  
});