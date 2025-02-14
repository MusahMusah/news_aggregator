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

### Retry Policy Implementation

The `RetryPolicy` class implements a retry mechanism for fault tolerance. It allows for different retry strategies, which can be swapped between test and production environments. This approach adheres to the Open/Closed Principle, as the retry policy can be extended without modifying existing code.

### Data Transfer Objects (DTOs)

The `ArticleData` class is a Data Transfer Object that encapsulates article data. It ensures a consistent structure across the application and promotes the Single Responsibility Principle by handling data representation separately from business logic.

## Task Scheduling for Hourly News Fetch

To ensure that news articles are fetched every hour, the application utilizes Laravel's task scheduling feature. This approach allows for the definition of scheduled tasks within the application itself, eliminating the need for manual cron entries.

### Scheduling the News Fetch Command

In the `routes/console.php` file, the `schedule` command is used to define the frequency of the `news:fetch` command. To execute the command every hour, add the following line:

```php
Schedule::command('news:fetch')->hourly();
```

This configuration ensures that the `news:fetch` command runs at the start of every hour.

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


## API Endpoints for Articles

The application provides the following API endpoints for retrieving articles:

- **Get All Articles**: `GET /api/articles`
    - **Description**: Retrieves a list of articles with support for filtering, sorting, and pagination.
    - **Query Parameters**:
        - `filter[title]`: Filter articles by title.
        - `filter[source]`: Filter articles by source.
        - `filter[authors.name]`: Filter articles by author's name.
        - `filter[published_at]`: Filter articles by publication date.
        - `sort=title | sort=-title`: Sort articles by specified attributes (e.g., `title`, `author`, `source`, `published_at`) using - (desc) or + (asc)
        - `page=number`: Specify the page number for pagination.
        - `per_page=number`: Specify the number of articles per page.

  **Example Request**:

  ```http
  GET /api/articles?filter[title]=technology&sort=published_at&page[number]=1
  ```

  **Response**:

  ```json
  {
    "success": true,
    "message": "Articles retrieved successfully.",
     "data": {
        "current_page": 1,
        "data": [
            {
                "id": 3,
                "title": "Attorney General Pam Bondi rails against New York leaders as she announces immigration lawsuit - The Associated Press",
                "author": null,
                "content": "WASHINGTON (AP) President Donald Trumps newly installed attorney general, Pam Bondi, went after New York leaders Wednesday over the states immigration policies, announcing a lawsuit in the latest eff… [+4611 chars]",
                "category": null,
                "description": "President Donald Trump’s newly installed attorney general, Pam Bondi, is going after New York leaders over the state’s immigration policies. She announced a lawsuit against them in the latest effort by the Republican administration to carry out the president’…",
                "source": "NewsAPI - Associated Press",
                "url": "https://apnews.com/article/justice-department-immigration-pam-bondi-trump-4829db2b93afcfa35194014f160d7edb",
                "image": "https://dims.apnews.com/dims4/default/d0b7601/2147483647/strip/true/crop/6728x3785+0+355/resize/1440x810!/quality/90/?url=https%3A%2F%2Fassets.apnews.com%2F72%2Fd8%2F603dd0f838449620586eb630ac86%2Fa5ca3ed54fa34ecba61c2c812c947e3a",
                "published_at": "2025-02-13T03:22:00+00:00",
                "authors": [
                    {
                        "id": 2,
                        "name": "ALANNA DURKIN RICHER"
                    },
                    {
                        "id": 3,
                        "name": "ANTHONY IZAGUIRRE"
                    }
                ]
            }
        ],
        "first_page_url": "http://news_aggregator.test/api/articles?include=authors&filter%5Bauthors.name%5D=Anthony&page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://news_aggregator.test/api/articles?include=authors&filter%5Bauthors.name%5D=Anthony&page=1",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://news_aggregator.test/api/articles?include=authors&filter%5Bauthors.name%5D=Anthony&page=1",
                "label": "1",
                "active": true
            },
            {
                "url": null,
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": null,
        "path": "http://news_aggregator.test/api/articles",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
  }
  ```

## Author

Developed by [MusahMusah](https://github.com/musahmusah).