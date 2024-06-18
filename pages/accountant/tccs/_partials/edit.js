/* global F1 */

F1.deferred.push(function initTccsEditPageView(app) {

  console.log('initTccsEditPageView()');

  const Ajax = F1.lib.Ajax;
  const Form = F1.lib.Form;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const F1SelectField = F1.lib.F1SelectField;
  const F1UploadField = F1.lib.F1UploadField;


  function onSubmit(event, formValid, firstInvalidField) {

    const formCtrl = this; // post = formCtrl.getValues();

    console.log('onSubmit hook:', { event, formCtrl, formValid, firstInvalidField });
    // console.log('Submitting post:', post);

    event.preventDefault();
    app.showBusy();

    // Perform some additional business rule validations
    const taxCertPDF = formCtrl.fields.tax_cert_pdf.getValue();
    const taxCaseNo = formCtrl.fields.tax_case_no.getValue();
    const tccPin = formCtrl.fields.tcc_pin.getValue();
    const status = formCtrl.fields.status.getValue();
    const date = formCtrl.fields.date.getValue();
    const approved = status === 'Approved';
    const issued = approved || taxCertPDF || tccPin;

    console.log({ status, taxCaseNo, taxCertPDF, tccPin, date, approved, issued });

    if ( formValid && issued && ( !taxCaseNo || !taxCertPDF || !tccPin || !date ) ) {
      app.removeBusy();
      formValid = false;
      try {
        if (!taxCaseNo) throw { message: 'Please fill out this field.', field: formCtrl.fields.tax_case_no };
        else if (!taxCertPDF) throw { message: 'Please upload a Tax Certificate file.', field: formCtrl.fields.tax_cert_pdf };
        else if (!tccPin) throw { message: 'Please fill out this field.', field: formCtrl.fields.tcc_pin };
        else if (!date) throw { message: 'Please fill out this field.', field: formCtrl.fields.date };
        throw { message: 'Please correct the errors.', field: firstInvalidField };
      }
      catch (err) {
        // app.alert({ message: err.message, afterClose: () => err.field.element.focus() });
        if (err.field) {
          const feedback = Utils.getEl(err.field.name + 'Feedback');
          if (feedback) feedback.innerHTML = err.message;
          err.field.focus();
        }
        console.error('onSubmit hook validation fail:', err);
      }
    }

    if (formValid) {
      console.log('Submitting formCtrl:', formCtrl);
      if (!formCtrl.isModified()) {
        console.log('Form not modified. Skip submit.');
        app.removeBusy();
        history.back();
        return;
      }
      Ajax.submit(formCtrl.formElement, { extraData: { action: 'saveTcc' } })
        .then(function (resp) {
          if (!resp.success) return app.handleAjaxError(resp, 'submit.tcc');
          app.redirect(resp.goto, 'submit.tcc.success:', resp);
        })
        .catch((err) => app.handleAjaxError(err, 'submit.tcc'));      
    }

    else {
      app.removeBusy();
      console.log('Submit failed!', firstInvalidField?.element ?? 'Business rule validation failed.');
    }

  } // onSubmit


  const customFieldTypes = { F1SelectField, F1UploadField };
  const formConfig =  { onSubmit, customFieldTypes, checkModified: true };
  console.log('formConfig:', formConfig);

  app.el.form = Utils.getEl('tcc-form');
  app.controllers.form = new Form(app.el.form, formConfig);  


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  console.log('initTccsEditPageView() done!');
});