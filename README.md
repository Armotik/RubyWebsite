# RubyWebsite

---

## Website API used for NationsGlory Ruby server's staff

---

### Authors :
- Armotik

### Contributors :
- TheKing4012
- bocqueraz

### API Version : 1.0.0

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

# Load the fixtures
php bin/console doctrine:fixtures:load

# Run the server
symfony server:start
```

---

## Documentation

This API uses the [NelmioApiDocBundle](https://symfony.com/bundles/NelmioApiDocBundle/current/index.html) to generate the documentation.

To access the documentation, go to the `/api/doc` route.

---

### ChangeLogs
All the changelog are available in the [changelogs'](changelogs) folder.

- [2024-03-24](changelogs/%5B2024-03-24%5D%20-%20Changelog.md)

---

### Last update : 2024-03-24
&copy; 2024 Armotik / NationsGlory - All rights reserved
