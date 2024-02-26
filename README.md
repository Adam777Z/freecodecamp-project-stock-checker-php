**freeCodeCamp** - Information Security and Quality Assurance Project
------

**Stock Price Checker**

### User Stories:

1. I can **GET** `/api/stock-prices` with form data containing a Nasdaq _stock_ ticker and recieve back an _object_.
2. In the recieved _object_, I can see the _stock_ (string, the ticker), _price_ (number), and _likes_ (number).
3. I can also pass along field _like_ as **true** (boolean) to have my like added to the stock(s). Only 1 like per IP address should be accepted.
4. If I pass along 2 stocks, the returned object will be an array with both stock's info and in addition to _likes_, it will also contain _rel\_likes_ (the difference between the likes on both) on both.
5. A good way to receive the current price is the following external API (replace 'stock' with the stock ticker and 'API\_KEY' with the API key):\
	`https://www.alphavantage.co/query?function=global_quote&symbol=stock&apikey=API_KEY`\
	(Get your free API key [here](https://www.alphavantage.co/support/#api-key))
6. All 5 tests are complete and passing.