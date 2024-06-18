<?php /* Client approved AIT PIN email template */

return <<<EOT
<p>Hi $emailView->toName,</p>

<p>Congratulations! Your SARS AIT pin has been approved.</p>

<p>
  Should your trading capital be available in your Capitec trading account 
  we will automatically add you to the active trading schedule.
</p>

<p>
  If you have withdrawn your funds then you may now proceed to credit your 
  Capitec account again and send us your 6 month statement showing the funds 
  leaving your account. The statement can be sent via our ticketing system.
</p>

<p>Happy Trading!</p>
EOT;