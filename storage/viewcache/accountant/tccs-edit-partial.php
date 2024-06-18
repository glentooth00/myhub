<style>
body > footer {
  overflow: hidden;
  height: 0;
}

.page {
	display: flex;
	flex-direction: column;
	align-items: center;
  font-family: 'Roboto', sans-serif;
}

.container {
  margin: 0 auto 3.5rem;
  padding: 1.5rem 1.5rem 0;
  background-color: white;
  box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
  width: min(640px, 100%);    
  overflow-y: auto;
}

.container h1 {
  text-align: center;
  font-size: 24px;
  position: relative;
  top: -0.9rem;
}

form {
  padding-bottom: 1.5rem;  
}

form [disabled] {
  background-color: whitesmoke;
  cursor: not-allowed;
}

input,
select,
textarea {
  width: 100%;
  padding: 0.75em;
  border-radius: 5px;
  border: 1px solid #cdcdcd;
  font-family: 'Roboto', sans-serif;
  background-color: white;
  font-size: 16px;
  line-height: 1.1;
}

input[type="file"] {
  padding: 0.5em 0.75em;
}

form label {
  margin: 1.5rem 0 3px;
  font-size: 13px;
  display: block;
  color: #666;
}

form label[required]::after {
  content: "*";
  color: red;
}

form label:first-of-type {
  margin-top: 0;
}

form footer {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  text-align: center;
  background-color: white;
  box-shadow: 0px 0px 14px rgba(0,0,0,0.2);
}

form footer button {
  margin: 10px;  
}

.field button {
  margin-top: 0.5rem;
  margin-right: 0.34rem;
}

[readonly] {
  background-color: whitesmoke;
  cursor: not-allowed;
}


.select {
  --select-color-muted: silver;
}

.select small {
  color: var(--select-color-muted);
}

.select li[role="option"]:hover small {
  color: var(--select-color-light);
}

.select ul[role="listbox"] {
  padding-top: 0;
}

.select__utilbar {
  display: block !important;
  padding: var(--select-padding-s);
}

.select__utilbar p {
  display: none;
  padding: var(--select-padding-s);
  margin: 0;
}

.select--noresults .select__utilbar p {
  display: block;
  margin-top: var(--select-padding-s);
}

.select__utilbar button {
  border: 1px solid;
  border-radius: var(--select-border-radius);
  padding: var(--select-padding-s) var(--select-padding);
  margin: 0 var(--select-padding-s) var(--select-padding-s);
  display: inline-block;
  font-size: 0.8em;
  cursor: pointer;
}

.select__add, .select__edit {
  color: var(--select-color-primary);  
}

.select__delete {
  color: var(--danger-color);  
}


.popup {
  --popup-color--info: var(--primary-color);
}
</style>
<div class="flex-col h100 w100">
  <div class="container">
    <header>
<?php if( $isEdit ): ?>
      <h1>Edit TCC ID: <?=$id?></h1>
<?php endif; ?>
<?php if( $isNew ): ?>
      <h1>Add New TCC</h1>
<?php endif; ?>
    </header>
    <form id="tcc-form" method="post" novalidate>
      
      <input type="hidden" name="id" value="<?=$tcc->id?>">

      <label for="status">PIN Status:</label>
      <select id="status" name="status" data-value="<?=$tcc->status?>">
<?php foreach( $statuses as $status ): ?>
        <option><?=$status?></option>
<?php endforeach; ?>
      </select>
      <p id="statusFeedback" class="feedback"></p>

      <label for="application_date" required>Applied On:</label>
      <input type="date" id="application_date" name="application_date" value="<?=$tcc->application_date?>" max="<?=date('Y-m-d')?>" required>
      <p id="application_dateFeedback" class="feedback"></p>

      <label for="amount_cleared" required>Amount:</label>
      <input type="text" id="amount_cleared" name="amount_cleared" value="<?=currency($tcc->amount_cleared, 'R ')?>" 
        onchange="this.value=F1.lib.Utils.currency(this.value)" required>
      <p id="amount_clearedFeedback" class="feedback"></p>

      <label for="client_id" required>Client:</label>
      <select id="client_id" name="client_id" data-custom-type="F1SelectField" data-size="large"
        data-searchable="true" data-clear-prompt="x" data-value="<?=$tcc->client_id?>" required>
<?php foreach( $clients as $client ): ?>
        <option value="<?=$client->client_id?>"><?=$client->name?></option>
<?php endforeach; ?>
      </select>
      <p id="client_idFeedback" class="feedback"></p>

      <label for="tax_case_no">Tax Case No:</label>
      <input type="text" id="tax_case_no" name="tax_case_no" value="<?=escape($tcc->tax_case_no)?>">
      <p id="tax_case_noFeedback" class="feedback"></p>

      <label for="tax_cert_pdf">Tax Cert File:</label>
      <input type="file" id="tax_cert_pdf" name="tax_cert_pdf" accept=".pdf" 
        data-custom-type="F1UploadField" data-value="<?=$tcc->tax_cert_pdf?>">
      <p id="tax_cert_pdfFeedback" class="feedback"></p>

      <label for="tcc_pin">PIN Number:</label>
      <input type="text" id="tcc_pin" name="tcc_pin" value="<?=escape($tcc->tcc_pin)?>"
        pattern="^[a-zA-Z0-9]{10}$" title="PIN must be exactly 10 alphanumeric characters" maxlength="10">
      <p id="tcc_pinFeedback" class="feedback"></p>

      <label for="date">Approved On:</label>
      <input type="date" id="date" name="date" value="<?=$tcc->date?>" max="<?=date('Y-m-d')?>">
      <p id="dateFeedback" class="feedback"></p>

      <label for="notes">Notes:</label>
      <textarea id="notes" name="notes"><?=escape($tcc->notes)?></textarea>

<?php if( $isEdit ): ?>
      <label for="amount_used">Amount Used:</label>
      <?=$form->input( 'text', $tcc, 'amount_used', ['format' => 'currency', 'readonly' => true] )?>

      <label for="rollover">Rollover:</label>
      <?=$form->input( 'text', $tcc, 'rollover', ['format' => 'currency', 'readonly' => true] )?>

      <label for="expired">Expired Year:</label>
      <?=$form->input( 'text', $tcc, 'expired', ['readonly' => true ] )?>

      <label for="allocated_trades">Allocated_Trades:</label>
      <?=$form->input( 'textarea', $tcc, 'allocated_trades', ['readonly' => true] )?>

      <label for="updated_by">Updated By:</label>
      <input type="text" id="updated_by" name="updated_by" value="<?=$tcc->updated_by?>" readonly>

      <label for="updated_at">Updated At:</label>
      <input type="text" id="updated_at" name="updated_at" value="<?=$tcc->updated_at?>" readonly>

      <label for="created_at">Created At:</label>
      <input type="text" id="created_at" name="created_at" value="<?=$tcc->created_at?>" readonly>

      <label for="created_by">Created By:</label>
      <input type="text" id="created_by" name="created_by" value="<?=$tcc->created_by?>" readonly>
<?php endif; ?>

      <label for="tcc_id">TCC UUID:</label>
      <?=$form->input( 'text', $tcc, 'tcc_id', ['readonly' => true] )?>

      <footer>
        <button type="submit" class="btn-success">Save</button>
        <button type="button" class="btn-default" onclick="F1.app.onExit(event, history.back.bind(history))">Cancel</button>
      </footer>
    </form>
  </div>
</div>
<script>
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
</script>

<!-- Compiled: 2024-06-17 23:07:32 -->