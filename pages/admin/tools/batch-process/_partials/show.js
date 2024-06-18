/* global F1 */

F1.deferred.push(function initAdminProcessClientView(app) {

  console.log('initAdminProcessClientView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;


  app.el.form = Utils.getEl('addProcessOperationForm');


  app.showProcessOperationForm = function(data = null) {
    if (!data) app.el.form.reset();
    else {
      console.log('showProcessOperationForm(), data', data);
      app.el.form.elements[0].value = data.id;
      app.el.form.elements[1].value = data.description;
      app.el.form.elements[2].value = data.page;
      app.el.form.elements[3].value = data.type_id;
    }
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


  app.editProcessOperation = function(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('id', id);
    Ajax.fetch(url.toString())
      .then(resp => {
        console.log(resp);
        this.showProcessOperationForm(resp.data);
      })
      .catch((err) => app.handleAjaxError(err, 'delete'));   
  };


  app.deleteProcessOperation = function(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('id', id);
    app.showBusy();
    Ajax.post(url.toString(), { id, action: 'deleteProcessOperation' })
      .then(resp => {
        console.log(resp);
        if (!resp.success) return app.handleAjaxError(resp, 'delete');
        app.removeBusy();
        console.log('delete.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'delete'));
  };


  app.submitProcessOperationForm = function (e) {
    e.preventDefault();
    const form = e.target;
    const afterClose = () => app.modal.firstFocusable?.focus();
    console.log('app.submitProcessOperationForm(), start...', form);
    app.showBusy();
    Ajax.submit( form, { extraData: { action: 'saveProcessOperation' } } )
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'submit', { afterClose });
        app.removeBusy();
        app.modal.close({src:'submit.success'});
        console.log('submit.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'submit', { afterClose }));
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  tippy('[data-tippy-content]', { allowHTML: true });

});
