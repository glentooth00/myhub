<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <base href="<?=$app->baseUri?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <title>Confirmation Email Sent Ok</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background: #f3f3f3;
      font-family: 'Arial', sans-serif;
      line-height: 1.6;
    }
    header {
      background: #222222;
      color: white;
      padding: 1rem;
      text-align: center;
    }
    main {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }
    a {
      color: #007bff;
      text-decoration: none;
    }
    .ghost-button {
      display: inline-block;
      padding: 10px 20px;
      margin: 10px;
      border: 1px solid #222;
      color: #222;
      border-radius: 5px;
      transition: background 0.3s, color 0.3s;
    }
    .ghost-button:hover {
      background: #222;
      color: white;
    }
  </style>
</head>
<body>
  <header>
    <h1>Confirmation Email Sent</h1>
  </header>
  <main>
    <p>An email has been sent to your email address with instructions on how to reset your password.</p>
    <p>If you do not receive an email within 5 minutes, please check your spam folder.</p>
    <p>If you still do not receive an email, please contact us at <a href="mailto:support@currencyhub.co.za">Currency Hub Support</a></p>
    <p>&nbsp;</p>
    <p>
      <a class="ghost-button" href="user/login">Return to Login</a>
    </p>
  </main>
</body>
</html>