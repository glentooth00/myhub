<style>
/* Page Specific CSS */

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
/*  --select-color-primary: #052fa7;*/
}

form [disabled] {
  background-color: whitesmoke;
  cursor: not-allowed;
}

input,
select,
textarea,
.upload button {
  width: 100%;
  padding: 0.75em;
  border-radius: 0.25em;
  border: 1px solid #cdcdcd;
  font-family: 'Roboto', sans-serif;
  background-color: white;
  font-size: 16px;
  line-height: 1.1;
}

select:focus {
  outline: 1px auto;
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
  content: " *";
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


/* Reset fieldset and legend styles */
fieldset.radio-buttons {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  border: none;
  padding: 0;
}

.feedback {
  color: red;
  display: flex;
  flex-direction: column;
  margin-top: 0.15rem;
  font-size: 0.8rem;
  line-height: 1;
}

/* Style for the labels/status buttons */
.radio-buttons label {
  display: inline-block;
  background-color: #f2f2f2;
  font-family: 'Roboto', sans-serif;
  transition: background-color 0.3s, color 0.3s;
  user-select: none; /* Prevent text selection */
  text-align: center;
  margin: 0 5px 0 0;
  font-size: 16px;
  cursor: pointer;
  color: #333;
  flex: 1;
}

/* Hide the radio input visually */
.radio-buttons input[type="radio"] {
  background: transparent;
  position: absolute;
  appearance: none;
  outline: none;
  height: 1px;
  width: 1px;
  border: 0;
}

/* Style for the span inside the label */
.radio-buttons label span {
  display: block;
  border: 1px solid #cdcdcd;
  border-radius: 4px;
  pointer-events: none; /* Makes click pass through to the radio button */
  padding: 10px 20px;
}

/* Style when the radio button is checked */
.radio-buttons input[type="radio"]:checked + span {
  background-color: var(--primary-color-dark);
  border-color: var(--primary-color);
  color: white;
}

/* Focus styles, applied to the span since the input is hidden */
.radio-buttons input[type="radio"]:focus + span {
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5);
}

/* Hover styles */
.radio-buttons label:hover span {
  background-color: #e7e7e7;
}

/* Last label style to remove extra margin */
.radio-buttons label:last-child {
  margin-right: 0;
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

.popup__close {
  font-size: 23px;
}

.popup__danger {
  color: crimson;
}

.popup__danger .popup__header {
  color: firebrick;
}

.extra-field {
  margin-top: 0.34rem;
  min-width: min(100vw, 280px);
}
</style>
<div class="flex-col h100 w100">
  <div class="container">
    <header>
<?php if( $isEdit ): ?>
      <h1>Edit Client ID: <?=$id?></h1>
<?php endif; ?>
<?php if( $isNew ): ?>
      <h1>Add New Client</h1>
<?php endif; ?>
    </header>
    <form id="client_form" method="post" novalidate>
      
      <input type="hidden" name="id" value="<?=$client->id?>">

      <label for="client_id" required>Client UID:</label>
      <?=$form->input( 'text', $client, 'client_id', ['readonly' => ($isEdit and !$super), 'required' => true] )?>
      <p id="client_idFeedback" class="feedback"></p>
      
      <label required>Status</label>
      <fieldset id="status" class="radio-buttons" data-value="<?=$client->status?>" >
        <label for="active">
          <input type="radio" id="active" name="status" value="Active" required>
          <span>Active</span>
        </label>
        <label for="inactive">
          <input type="radio" id="inactive" name="status" value="Inactive">
          <span>Inactive</span>
        </label>
        <label for="closed">
          <input type="radio" id="closed" name="status" value="Closed">
          <span>Closed</span>
        </label>
      </fieldset>
      <p id="statusFeedback" class="feedback"></p>

      <label for="name" required>First Name</label>
      <input type="text" id="first_name" name="first_name" value="<?=$client->first_name?>" required>
      <p id="first_nameFeedback" class="feedback"></p>

      <label for="middle_name">Middle Name</label>
      <input type="text" id="middle_name" name="middle_name" value="<?=$client->middle_name?>">
      <p id="middle_nameFeedback" class="feedback"></p>

      <label for="last_name" required>Surname</label>
      <input type="text" id="last_name" name="last_name" value="<?=$client->last_name?>" required>
      <p id="last_nameFeedback" class="feedback"></p>

      <label for="name" required>Preferred Name</label>
      <input type="text" id="name" name="name" value="<?=$client->name?>" required>
      <p id="nameFeedback" class="feedback"></p>

      <label for="personal_email" required>Personal Email</label>
      <input type="email" id="personal_email" name="personal_email" value="<?=$client->personal_email?>" required>
      <p id="personal_emailFeedback" class="feedback"></p>

      <label for="spouse_id">Spouse:</label>
      <select id="spouse_id" name="spouse_id" data-custom-type="F1SelectField" data-size="large"
        data-searchable="true" data-clear-prompt="x" data-manage-options="off" 
        data-value="<?=$client->spouse_id?>">
        <option value="">Select...</option>
<?php foreach( $clients as $spouse ): ?>
        <option value="<?=$spouse->id?>"><?=escape($spouse->name)?></option>
<?php endforeach; ?>
      </select>
      <p id="spouse_idFeedback" class="feedback"></p>

      <label for="ncr">NCR</label>
      <select id="ncr" name="ncr" data-custom-type="F1SelectField"
        data-searchable="true" data-clear-prompt="x" data-size="large" data-manage-options="on">
        <option value="">Select...</option>
<?php foreach( $ncrs as $ncr ): ?>
        <?=$form->option($ncr->id, escape($ncr->name), $client->ncr)?>
<?php endforeach; ?>
      </select>
      <p id="ncrFeedback" class="feedback"></p>
    
      <label for="referrer">Referrer</label>
      <select id="referrer" name="referrer_id" data-custom-type="F1SelectField"
        data-searchable="true" data-clear-prompt="x" data-size="large" data-manage-options="on">
        <option value="">Select...</option>
<?php foreach( $referrers as $referrer ): ?>
        <?=$form->option($referrer->id, escape($referrer->name), $client->referrer_id)?>
<?php endforeach; ?>
      </select>
      <p id="referrer_idFeedback" class="feedback"></p>

      <label for="phone_number" required>Phone Number</label>
      <input type="text" id="phone_number" name="phone_number" placeholder="e.g. 27821234567" 
        pattern="\+?\d{1,4}([\- ]?\d{1,3}){1,4}" title="International phone number format" 
        value="<?=$client->phone_number?>" required>
      <p id="phone_numberFeedback" class="feedback"></p>

      <label for="id_number" required>ID Number</label>
      <!-- TODO: Add validation for ID number or passport number -->
      <input type="text" id="id_number" name="id_number" maxLength="13" value="<?=$client->id_number?>" required>
      <p id="id_numberFeedback" class="feedback"></p>

      <label for="accountant">Accountant</label>
      <select id="accountant" name="accountant">
        <option value="">- None -</option>
<?php foreach( $accountants as $accountant ): ?>
        <?=$form->option(fullName($accountant), null, $client->accountant)?>
<?php endforeach; ?>
      </select>
      <p id="accountantFeedback" class="feedback"></p>

      <label for="tax_number" required>Tax Number</label>
      <input type="text" id="tax_number" name="tax_number" value="<?=$client->tax_number?>" maxLength="13" required>
      <p id="tax_numberFeedback" class="feedback"></p>

      <label for="bank">Bank</label>
      <select id="bank" name="bank" data-custom-type="F1SelectField" data-searchable="true"
        data-size="large" data-clear-prompt="x" data-manage-options="on" data-simple-options="true" >
<?php foreach( $banks as $bank ): ?>
        <?=$form->option(escape($bank->name), null, escape($client->bank), '', $bank->id)?>
<?php endforeach; ?>
      </select>
      <p id="bankFeedback" class="feedback"></p>

      <label for="cif_number">CIF Number</label>
      <input type="text" id="cif_number" name="cif_number" value="<?=$client->cif_number?>">
      <p id="cif_numberFeedback" class="feedback"></p>

      <label for="bp_number">BP Number</label>
      <input type="text" id="bp_number" name="bp_number" value="<?=$client->bp_number?>">
      <p id="bp_numberFeedback" class="feedback"></p>

      <label for="capitec_id">Capitec ID</label>
      <input type="text" id="capitec_id" name="capitec_id" value="<?=$client->capitec_id?>">
      <p id="capitec_idFeedback" class="feedback"></p>

      <label for="capitec_name">Capitec Name</label>
      <input type="text" id="capitec_name" name="mercantile_name" value="<?=$client->mercantile_name?>">
      <p id="mercantile_nameFeedback" class="feedback"></p>

      <label for="address" required>Address</label>
      <input type="text" id="address" name="address" value="<?=$client->address?>" required>
      <p id="addressFeedback" class="feedback"></p>

      <label for="suburb" required>Suburb</label>
      <select id="suburb" name="suburb" data-custom-type="F1SelectField" data-searchable="true" 
        data-size="large" data-clear-prompt="x" data-manage-options="on" data-simple-options="true" required>
        <option value="">Select...</option>
<?php foreach( $suburbs as $suburb ): ?>
        <?=$form->option(escape($suburb->name), null, escape($client->suburb), '', $suburb->id)?>
<?php endforeach; ?>
      </select>
      <p id="suburbFeedback" class="feedback"></p>

      <label for="city" required>City</label>
      <select id="city" name="city" data-custom-type="F1SelectField" data-searchable="true"
        data-size="large" data-clear-prompt="x" data-manage-options="on" data-simple-options="true" required>
        <option value="">Select...</option>
<?php foreach( $cities as $city ): ?>
        <?=$form->option(escape($city->name), null, escape($client->city), '', $city->id)?>
<?php endforeach; ?>
      </select>
      <p id="cityFeedback" class="feedback"></p>

      <label for="province" required>Province</label>
      <select id="province" name="province" data-custom-type="F1SelectField" data-searchable="true"
        data-size="large" data-clear-prompt="x" data-manage-options="on" data-simple-options="true" required>
        <option value="">Select...</option>
<?php foreach( $provinces as $province ): ?>
        <?=$form->option(escape($province->name), null, escape($client->province), '', $province->id)?>
<?php endforeach; ?>
      </select>
      <p id="provinceFeedback" class="feedback"></p>

      <label for="country" required>Country</label>
      <select id="country" name="country" data-custom-type="F1SelectField" data-searchable="true"
        data-size="large" data-clear-prompt="x" data-manage-options="on" data-simple-options="true" required>
        <option value="">Select...</option>
<?php foreach( $countries as $country ): ?>
        <?=$form->option(escape($country->name), null, escape($client->country), '', $country->id)?>
<?php endforeach; ?>
      </select>
      <p id="countryFeedback" class="feedback"></p>

      <label for="postal_code" required>Postal Code</label>
      <input type="text" id="postal_code" name="postal_code" maxLength="4" value="<?=$client->postal_code?>" required>
      <p id="postal_codeFeedback" class="feedback"></p>

      <label for="trading_capital">Trading Capital</label>
      <input type="text" id="trading_capital" name="trading_capital" value="<?=currency($client->trading_capital, 'R ')?>" 
        onchange="this.value = F1.lib.Utils.currency(this.value)">

      <p id="trading_capitalFeedback" class="feedback"></p>

      <label for="sda_mandate">SDA Mandate</label>
      <input type="text" id="sda_mandate" name="sda_mandate" value="<?=currency($client->sda_mandate, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)">
      <p id="sda_mandateFeedback" class="feedback"></p>

      <label for="fia_mandate">FIA Mandate</label>
      <input type="text" id="fia_mandate" name="fia_mandate" value="<?=currency($client->fia_mandate, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)">      
      <p id="fia_mandateFeedback" class="feedback"></p>

      <label for="next_year_sda">Next Year's SDA Mandate</label>
      <input type="text" id="next_year_sda" name="next_years_sda_mandate" value="<?=currency($client->next_years_sda_mandate, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)">      
      <p id="next_years_sda_mandateFeedback" class="feedback"></p>

      <label for="next_year_fia">Next Year's FIA Mandate</label>
      <input type="text" id="next_year_fia" name="next_years_fia_mandate" value="<?=currency($client->next_years_fia_mandate, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)">      
      <p id="next_years_fia_mandateFeedback" class="feedback"></p>

      <label for="marriage_cert">Marriage Cert File:</label>
      <input type="file" id="marriage_cert" name="spare_1" accept=".jpg, .png, .pdf" 
        data-custom-type="F1UploadField" data-value="<?=$client->spare_1?>">
      <p id="spare_1Feedback" class="feedback"></p>

      <label for="crypto_declaration">Crypto Declaration File:</label>
      <input type="file" id="crypto_declaration" name="spare_2" accept=".jpg, .png, .pdf" 
        data-custom-type="F1UploadField" data-value="<?=$client->spare_2?>">
      <p id="spare_2Feedback" class="feedback"></p>

      <label for="notes">Notes</label>
      <textarea id="notes" name="notes"><?=$client->notes?></textarea>
      <p id="notesFeedback" class="feedback"></p>

      <footer>
        <button type="submit" class="btn-success">Save</button>
        <button type="button" class="btn-default" onclick="F1.app.onExit(event, history.back.bind(history))">Cancel</button>
      </footer>
    </form>
  </div>
</div>
<script>
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
</script>
<!-- Compiled: 2024-06-17 23:21:28 -->