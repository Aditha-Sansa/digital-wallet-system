
## Minimum requirements

- PHP 8.2 or above
- Laravel 12+
- MySQL 8.0.30+ OR MariaDB 10.11
- Redis server 7.0.15
- Linux or Mac env to run this on localhost


## How to setup / Documentation

- Make sure you have the required environment above
- Git pull https://github.com/Aditha-Sansa/digital-wallet-system.git
- Copy the .env.example into your projects .env (Make sure you have redis set as `CACHE_STORE` among other settings )
- Run Composer install
- Run php artisan migrate
- Dont run any seeders, run the command `php artisan users:seed-and-export`command to seed the users and generate a CSV file of wallet credits for those users. Make sure you have the redis connection working properly. You can add `--total=` flag with a count if you need to seed less than 100K records.
- Use a API client tool to interact with the API. I used Insomnia
- The generated CSV is available in `storage\app\exports` folder as `users_wallet_seed.csv`
- Upload it to the bulk credit endpoint. `http://{your-local-host}/api/v1/wallet/bulk-credit`
- The credit process will start to run and activity_id will come in the response.
- Any transactions that are failed can be retried from the `http://{your-local-host}/api/v1/wallet/bulk-credit/retry` endpoint. Send the activity_id you got from the bulk credit endpoint as a json post request and it will start the retry process. For ex: `{
	"activity_id": "14decf5f-a208-43d3-b603-1389951d4a2a"
}`
- To emulate failed transactions I have added `BULK_CREDIT_FAIL_USER_IDS` variable to .env file. Add one or more uuid's from the csv file you generate and then make request to bulk credit to see jobs failing. When you retry, please clear the `BULK_CREDIT_FAIL_USER_IDS` values so the failed jobs will succeed next time.

## Resources referred 

Following links were referred when working on this project to get an idea how ledgers work in fintech use cases.

- https://finlego.com/tpost/c2pjjza3k1-designing-a-real-time-ledger-system-with
- https://www.linkedin.com/pulse/developing-digital-wallet-app-heres-everything-you-need-austin-roger-wbzzf/

Concept of idempotency
- https://lukasniessen.medium.com/idempotency-in-system-design-full-example-80e9027e7bea
- https://blog.bytebytego.com/p/mastering-idempotency-building-reliable
- https://brandur.org/idempotency-keys
