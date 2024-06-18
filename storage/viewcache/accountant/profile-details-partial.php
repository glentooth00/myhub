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
  display: flex;
  flex-direction: column;
  position: relative;
  background-color: #fefefe;
  margin: auto;
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

form button {
  margin-top: 1.5rem;
  padding: 13px;
}

</style>
<div class="content-wrapper">
  <header>
    <h1><?=$user->first_name . " " . $user->last_name?></h1>
    <h4>User ID: <?=$user->user_id?></h4>
  </header>
  <section class="flex-row flex-gap">
    <button class="btn-round btn-primary" type="button"><span>A</span></button>
    <button class="btn-round btn-primary" type="button"><span>B</span></button>
    <button class="btn-round btn-primary" type="button" title="Change Password" onclick="F1.app.showChangePasswordForm()">
      <i class="fa fa-key fa-lg"></i>
    </button>
  </section>
  <section>
    <ul>
      <li><label>Username:</label> <span><?=$user->username?></span></li>
      <li><label>Email:</label> <span><?=$user->email?></span></li>
      <li><label>Status:</label> <span><?=$user->status?></span></li>
      <li><label>Created At:</label> <span><?=$user->created_at?></span></li>
      <li><label>Updated At:</label> <span><?=$user->updated_at?></span></li>
      <li><label>Last Login At:</label> <span><?=$user->last_login_at?></span></li>
      <li><label>Last Activity At:</label> <span><?=$user->last_activity_at?></span></li>
      <li><label>Failed Login Attempts:</label> <span><?=$user->failed_logins?></span></li>
    </ul>      
  </section>
</div>
<!-- 
<button class="fab btn-round btn-primary" type="button" 
  onclick="F1.app.navigateTo({url:'account/user?view=edit&id=<?=$user->id?>', view:'edit', title:'User Form'})">
  <i class="fa fa-edit fa-lg"></i>
</button>
 -->
<template id="tplChangePasswordForm">
  <form onsubmit="F1.app.submitChangePassword(event)">
    <label for="oldPassword">Current Password</label>
    <input type="password" id="oldPassword" name="oldPassword" required>
    <label for="newPassword">New Password</label>
    <input type="password" id="newPassword" name="newPassword" required>
    <label for="confirmPassword">Confirm New Password</label>
    <input type="password" id="confirmPassword" name="confirmPassword" required>
    <input type="hidden" name="userId" value="<?=$user->id?>">
    <button type="submit" class="btn-primary">Change Password</button>
  </form>
</template>

<script>
/* global F1 */

F1.deferred.push(function initProfileDetailsPageView(app) {

  console.log('initProfileDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;

  app.formTemplate = Utils.getEl('tplChangePasswordForm');

  app.showChangePasswordForm = function() {
    app.modal = new Popup({
      type: 'modal',
      content: app.formTemplate.innerHTML,
      position: 'center',
    });
    app.modal.show();
  };

  app.submitChangePassword = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy();
    Ajax.submit( form, { extraData: { action: 'changePassword' } } )
      .then(function (resp) {
        app.modal.close();
        if (!resp.success) return app.handleAjaxError(resp, 'submit.cpw');
        app.removeBusy();
        console.log('submit.cpw.success:', resp);
        app.toast({ message: resp.message, afterClose: () => app.redirect(resp.goto) });        
      })
      .catch((err) => app.handleAjaxError(err, 'submit.cpw')); 
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

});
</script>
<!-- Compiled: 2024-06-17 16:03:58 -->