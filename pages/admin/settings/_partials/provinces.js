/* global F1 */

F1.deferred.push(function initSettingsProvincesView(app) {

    console.log('initSettingsProvincesView()');
    
    const Ajax = F1.lib.Ajax;
    const Popup = F1.lib.Popup;
    const Utils = F1.lib.Utils;
    
    
    app.el.form = Utils.getEl('ProvinceForm');
    
    
    app.showProvinceForm = function(data = null) {
        app.el.form.reset();
        if (!data) {
          console.log('showProviceForm(), new province');
          app.el.form.querySelector('h4').textContent = 'New Province';
          app.el.form.elements[0].value = 'new'; // data.id
        } else {
          console.log('showProvinceForm(), edit province:', data);
          app.el.form.querySelector('h4').textContent = 'Edit Province';
          app.el.form.elements[0].value = data.id;
          app.el.form.elements[1].value = data.name;
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
    
    
    app.editProvince = function(id) {
        const url = new URL(window.location.href);
        url.searchParams.set('id', id);
        Ajax.fetch(url.toString())
        .then(resp => {
            console.log(resp);
            this.showProvinceForm(resp.data);
        })
        .catch((err) => app.handleAjaxError(err, 'delete'));  
    };
    
    
    app.deleteProvince = function(id,name) {
        if (!confirm('Are you sure you want to delete: ' + name)) return;
        const url = new URL(window.location.href);
        url.searchParams.set('id', id);
        app.showBusy();
        Ajax.post(url.toString(), { id, action: 'deleteProvince' })
        .then(resp => {
            console.log(resp);
            if (!resp.success) return app.handleAjaxError(resp, 'delete');
            app.removeBusy();
            console.log('delete.success:', resp);
            app.redirect(resp.goto);
        })
        .catch((err) => app.handleAjaxError(err, 'delete'));
    };
    
    
    app.submitProvinceForm = function (e) {
        e.preventDefault();
        const form = e.target;
        const afterClose = () => app.modal.firstFocusable?.focus();
        console.log('app.submitProvinceForm(), start...', form);
        app.showBusy();
        Ajax.submit( form, { extraData: { action: 'saveProvince' } } )
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
    