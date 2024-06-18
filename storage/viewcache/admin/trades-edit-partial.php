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
</style>
<div class="flex-col h100 w100">
  <div class="container">
    <header>
<?php if( $isEdit ): ?>
      <h1>Edit Trade ID: <?=$id?></h1>
<?php endif; ?>
<?php if( $isNew ): ?>
      <h1>Add New Trade</h1>
<?php endif; ?>
    </header>
    <form id="trade-form" method="post">
      
      <input type="hidden" name="id" value="<?=$trade->id?>">

      <label for="client_id" required>Client:</label>
      <select id="client_id" name="client_id" data-value="<?=$trade->client_id?>" required>
<?php foreach( $clients as $client ): ?>
        <option value="<?=$client->client_id?>"><?=$client->name?></option>
<?php endforeach; ?>
      </select>  
      
      <label for="trade_id" required>Trade ID:</label>
      <input type="text" id="trade_id" name="trade_id" value="<?=escape($trade->trade_id)?>" required>

      <label for="sda_fia" required>SDA/FIA:</label>
      <select id="sda_fia" name="sda_fia" data-value="<?=$trade->sda_fia?>" required>
        <option></option>
        <option>SDA</option>
        <option>FIA</option>
        <option>SDA/FIA</option>
      </select>  

      <label for="otc" required>OTC:</label>
      <select id="otc" name="otc" data-value="<?=$trade->otc?>" required>
        <option></option>
        <option>VALR</option>
        <option>OVEX</option>
      </select>  

      <label for="date" required>Trade Date:</label>
      <input type="date" id="date" name="date" value="<?=$trade->date?>" max="<?=date('Y-m-d')?>" required>

      <label for="zar_sent" required>Trade Amount (R):</label>
      <input id="zar_sent" name="zar_sent" value="<?=currency($trade->zar_sent, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)" required>

      <label for="usd_bought" required>TUSD Bought ($):</label>
      <input type="text" id="usd_bought" name="usd_bought" value="<?=currency($trade->usd_bought, '$ ', ' ', 2)?>"
        onchange="this.value = F1.lib.Utils.currency(this.value, '$ ', ' ', 2)" required>

      <label for="forex_rate" required>Forex (USDZAR) Rate (R):</label>
      <input type="text" id="forex_rate" name="forex_rate" value="<?=currency($trade->forex_rate, 'R ', ' ', 3)?>"
        onchange="this.value = F1.lib.Utils.currency(this.value, 'R ', ' ', 3)" required>

      <label for="zar_profit" required>Profit Amount (R):</label>
      <input id="zar_profit" name="zar_profit" value="<?=currency($trade->zar_profit, 'R ', ' ', 2)?>"
        onchange="this.value = F1.lib.Utils.currency(this.value, 'R ', ' ', 2)" required>

      <label for="percent_return" required>% Return:</label>
      <input type="text" id="percent_return" name="percent_return" value="<?=currency($trade->percent_return, '', ' ', 2)?>"
        onchange="this.value = F1.lib.Utils.currency(this.value, '', ' ', 2)" required>

      <label for="trade_fee">Trade Fee %:</label>
      <input type="text" id="trade_fee" name="trade_fee" value="<?=currency($trade->trade_fee, '', ' ', 2)?>"
        onchange="this.value = F1.lib.Utils.currency(this.value, '', ' ', 2)">

      <label for="amount_covered">Amount Covered:</label>
      <input id="amount_covered" name="amount_covered" value="<?=currency($trade->amount_covered, 'R ')?>"
        onchange="this.value = F1.lib.Utils.currency(this.value)">

      <label for="allocated_pins">Allocated Pins:</label>
      <textarea id="allocated_pins" name="allocated_pins"><?=escape($trade->allocated_pins)?></textarea>

<?php if( $trade->id > 0 ): ?>
      <label for="updated_by">Updated By:</label>
      <input type="text" id="updated_by" name="updated_by" value="<?=$trade->updated_by?>" readonly>

      <label for="updated_at">Updated At:</label>
      <input type="text" id="updated_at" name="updated_at" value="<?=$trade->updated_at?>" readonly>

      <label for="created_at">Created At:</label>
      <input type="text" id="created_at" name="created_at" value="<?=$trade->created_at?>" readonly>

      <label for="created_by">Created By:</label>
      <input type="text" id="created_by" name="created_by" value="<?=$trade->created_by?>" readonly>
<?php endif; ?>      

      <footer>
        <button type="submit" class="btn-success">Save</button>
        <button type="button" class="btn-default" onclick="F1.app.onExit(event, history.back.bind(history))">Cancel</button>
      </footer>
    </form>
  </div>
</div>
<script>
/* global F1 */

F1.deferred.push(function initTradesEditPageView(app) {

  console.log('initTradesEditPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;

  app.el.form = Utils.getEl('trade-form');

  app.el.form.onsubmit = function (e) {
    e.preventDefault();
    const form = e.target;
    console.log('onSubmit start...');
    app.showBusy(app.el.content);
    /* do some validation */
    const dateInput = Utils.getEl('date');
    if (!dateInput.value) {
      app.removeBusy(app.el.content);
      app.alert({ message: 'Trade Date is required.', theme: 'error' });
      dateInput.focus();
      return;
    }
    /* submit form */
    Ajax.submit(form, { extraData: { action: 'saveTrade' } })
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) {
          console.log('submit.trade.fail:', resp);
          let message = resp.errors || resp.message || resp;
          if ( typeof message == 'object' ) message = Object.values(message).join('<br>');
          app.alert({ message, theme: 'error' });
          return;
        }        
        console.log('submit.trade.success:', resp);
        if (resp.goto === 'back') history.back();
        else if (resp.goto) window.location.href = resp.goto;
        else window.location.reload();
      })
      .catch(function (error) {
        console.log('submit.trade.fail says Hi!');
        app.removeBusy(app.el.content);
        let message = error.errors || error.message || error;
        if ( typeof message == 'object' ) message = Object.values(message).join('<br>');
        app.alert({ message, theme: 'error' });
      });
  };


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* initialize select inputs */
  app.el.clientInput = Utils.getEl('client_id');
  app.el.clientInput.value = app.el.clientInput.dataset.value;  
  
  app.el.sdafiaInput = Utils.getEl('sda_fia');
  app.el.sdafiaInput.value = app.el.sdafiaInput.dataset.value;

  app.el.otcInput = Utils.getEl('otc');
  app.el.otcInput.value = app.el.otcInput.dataset.value;  

});
</script>

<!-- Compiled: 2024-06-17 23:10:51 -->