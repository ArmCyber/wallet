# Wallet task

## Requirements

- PHP >=8.0.2
- Composer >=2.0.0

## Installation instructions

- Clone this repository ```git clone https://github.com/zakhayko/wallet.git```
- Go to project path ```cd wallet```
- Install composer dependencies ```composer install```

## Running command instructions

I created a command to run the calculations as I saw you used CLI. \
All you need to do is copying your csv file to project's ```storage/commision-csv/``` directory and run ```php artisan commission-fees:calculate {filename}``` command, now you should see the output. Filename can be with or without extension (both example.csv and example are allowed).

## Testing

There is test-file.csv inside ```storage/commision-csv/```, that file I created to run my test, this is the only file in that path which is not gitignored (excluding .gitignore itself).\
To run the test, just run ```php artisan test```
