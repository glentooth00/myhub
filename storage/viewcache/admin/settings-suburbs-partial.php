<style>
/* Page Specific CSS */

.container {
  height: 100%;
  margin: auto;
  padding: 1rem;
  max-width: 768px;
  overflow: auto;
}

.table-responsive {
  overflow: auto;
  margin: 1rem 0;
}

table {
  border-collapse: collapse;
  width: 100%;
}

th {
  text-align: left;
  padding: 4px 7px;
}

td {
  text-align: left;
  padding: 1px 7px;
  font-size: 0.96em;
  border-bottom: 1px solid gainsboro;
}

/* The Form */
form {
  position: relative;
  background-color: #fefefe;
  padding: 20px;
}

.form-header {
  margin: 0;
  padding-bottom: 1.33em;
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

form input[type="submit"] {
  margin-top: 1.5rem;
  cursor: pointer;
  padding: 13px;
}

.popup__content form {
  display: flex;
  flex-direction: column;
  margin: auto;
}

.btn-sm {
  padding: 2px 6px;
}

.btn-sm:hover {
  color: dodgerblue;
}


</style>
<div class="container">
  <hr>
  <h2>Welcome to Suburbs</h2>
  <hr>
  <br>
  <header>
    <button class="btn btn-primary" type="button" onclick="F1.app.showSuburbForm()">
      <i class="fa fa-plus"></i> Add Suburb
    </button>
  </header>
  <div class="table-responsive">
    <table id="suburbs">
      <thead>
        <tr>
          <th>Suburbs</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
<?php foreach( $suburbs as $i => $suburb ): ?>
        <tr>
           <td class="text-primary w100">
             <i class="fa fa-map"></i>&nbsp;<?=$suburb->name?>
           </td>
           <td>
            <button class="btn-sm text-primary" type="button" title="Edit Suburb"
              onclick="F1.app.editSuburb('<?=$suburb->id?>')">
              <i class="fa fa-pencil"></i></button>
           </td>
           <td>
            <button class="btn-sm text-primary" type="button" title="Delete Suburb" 
              onclick="F1.app.deleteSuburb('<?=$suburb->id?>','<?=$suburb->name?>')">
              <i class="fa fa-trash"></i></button>
           </td>
        </tr>
<?php endforeach; ?>
<?php if( !$suburbs ): ?>
        <tr>
          <td colspan="3">No suburbs found.</td>
        </tr>
<?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<form id="SuburbForm" onsubmit="F1.app.submitSuburbForm(event)" hidden>
  <input type="hidden" name="id" value="new">
  <h4 class="form-header">New Suburb</h4>
  <label>Suburb</label>
  <input type="text" name="name" placeholder="Suburb Name" style="width:min(320px,90vw)" required>
  <br>
  <br>
  <input type="submit" class="btn-primary" value="Save">  
</form>
<script>
/* global F1 */

F1.deferred.push(function initAdminSettingsSuburbsView(app) {

    console.log('initAdminSettingsSuburbsView()');
    
    const Ajax = F1.lib.Ajax;
    const Popup = F1.lib.Popup;
    const Utils = F1.lib.Utils;
    
    
    app.el.form = Utils.getEl('SuburbForm');
    
    
    app.showSuburbForm = function(data = null) {
      app.el.form.reset();
      if (!data) {
        console.log('showSuburbForm(), new suburb');
        app.el.form.querySelector('h4').textContent = 'New Suburb';
        app.el.form.elements[0].value = 'new'; // data.id
      } else {
        console.log('showSuburbForm(), edit suburb:', data);
        app.el.form.querySelector('h4').textContent = 'Edit Suburb';
        app.el.form.elements[0].value = data.id;
        app.el.form.elements[1].value = data.name;
        // app.el.form.elements[2].value = data.type_id;
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
    
    
    app.editSuburb = function(id) {
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      Ajax.fetch(url.toString())
      .then(resp => {
        console.log(resp);
        app.showSuburbForm(resp.data);
      })
      .catch((err) => app.handleAjaxError(err, 'edit'));  
    };
    
    
    app.deleteSuburb = function(id,name) {
      if (!confirm('Are you sure you want to delete: ' + name)) return;
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      app.showBusy();
      Ajax.post(url.toString(), { id, action: 'deleteSuburb' })
      .then(resp => {
        console.log(resp);
        if (!resp.success) return app.handleAjaxError(resp, 'delete');
        app.removeBusy();
        console.log('delete.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'delete'));
    };
    
    
    app.submitSuburbForm = function (e) {
      e.preventDefault();
      const form = e.target;
      const afterClose = () => app.modal.firstFocusable?.focus();
      console.log('app.submitSuburbForm(), start...', form);
      app.showBusy();
      Ajax.submit( form, { extraData: { action: 'saveSuburb' } } )
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
    
</script>
<!-- Compiled: 2024-06-17 16:37:22 -->