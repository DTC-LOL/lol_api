# Install LoL Api Project
This project was created with [Symfony 6](https://symfony.com/)

## Requirements
* PHP 8.0.3 or higher.

## Installation
```bash
git clone https://github.com/DTC-LOL/lol_api.git  
```

Install Composer dependencies:

```cmd 
composer install
```  

Create a .env.local and configure your .env variables : database and keys.

### Database 

Create the database :
```cmd
symfony console doctrine:database:create
```

Run migrations :
```cmd
symfony console doctrine:migrations:migrate
```

Run fixtures to populate database :
```cmd
symfony console doctrine:fixtures:load
```

## Usage

Finally, run : 
```cmd
symfony serve
```
Application runs on localhost:8000