/* global F1 */

F1.deferred.push(function initUserProfilePageView(app) {

  console.log('initUserProfilePageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;

  app.el.form = Utils.getEl('changePasswordForm');

  app.showChangePasswordForm = function() {
    app.el.form.reset();
    app.modal = new Popup({
      modal: true,
      backdrop: 'dim',
      animation: 'fade',
      content: app.el.form,
      anchor: app.currentPage.el,
      position: 'center',
      // size: 'large',
    });
    app.modal.show();
  };

  app.submitChangePassword = function (e) {
    e.preventDefault();
    const form = e.target;
    const afterClose = () => app.modal.firstFocusable?.focus();
    console.log('app.submitChangePassword(), start...', form);
    app.showBusy();
    Ajax.submit( form, { extraData: { action: 'changePassword' } } )
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'submit.cpw', { afterClose });
        app.removeBusy();
        app.modal.close({src:'submit.cpw.success'});
        console.log('submit.cpw.success:', resp);
        app.toast({ message: resp.message, afterClose: () => app.redirect(resp.goto) });
      })
      .catch((err) => app.handleAjaxError(err, 'submit.cpw', { afterClose }));
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  tippy('[data-tippy-content]', { allowHTML: true });  

});