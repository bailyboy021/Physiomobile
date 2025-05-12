<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


# Physiomobile

Physiomobile is a RESTful API built with Laravel 11, designed to handle transactional data using a MySQL database. It includes integrated API documentation powered by Swagger (via l5-swagger).


## Technologies Used

- **Framework**: Laravel 11 (PHP 8.2)
- **Database**: MySQL
- **API Documentation**: Swagger (l5-swagger)

## Installation

1.  Clone the repository:

    ```bash
    git clone https://github.com/bailyboy021/Physiomobile.git
    ```

2.  Navigate to the project directory:

    ```bash
    cd Physiomobile
    ```

3.  Install Composer dependencies:

    ```bash
    composer install
    ```

4. Copy the .env.example file to .env and configure your environment settings (database, access key, Swagger):

   ```bash
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=physiomobile
   DB_USERNAME=root
   DB_PASSWORD=

   ACCESS_KEY=your_access_key

   L5_SWAGGER_GENERATE_ALWAYS=true
   L5_SWAGGER_API_VERSION=1.0.0
   L5_SWAGGER_TITLE="API Documentation"
   L5_SWAGGER_DESCRIPTION="Documentation for Physiomobile API"
   L5_SWAGGER_SCHEMES=https
   L5_SWAGGER_BASE_PATH=/api

5. Run database migrations:

   ```bash
   php artisan migrate --seed

6. Start the development server:

   ```bash
   php artisan serve

## API Documentation

To view the API documentation:
1. Make sure the application is running locally.
2. Open your browser and go to:

   ```bash
   http://localhost:8000/api/documentation
