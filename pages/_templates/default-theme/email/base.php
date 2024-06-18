<?php /* email template */

return <<<EOT
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 20px;
    }
    .page {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .header {
      font-size: 24px;
      font-weight: bold;
      color: #333333;
      margin-bottom: 20px;
    }
    .content {
      font-size: 16px;
      color: #666666;
      line-height: 1.5;
    }
    .content a {
      color: #0066cc;
      text-decoration: none;
    }
    .footer {
      margin-top: 32px;
      font-weight: bold;
      color: #333333;
    }
    a {
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">$emailView->title</div>
    <div class="content">
      $emailView->content
    </div> <!-- .content -->
    <div class="footer">
      <p>Regards,<br>The CURRENCY HUB Team</p>
    </div>
  </div> <!-- .page -->
</body>
</html>
EOT;