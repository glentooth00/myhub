# My Currency Hub

Manage your Currency Hub account, whether you're a client, 
accountant, or a system administrator.

---

# FIA & SDA Allowance Tracking
Provide "FIA Available" and "SDA Available" indicators to traders. 
Prevent over-trading and ensure traders trade within governmental 
and client-mandated investment limits.  

---

## SDA Available ATM

TY = This Year  
LY = Last Year  
ATM = At The Moment  

GSDA = Government Standard Annual Investment Allowance TY  
CSDA = Client SDA Mandate TY

SDA Mandate Remaining ATM = Min( GSDA, CSDA ) - Total SDA Trade Amounts TY

SDA Available ATM = SDA Mandate Remaining ATM

---

## FIA Available ATM

TCC = FIA Tax Clearance Certificate  
GFIA = Government Annual Foreign Investment Allowance TY  
CFIA = Client FIA Mandate TY  

Total Rollovers from LY ATM = Sum all approved TCCs with rollover amounts, issued LY  

FIA Mandate Remaining ATM = Min( GFIA, CFIA ) - Total FIA Trades (Usage) TY  

Total FIA Approved ATM = Sum of Approved TCCs TY + Total Rollovers from LY ATM  

FIA Approved Remaining ATM = Total FIA Approved ATM - Total FIA Trade Amounts (Usage) TY  

FIA Available ATM = Min( FIA Mandate Remaining ATM, FIA Approved Remaining ATM )  

---

## FIA & SDA Allowance Tracking Rules

- FIA TCCs are sorted by issue date, so the oldest TCCs are used first.
- Trades are also sorted by date, so the oldest trades are covered first.
- TCCs are only valid for 1 year from date of issue.
- TCCs can only cover trades that happened BEFORE the TCC expires.
- TCCs can only cover trades that happened AFTER the TCC was issued.
- There are two types of cover. FIA and SDA.
- Max available SDA per year is 1000000, but can be lower if the client provides a lower SDA mandate.
- Max available FIA per year is 10000000, but can be lower if the client provides a lower FIA mandate.
- SDA cover do not require TCCs or approval (except for the SDA mandate).
- SDA available = min(1000000, client->sda_mandate) - client->sda_used.
- FIA remaining = min(10000000, client->fia_mandate) - client->fia_used.
- FIA available = client->fia_approved (sum of all approved pins atm + sum of expired r/o amounts used this year) - client->fia_used.
- SDA allowance should be allocated first.
- Trades should be tagged as "SDA", "FIA" or "SDA/FIA" via the trade->sda_fia field.
- TCC Allocations schema: [ trade_id => amount_allocated_to_trade, ... ]
- TCC Allocations Example: allocated_trades = json_encode( [ 'CH1234' => 150000, '345631' => 250000, ... ] )
- Trade Allocations schema: [ tcc_pin => amount_covered_by_pin, ... ]
- Trade Allocations Example: allocated_pins = json_encode( [ '_SDA_' => 180000, 'XDR23FD9' => 320000, ... ] )

---

## FIA & SDA Allowance Tracking Processes

1.  Roll-over approved TCCs with remaining cover  
      1.1. Roll-over approved TCCs with remaining cover from the year before TY. (Manual action, usually run retrospectively to correct allocation errors)      1.2. Roll-over approved TCCs with remaining cover TY to next year. (Manual action, usually after trading closed for the year)  

2.  Expire any approved TCCs older than 1 year. (Run at least once per day or just before allocating TCCs to trades)  

3. Allocate SDA Remaining ATM (To trades requiring cover)  
    9.1. Calculate SDA Remaining ATM  
    9.2. Get all SDA and SDA/FIA Trades requiring cover ATM  
    9.3. Allocate SDA cover to SDA Trades until SDA Remaining ATM is 0 or all SDA Trades are covered.  

4. Allocate FIA Available ATM (To trades requiring cover)  
    10.1. Get all approved TCCs with rollover amounts, issued LY  
    10.2. Get all approved TCCs issued TY  
    10.3. Get all Trades requiring cover ATM  
    10.4. Allocate FIA cover to Trades until FIA Remaining ATM is 0 or all Trades are covered.  
    10.5. Save All Trades with changes.  
    10.6. Save All TCCs with changes.  

5. Expire all approved TCCs fully used TY

6. Update Client Stats ATM  
   3.1. Get all approved TCCs with rollover amounts, issued LY, ATM  
   3.2. Get all approved TCCs issued TY ATM  
   3.3. Get all Trades executed TY ATM  
   3.4. Calculate FIA Approved TY ATM  
   3.5. Calculate SDA Used TY ATM  
   3.6. Calculate FIA Used TY ATM  
   3.7. Get ALL Pending TCCs ATM  
   3.8. Get all Declined TCCs TY ATM  
   3.9. Calculate Total FIA Pending ATM  
   3.10. Calculate Total FIA Declined ATM  
   3.11. Save Client Stats  

7. Update Client on S2.  

8. Sanity check client data (This year / All Time) (Manual action, any time).  
   7.1. Run sanity check test suite per Trade and collectively for all Trades.  
   7.2. Run sanity check test suite per TCC and collectively for all TCCs.  

9. Archive all TCCs and Trades older than 2 years. (Manual action, usually after trading closed for the year).

---

# Project Structure
```plaintext
/myhub
│
├── /api
│   └── /v1
│       ├── /client
│       │   └── /statement
│       │
│       ├── /clients
│       │   └── /tradesheet
│       │
│       ├── /tccs
│       │   └── /expire
│       │
│       ├── /trades
│       ├── /users
│       └── api.php
│
├── /assets
│   ├── /css
│   ├── /images
│   └── /js
│
├── /backups
│
├── /config
│   ├── .env-local-example
│   ├── google.php
│   ├── mail.php
│   └── ...
│
├── /database
│   ├── /migrations
│   ├── /schema
│   ├── /scripts
│   ├── /seeddata
│   └── /views
│
├── /includes
│   ├── /models
│   │   ├── Client.php
│   │   ├── ClientState.php
│   │   ├── ClientStatement.php
│   │   ├── Trade.php
│   │   ├── Tcc.php
│   │   ├── User.php
│   │   └── ...
│   │
│   ├── /services
│   │   ├── AppBackup.php
│   │   ├── AppForm.php
│   │   ├── AppLogger.php
│   │   ├── AppMailer.php
│   │   ├── AppRequest.php
│   │   ├── AppView.php
│   │   └── GoogleAPI.php
│   │
│   ├── autoload.php
│   ├── exceptions.php
│   └── helpers.php
│
├── /pages
│   ├── /templates
│   │   └── /default-theme
│   │       ├── /accountant
│   │       ├── /admin
│   │       ├── /client
│   │       ├── /email
│   │       └── /site
│   │
│   ├── /accountant
│   │   ├── /clients
│   │   ├── /dashboard
│   │   ├── /profile
│   │   ├── /tccs
│   │   └── /trades
│   │
│   ├── /admin
│   │   ├── /clients
│   │   ├── /dashboard
│   │   ├── /referrers
│   │   ├── /settings
│   │   ├── /tccs
│   │   ├── /tools
│   │   ├── /trades
│   │   └── /users
│   │
│   ├── /client
│   │   ├── /dashboard
│   │   └── /statement
│   │
│   └── /user
│       ├── /error
│       ├── /login
│       ├── /logout
│       ├── /register
│       └── /reset-pw
│
├── /storage
│   ├── /logs
│   ├── /manifest
│   └── /viewscache
│
├── /tests
│
├── /uploads
│
├── /vendors
│   ├── /F1
│   ├── /FAwesome
│   ├── /Firebase
│   ├── /FPDF
│   ├── /GridStack
│   ├── /JQDTables
│   ├── /JQuery
│   ├── /PHPMailer
│   └── /Tippy
│
├── .htaccess
├── .env-local
├── favicon.ico
├── index.php
└── README.md
