# News Aggregator

The News Aggregator is a Laravel 11 application designed to collect, process, and store news articles from various sources. It employs design patterns and SOLID principles to ensure maintainability and scalability. The system utilizes a retry policy for fault tolerance and incorporates Data Transfer Objects (DTOs) to centralize data payloads.

## Prerequisites

Ensure you have the following installed before proceeding:

- [PHP](https://www.php.net/) (version 8.2 or later recommended)
- [Composer](https://getcomposer.org/)
- [Nginx](https://www.nginx.com/) or [Apache](https://httpd.apache.org/) (for serving the application)
- [Docker](https://www.docker.com/) (optional for containerized development)

## Installation

Clone the repository and install dependencies:

```bash
cd news-aggregator

# Install PHP dependencies
composer install
```

## Environment Configuration

Copy the example environment file and set up the required configurations:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

## Running the Application

Start the development server:

```bash
php artisan serve
```

Alternatively, if using Docker:

```bash
./vendor/bin/sail up
```

The application should now be accessible at [http://localhost:8000](http://localhost:8000).

## Running Tests

Run the test suite:

```bash
php artisan test
```

## Deployment

### Deploying to Production

For production deployment, set up your web server:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan schedule:work
```

---

## News Aggregator Architecture and Design Patterns

### Architecture

The News Aggregator follows a modular architecture:

1. **News Aggregator Service**: Manages news sources and observers, orchestrating the fetching and processing of news articles.

2. **News Sources**: Implement the `NewsSourceInterface` to fetch articles from various providers.

3. **Observers**: Implement the `NewsObserverInterface` to handle events when news articles are updated.

4. **Data Transfer Objects (DTOs)**: Encapsulate article data, ensuring a consistent structure across the application.

### Design Patterns and SOLID Principles

- **Strategy Pattern**: The `RetryPolicy` class allows for different retry strategies, promoting flexibility and adherence to the Open/Closed Principle.

- **Observer Pattern**: Observers listen for updates from the News Aggregator, enabling a decoupled design and adherence to the Dependency Inversion Principle.

- **Factory Pattern**: The `NewsAggregator` class can instantiate various news sources, promoting the Open/Closed Principle.

- **Data Transfer Object (DTO) Pattern**: The `ArticleData` class encapsulates article data, ensuring a consistent structure and promoting the Single Responsibility Principle.

### Retry Policy Implementation

The `RetryPolicy` class implements a retry mechanism for fault tolerance. It allows for different retry strategies, which can be swapped between test and production environments. This approach adheres to the Open/Closed Principle, as the retry policy can be extended without modifying existing code.

### Data Transfer Objects (DTOs)

The `ArticleData` class is a Data Transfer Object that encapsulates article data. It ensures a consistent structure across the application and promotes the Single Responsibility Principle by handling data representation separately from business logic.

## Task Scheduling for Hourly News Fetch

To ensure that news articles are fetched every hour, the application utilizes Laravel's task scheduling feature. This approach allows for the definition of scheduled tasks within the application itself, eliminating the need for manual cron entries. citeturn0search0

### Scheduling the News Fetch Command

In the `app/Console/Kernel.php` file, the `schedule` method is used to define the frequency of the `news:fetch` command. To execute the command every hour, add the following line:

```php
$schedule->command('news:fetch')->hourly();
```

This configuration ensures that the `news:fetch` command runs at the start of every hour. citeturn0search0

### Setting Up the Scheduler

For the scheduler to run, a cron entry is required on the server. Add the following line to your server's crontab:

```bash
* * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
```

This cron job runs every minute, allowing Laravel to evaluate and execute any scheduled tasks that are due. citeturn0search0

## Dependency Injection in NewsServiceProvider

The `NewsServiceProvider` class is responsible for binding the `NewsAggregator` service into the application's service container. This approach utilizes dependency injection to manage class dependencies, promoting loose coupling and enhancing testability.

### Registering the NewsAggregator Singleton

Within the `register` method of the `NewsServiceProvider`, the `NewsAggregator` is registered as a singleton:

```php
$this->app->singleton(NewsAggregator::class, function () {
    $aggregator = new NewsAggregator();

    $aggregator->addSource(new NewsApiSource(config('news_sources.newsapi.api_key')));
    if (!app()->environment('testing')) {
        $aggregator->addSource(new GuardianSource(config('news_sources.guardian.api_key')));
        $aggregator->addSource(new NewYorkTimesSource(config('news_sources.new_york_times.api_key')));
    }

    // Add observers
    $aggregator->addObserver(new CacheObserver());

    return $aggregator;
});
```

This configuration ensures that the same instance of `NewsAggregator` is used throughout the application, adhering to the Singleton design pattern.

### Adding News Sources and Observers

The `NewsAggregator` is configured with various news sources and observers:

- **News Sources**: Instances of `NewsApiSource`, `GuardianSource`, and `NewYorkTimesSource` are added to the aggregator. The `GuardianSource` and `NewYorkTimesSource` are conditionally added based on the application's environment, ensuring they are not included during testing.

- **Observers**: An instance of `CacheObserver` is added to the aggregator to handle caching concerns.

This setup demonstrates the use of dependency injection to manage class dependencies, promoting a clean and maintainable codebase.

## Author

Developed by [MusahMusah](https://github.com/musahmusah). 