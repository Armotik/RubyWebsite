## Install

```bash
git clone git@github.com:Armotik/RubyWebsite.git
cd RubyWebsite
composer install

# Create a .env file
cp .env.example .env

# Create a database
# Edit the .env file to match your database settings
php bin/console doctrine:database:create

# Create the database schema
php bin/console doctrine:schema:create

# Load the fixtures
php bin/console doctrine:fixtures:load

# Run the server
symfony server:start
```