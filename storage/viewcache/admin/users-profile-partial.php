<style>
.page {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.content-wrapper {
  background-color: white;
  box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
  min-width: min(800px, 100%);
  overflow-y: auto;
  padding: 1rem;
  flex: 1;
}

header + section {
  margin-top: 1rem;
  margin-bottom: 1rem;
}

section .btn-round span {
  font-size: 1.34rem;
}

section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

section ul li {
  padding: 10px 0;
/*  border-bottom: 1px solid #ddd;*/
}

section ul li:last-child {
  border-bottom: none;
}

section ul li label {
  display: block;
  font-size: 0.8em;
  color: #777;
  margin-bottom: 5px;
}

section ul li span {
  display: block;
  font-size: 1.1em;
  color: #333;
}

section .ext-link .icon {
  width: 1.15rem;
  height: 1.15rem;
  margin-left: 0.67rem;
}


/* The Form */
form {
  position: relative;
  background-color: #fefefe;
  padding: 20px;
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


</style>
<div class="content-wrapper">
  <header>
    <h1><i class="fa fa-vcard fa-lg"></i>&nbsp;<?=$user->first_name . " " . $user->last_name?></h1>
    <h4 class="py1">UID: <?=$user->user_id?></h4>
  </header>
  <section class="flex-row flex-gap">
    <button class="btn-round btn-primary" type="button"><span>A</span></button>
    <button class="btn-round btn-primary" type="button"><span>B</span></button>
<?php if( $isMyProfile or $super ): ?>
    <button class="btn-round btn-primary" type="button" data-tippy-content="Change Password"
      onclick="F1.app.showChangePasswordForm()">
      <i class="fa fa-key fa-lg"></i>
    </button>
<?php endif; ?>
  </section>
  <section>
    <ul>
      <li><label>Username:</label> <span><?=$user->username?></span></li>
      <li><label>Email:</label> <span><?=$user->email?></span></li>
      <li><label>Status:</label> <span><?=$user->status?></span></li>
      <li><label>Role:</label> <span><?=$roles[$user->role_id] ?? '-'?></span></li>
      <li><label>Created At:</label> <span><?=$user->created_at?></span></li>
      <li><label>Updated At:</label> <span><?=$user->updated_at?></span></li>
      <li><label>Last Login At:</label> <span><?=$user->last_login_at?></span></li>
      <li><label>Last Activity At:</label> <span><?=$user->last_activity_at?></span></li>
      <li><label>Failed Login Attempts:</label> <span><?=$user->failed_logins?></span></li>
    </ul>      
  </section>
</div>
<button class="fab btn-round btn-primary" type="button" data-tippy-content="Edit"
  onclick="F1.app.navigateTo({url:'admin/users?view=edit&id=<?=$id?>', view:'edit', title:'User Form'})">
  <i class="fa fa-edit fa-lg"></i>
</button>
<form id="changePasswordForm" onsubmit="F1.app.submitChangePassword(event)" hidden>
  <input type="hidden" name="user_id" value="<?=$user->id?>">
  <label for="newPassword">New Password</label>
  <input type="password" id="newPassword" name="newPassword" required>
  <label for="confirmPassword">Confirm New Password</label>
  <input type="password" id="confirmPassword" name="confirmPassword" required>
  <input type="submit" class="btn-primary" value="Change Password">
</form>

<script>
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
</script>
<!-- Compiled: 2024-06-17 16:33:55 -->