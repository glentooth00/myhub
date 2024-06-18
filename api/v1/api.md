# MyHub API v1

## Authentication

1. HTTP_X_API_KEY verification.
2. HTTP_USER_AGENT id verification.
3. HTTP_AUTHORIZATION bearer token decryption and user identification.
4. ```php if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 ); ```


---

## Clients

### Client STAT Fields:  
- `trading_capital`
- `sda_mandate`
- `fia_mandate`
- `bank`
- `accountant`
- `fia_approved`
- `sda_used`
- `fia_used`

### Client Endpoints

GET https://mycurrencyhub.co.za/api/v1/clients?stat=count // Get the total number of clients  

GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=1  // Return ALL clients as CSV. Include all required fields - Trading sheet  
GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=2  // Return ALL clients as CSV. Include only STAT fields - Trading sheet  

GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=3  // Return ALL clients as CSV. Include only STAT fields - Google Clients Proxy Sheet, `;` delimited!
GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=4  // Return ALL clients as CSV. Include all fields - Google Clients Proxy Sheet, `;` delimited!

GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=1&offset=0&limit=10  // Start from client 0, take 10 clients. Include all fields.  
GET https://mycurrencyhub.co.za/api/v1/clients?fieldset=2&offset=0&limit=10  // Start from client 0, take 10 clients. Include only STAT fields.  

POST https://mycurrencyhub.co.za/api/v1/clients/tradesheet // Get STAT fields for the list of clients provided.  
```json
{
	"action": "provideClientStats",
	"clients": ["clientUid1", "clientUid2", "clientUid3"]
}
```

GET https://mycurrencyhub.co.za/api/v1/client/statement?cuid=cjdev12&year=2024&pdf=0  // Return the client's PDF or Client info and Statment Data.
GET https://mycurrencyhub.co.za/api/v1/client/statement?cidn=9401255148087&year=2024&pdf=0  // Return the client's PDF or Client info and Statment Data.

GET https://mycurrencyhub.co.za/api/v1/client/info  // Return the client's basic auth info.
GET https://mycurrencyhub.co.za/api/v1/client/statements/list  // Return the client's basic auth info.

---

## Trades


---

## TCCs

