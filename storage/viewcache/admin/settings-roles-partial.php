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
  <h2>Welcome to Roles</h2>
  <hr>
  <br>
  <header>
    <button class="btn btn-primary" type="button" onclick="F1.app.showRoleForm()">
      <i class="fa fa-plus"></i> Add Role
    </button>
  </header>
  <div class="table-responsive">
    <table id="roles">
      <thead>
        <tr>
          <th>Role</th>
          <th class="nowrap">Description</th>
          <th class="nowrap">Def. Home Ref</th>
          <th class="nowrap">Permissions</th>
        </tr>
      </thead>
      <tbody>
<?php foreach( $roles as $i => $role ): ?>
        <tr>
           <td class="text-primary nowrap">
             <i class="fa fa-user"></i>&nbsp;<?=$role->name?>
           </td>
           <td class="text-primary nowrap">
             <i class="fa fa-mail"></i>&nbsp;<?=$role->description?>
           </td>
           <td class="text-primary nowrap">
             <i class="fa fa-mail"></i>&nbsp;<?=$role->home?>
           </td>
           <td class="text-primary nowrap w100">
             <i class="fa fa-mail"></i>&nbsp;<?=$role->permissions?>
           </td>
           <td>
             <button class="btn-sm text-primary" type="button" title="Edit Role"
             onclick="F1.app.editRole('<?=$role->id?>')">
             <i class="fa fa-pencil"></i></button>
           </td>
           <td>
             <button class="btn-sm text-primary" type="button" title="Delete Role" 
             onclick="F1.app.deleteRole('<?=$role->id?>','<?=$role->name?>')">
             <i class="fa fa-trash"></i></button>
           </td>
        </tr> 
<?php endforeach; ?>
<?php if( !$roles ): ?>
        <tr>
          <td colspan="4">No roles found.</td>
        </tr>
<?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<form id="RoleForm" onsubmit="F1.app.submitRoleForm(event)" hidden>
  <input type="hidden" name="id" value="new">
  <h4 class="form-header">New Role</h4>
  <label>Name</label>
  <input type="text" name="name" placeholder="Role Name" style="width:min(320px,90vw)" required>
  <label>Description</label>
  <input type="text" name="description" placeholder="Role Description" style="width:min(320px,90vw)" required>
  <label>Home</label>
  <input type="text" name="home" placeholder="Role Home" style="width:min(320px,90vw)" required>
  <label>Permissions</label>
  <input type="text" name="permissions" placeholder="Role Permissions" style="width:min(320px,90vw)" required>
  <br>
  <br>
  <input type="submit" class="btn-primary" value="Save">  
</form>
<script>
/* global F1 */

F1.deferred.push(function initAdminSettingsRolesView(app) {

    console.log('initAdminSettingsRolesView()');
    
    const Ajax = F1.lib.Ajax;
    const Popup = F1.lib.Popup;
    const Utils = F1.lib.Utils;
    
    
    app.el.form = Utils.getEl('RoleForm');
    
    
    app.showRoleForm = function(data = null) {
      app.el.form.reset();
      if (!data) {
        console.log('showRoleForm(), new role');
        app.el.form.querySelector('h4').textContent = 'New Role';
        app.el.form.elements[0].value = 'new'; // data.id
      } else {
        console.log('showRoleForm(), edit role:', data);
        app.el.form.querySelector('h4').textContent = 'Edit Role';
        app.el.form.elements[0].value = data.id;
        app.el.form.elements[1].value = data.name;
        app.el.form.elements[2].value = data.description;
        app.el.form.elements[3].value = data.home;
        app.el.form.elements[4].value = data.permissions;
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
    
    
    app.editRole = function(id) {
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      Ajax.fetch(url.toString())
      .then(resp => {
        console.log(resp);
        app.showRoleForm(resp.data);
      })
      .catch((err) => app.handleAjaxError(err, 'edit'));  
    };
    
    
    app.deleteRole = function(id,name) {
      if (!confirm('Are you sure you want to delete: ' + name)) return;
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      app.showBusy();
      Ajax.post(url.toString(), { id, action: 'deleteRole' })
      .then(resp => {
        console.log(resp);
        if (!resp.success) return app.handleAjaxError(resp, 'delete');
        app.removeBusy();
        console.log('delete.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'delete'));
    };
    
    
    app.submitRoleForm = function (e) {
      e.preventDefault();
      const form = e.target;
      const afterClose = () => app.modal.firstFocusable?.focus();
      console.log('app.submitRoleForm(), start...', form);
      app.showBusy();
      Ajax.submit( form, { extraData: { action: 'saveRole' } } )
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
<!-- Compiled: 2024-06-17 16:36:59 -->