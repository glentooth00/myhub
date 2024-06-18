<?php return array(


  /// Pages ///
  'pages' => [



    'user' => [
    ],



    'admin' => [


      'dashboard' => [
      ],


      'clients' => [
      ],


      'tccs' => [
      ],


      'trades' => [
      ],


      'users' => [

        'profile' => [

          [ 'Fetch the currently logged in user\'s profile if no id is provided', 'test1' ],
          [ 'Fetch the user\'s profile by id if one is provided', 'test2' ],
          [ 'Hide the "Change Password" action if the user is not the logged in user or super', 'test3' ],
          [ 'Clicking on the "Change Password" action should show the "New Password" modal popup form.', 'test4' ],
          [ 'Both "New Password" and "Confirm Password" should be required.', 'test5' ],
          [ 'Both "New Password" and "Confirm Password" should be at least 8 characters.', 'test6' ],
          [ 'Both "New Password" and "Confirm Password" should match.', 'test7' ],
          [ 'Clicking on the "Change Password" button should save the new password, close the form popup, and show a success toast.', 'test8' ],
          [ 'Clicking on the "Close X" should close the "New Password" popup form.', 'test9' ],

        ],

        'list' => [
        ],

        'edit' => [
        ],

      ],

    ],


    'accountant' => [
    ],


  ],



  /// API ///
  'api' => [

    'v1' => [

      'tradebook' => [
      ],

      'clients' => [
      ],

    ],

  ],


  '404' => [
  ],

);