/* global F1 */

F1.deferred.push(function initClientsEditPageView(app) {

  console.log('initClientsEditPageView()');
  

  const Ajax = F1.lib.Ajax;
  const Form = F1.lib.Form;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const F1SelectField = F1.lib.F1SelectField;
  const F1UploadField = F1.lib.F1UploadField;  


  app.updateSliderUi = function(slider) {
    valueDisplay = slider.nextElementSibling;
    valueDisplay.innerText = slider.value + '%';
  };


  app.addSelectOption = function(event) {
    const select = event.target.closest('.select');
    const selectCtrl = select.CONTROLLER;
    const fieldName = selectCtrl.select.name;
    const optName = selectCtrl.searchInput.value.trim();
    const afterClose = () => selectCtrl.toggleDropdown('closed');
    const addAndSelectOption = function(e, btn) {
      console.log('Hello from addAndSelectOption()');
      const simpleOptions = selectCtrl.select.dataset?.simpleOptions || '';
      const optName = popup.content.querySelector('#optName').value.trim();
      const extraFields = popup.content.querySelectorAll('.extra-field');
      const extraData = {};
      extraFields.forEach(field => { extraData[field.name] = field.value.trim(); });
      const payload = { action: 'addOption', fieldName, optName };
      if (Object.keys(extraData).length) payload.extraData = JSON.stringify(extraData);
      console.log('addAndSelectOption.payload:', payload);
      Ajax.post(location.href, payload).then(function (resp) {
          const fail = !resp.success || !resp.optID;
          if (fail) return app.handleAjaxError(resp, 'submit.option', { afterClose } );
          console.log('submit.option.success:', resp);
          app.toast({ message: resp.message });
          const optValue = simpleOptions ? resp.optName : resp.optID;
          const newOption = Utils.newEl('option', null, { text: resp.optName });
          if (simpleOptions) newOption.dataset.key = resp.optID;
          else newOption.value = optValue;
          selectCtrl.select.append(newOption); selectCtrl.updateOptions();
          selectCtrl.selectOption(optValue);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.option.catch', { afterClose } ));
      popup.close({event});
    };
    const addButton = { text: 'Add Option', className: 'btn--primary', onClick: addAndSelectOption };
    const cancelButton = { text: 'Cancel', className: 'btn--secondary' };
    const popup = new Popup({
      theme: 'info',
      type: 'prompt',
      position: 'center',
      title: 'Add new <b>' + Utils.titleCase(selectCtrl.select.id) + '</b>',
      content: `<input id="optName" type="text" value="${optName}" style="width: 100%;">`,
      buttons: [addButton, cancelButton],
      afterClose,
    });    
    if (fieldName == 'referrer_id') {      
      const idNumberField = Utils.newEl('input', 'extra-field', { name: 'id_number', type: 'text', placeholder: 'ID Number' });
      const emailField = Utils.newEl('input', 'extra-field', {name: 'email', type: 'email', placeholder: 'Email Address' });
      const phoneNumberField = Utils.newEl('input', 'extra-field', {name: 'phone_number', type: 'tel', placeholder: 'Phone Number' });
      popup.content.querySelector('#optName').placeholder = 'Name';
      popup.content.appendChild(idNumberField);
      popup.content.appendChild(emailField);
      popup.content.appendChild(phoneNumberField);
    }
    popup.show();
  };


  app.editSelectOption = function(event) {
    const select = event.target.closest('.select');
    const selectCtrl = select.CONTROLLER;
    const opt = selectCtrl.select.selectedOptions[0];
    const optValue = opt.value.trim();
    const optName = opt.innerText.trim();
    const optID = opt.dataset.key || optValue;
    console.log('editSelectOption:', { select, selectCtrl, optID, optValue, optName });
    const afterClose = () => selectCtrl.toggleDropdown('closed');
    const editAndSelectOption = function(e, btn) {
      console.log('Hello from editSelectOption()');
      const newOptName = popup.content.querySelector('#newOptName').value.trim();
      const simpleOptions = selectCtrl.select.dataset?.simpleOptions || '';
      const fieldName = selectCtrl.select.name;
      Ajax.post(location.href, { action: 'updateOption', fieldName, optID, optValue, 
        optName, newOptName, simpleOptions }).then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.option', { afterClose } );
          console.log('edit.option.success:', resp);
          app.toast({ message: resp.message });
          opt.innerText = resp.optName;
          selectCtrl.updateOptions();
          selectCtrl.selectOption(simpleOptions ? resp.optName : optValue);
        })
        .catch((err) => app.handleAjaxError(err, 'edit.option.catch', { afterClose } ));
      popup.close({event});
    };
    const editButton = { text: 'Update Option', className: 'btn--primary', onClick: editAndSelectOption };
    const cancelButton = { text: 'Cancel', className: 'btn--secondary' };
    const popup = new Popup({
      theme: 'info',
      size: 'small',
      type: 'prompt',
      position: 'center',
      title: 'Edit <b>' + Utils.titleCase(selectCtrl.select.id) + '</b>',
      content: `<input id="newOptName" type="text" value="${optName}" style="width: 100%;">`,
      buttons: [editButton, cancelButton],
      afterClose,
    });    
    popup.show();
  };


  app.deleteSelectOption = function(event) {
    const select = event.target.closest('.select');
    const selectCtrl = select.CONTROLLER;
    const opt = selectCtrl.select.selectedOptions[0];
    const optValue = opt.value.trim();
    const optName = opt.innerText.trim();
    const optID = opt.dataset.key || optValue;
    console.log('deleteSelectOption:', { select, selectCtrl, optID, optValue, optName });
    const afterClose = () => selectCtrl.toggleDropdown('closed');
    const deleteAndSelectOption = function(e, btn) {
      console.log('Hello from deleteSelectOption()');
      const fieldName = selectCtrl.select.name;
      Ajax.post(location.href, { action: 'deleteOption', optID, optName, optValue, fieldName })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.option', { afterClose } );
          console.log('delete.option.success:', resp);
          app.toast({ message: resp.message });
          opt.remove(); selectCtrl.updateOptions();
          selectCtrl.selectOption(selectCtrl.select.options[0].value);
        })
        .catch((err) => app.handleAjaxError(err, 'delete.option.catch', { afterClose } ));
      popup.close({event});
    };
    const deleteButton = { text: 'Delete Option', className: 'btn--primary', onClick: deleteAndSelectOption };
    const cancelButton = { text: 'Cancel', className: 'btn--secondary' };
    const popup = new Popup({
      theme: 'danger',
      size: 'small',
      type: 'prompt',
      position: 'center',
      title: 'Delete <b>' + Utils.titleCase(selectCtrl.select.id) + '</b>',
      content: `<span>Are you sure you want to delete option <b>${optName}</b>?</span>`,
      buttons: [deleteButton, cancelButton],
      afterClose,
    });    
    popup.show();
  };


  app.manageSelectOptions = function(event) {
    alert('Hello from manageOptions()');
  };


  const onSubmit = function(event, formValid, firstInvalidField) {
    const formCtrl = this; // post = formCtrl.getValues();
    console.log('onSubmit hook:', { event, formCtrl, formValid, firstInvalidField });
    // console.log('Submitting post:', post);

    app.showBusy();
    event.preventDefault();

    // Perform some additional business rule validations
    // Additional validation logic goes here...

    if (formValid) {
      console.log('Submitting formCtrl:', formCtrl);
      if (!formCtrl.isModified()) {
        console.log('Form not modified. Skip submit.');
        app.removeBusy();
        history.back();
        return;
      }
      Ajax.submit(formCtrl.formElement, { extraData: { action: 'saveClient' } })
        .then(function (resp) {
          if (!resp.success) {
            if (resp.errors) {
              app.removeBusy();
              for (const fieldname in resp.errors) {
                const field = formCtrl.fields[fieldname];
                if (field) {
                  field.updateValidationUi(false, resp.errors[fieldname]);
                  return formCtrl.gotoField(field);
                }
              }
            } else { 
              return app.handleAjaxError(resp, 'submit.client');
            }
          }       
          app.redirect(resp.goto, 'submit.client.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.client'));      
    } 
    else {
      app.removeBusy();
      console.log('Submit failed!', firstInvalidField?.element ?? 'Business rule validation failed.');
    }

  }; // end: onSubmit


  const formConfig =  { customFieldTypes: { F1SelectField, F1UploadField }, onSubmit, checkModified: true };
  console.log('formConfig:', formConfig);

  app.el.form = Utils.getEl('client_form');
  app.controllers.form = new Form(app.el.form, formConfig);


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  console.log('initClientsEditPageView() done!');
});