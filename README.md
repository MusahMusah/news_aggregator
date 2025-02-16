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
        - `filter[categories.name]`: Filter articles by category's name.
        - `filter[published_at]`: Filter articles by publication date.
        - `include=categories,authors`: Include authors and categories in payload.
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
                "id": 1,
                "title": "Three Israeli hostages freed after dispute threatened Gaza ceasefire - CNN",
                "author": null,
                "content": "Three Israeli hostages have been freed from Gaza under a ceasefire agreement between Israel and Hamas after a dispute this week threatened to derail the deal.\r\nAmerican-Israeli Sagui Dekel-Chen, Russ… [+4917 chars]",
                "category": null,
                "description": "Three Israeli hostages were handed over to the Red Cross in Gaza by Palestinian militants on Saturday, in the sixth exchange of hostages and Palestinian prisoners under the ceasefire deal that came into effect last month.",
                "source": "NewsAPI - CNN",
                "url": "https://www.cnn.com/2025/02/15/middleeast/israel-hamas-hostage-release-feb15-intl-hnk/index.html",
                "image": "https://media.cnn.com/api/v1/images/stellar/prod/ap25046318714148.jpg?c=16x9&q=w_800,c_fill",
                "published_at": "2025-02-15T09:10:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 2,
                "title": "MSC 2025: Scholz rejects far right, upholds Ukraine support - DW (English)",
                "author": null,
                "content": "German Chancellor Olaf Scholz said during an interview after his speech at the Munich Security Conference that a future government should create an exemption for spending on defense and security when… [+2056 chars]",
                "category": null,
                "description": "German Chancellor Olaf Scholz has opened the second day of the Munich Security Conference. He rejected working with the far right after a divisive speech by US VP Vance on Friday. DW has more.",
                "source": "NewsAPI - DW (English)",
                "url": "https://www.dw.com/en/msc-2025-scholz-rejects-far-right-upholds-ukraine-support/live-71599568",
                "image": "https://static.dw.com/image/71619480_6.jpg",
                "published_at": "2025-02-15T08:15:00+00:00",
                "categories": []
            },
            {
                "id": 3,
                "title": "Horoscope for Saturday, February 15, 2025 - Chicago Sun-Times",
                "author": null,
                "content": "Moon Alert\r\nAvoid shopping or important decisions from 2 to 6 a.m. Chicago time. After that, the moon moves from Virgo into Libra.\r\nAries (March 21-April 19)\r\nTread carefully, because discussions wit… [+3771 chars]",
                "category": null,
                "description": null,
                "source": "NewsAPI - Suntimes.com",
                "url": "https://chicago.suntimes.com/horoscopes/2025/02/15/horoscopes-today-saturday-february-15-2025",
                "image": "https://cst.brightspotcdn.com/dims4/default/2145dbd/2147483647/strip/true/crop/870x497+0+67/resize/1461x834!/quality/90/?url=https%3A%2F%2Fchorus-production-cst-web.s3.us-east-1.amazonaws.com%2Fbrightspot%2Fac%2Ffd%2F790f04b15195427014757adc0272%2Fgeorgia-nicols.jpg",
                "published_at": "2025-02-15T06:02:13+00:00",
                "categories": []
            },
            {
                "id": 4,
                "title": "Keyonte George will play in All-Star game after Rising Stars win - KSL.com",
                "author": null,
                "content": "SAN FRANCISCO Keyonte George called game, and now he'll be playing on All-Star Sunday.\r\nGeorge hit the game-winning bucket for \"Team C\" on Friday in the championship game of the Rising Stars competit… [+1549 chars]",
                "category": null,
                "description": "Keyonte George called game — and now he'll be playing on All-Star Sunday.",
                "source": "NewsAPI - KSL.com",
                "url": "https://www.ksl.com/article/51254110/keyonte-george-will-play-in-all-star-game-after-rising-stars-win-",
                "image": "https://img.ksl.com/slc/3041/304176/30417641.jpeg?filter=kslv2/responsive_story_lg",
                "published_at": "2025-02-15T05:24:24+00:00",
                "categories": []
            },
            {
                "id": 5,
                "title": "Federal judge hands Musk’s DOGE a win on data access at 3 agencies - ABC News",
                "author": null,
                "content": null,
                "category": null,
                "description": null,
                "source": "NewsAPI - ABC News",
                "url":"https://abcnews.go.com/Politics/federal-judge-hands-musks-doge-win-data-access/story?id\\\=118851973",
                "image": null,
                "published_at": "2025-02-15T05:11:19+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 6,
                "title": "Trump's job cuts: Anger, chaos and confusion take hold as federal workers face mass layoffs - The Associated Press",
                "author": null,
                "content": "NEW YORK (AP) Workers across the country responded with anger and confusion Friday as they grappled with the Trump administration s aggressive effort to shrink the size of the federal workforce by or… [+9742 chars]",
                "category": null,
                "description": "Federal workers were responding with anger and confusion Friday as they grappled with the Trump administration’s latest effort to shrink the size of the federal workforce by ordering agencies to lay off probationary employees who have yet to qualify for civil…",
                "source": "NewsAPI - Associated Press",
                "url": "https://apnews.com/article/trump-firing-probation-workforce-buyouts-layoffs-doge-159a6de411622c2eb651016b1e99da37",
                "image": "https://dims.apnews.com/dims4/default/c03d34b/2147483647/strip/true/crop/4323x2432+0+225/resize/1440x810!/quality/90/?url=https%3A%2F%2Fassets.apnews.com%2Ff6%2Fef%2Fb447c386aed921b95de41bf9d695%2Fcbf9c83827c049c99de488ebc7fc15ad",
                "published_at": "2025-02-15T04:31:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 7,
                "title": "White House forcing out top leadership at National Archives in major shakeup - CNN",
                "author": null,
                "content": "The Trump administration is forcing out senior leadership at the National Archives and Records Administration in a major shakeup, according to a source familiar. President Donald Trump has been highl… [+2514 chars]",
                "category": null,
                "description": "The Trump administration is forcing out senior leadership at the National Archives and Records Administration in a major shakeup, according to a source familiar. President Donald Trump has been highly critical of the archives since the agency asked the Depart…",
                "source": "NewsAPI - CNN",
                "url": "https://www.cnn.com/2025/02/14/politics/national-archives-leadership-forced-out-trump/index.html",
                "image": "https://media.cnn.com/api/v1/images/stellar/prod/gettyimages-1246620320.jpg?c=16x9&q=w_800,c_fill",
                "published_at": "2025-02-15T04:17:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 8,
                "title": "Most Energetic Cosmic Neutrino Ever Observed By KM3NeT Deep Sea Telescope - Hackaday",
                "author": null,
                "content": "On February 13th of 2023, ARCA of the kilometre cubic neutrino telescope (KM3NeT) detected a neutrino with an estimated energy of about 220 PeV. This event, called KM3-230213A, is the most energetic … [+1378 chars]",
                "category": null,
                "description": "On February 13th of 2023, ARCA of the kilometre cubic neutrino telescope (KM3NeT) detected a neutrino with an estimated energy of about 220 PeV. This event, called KM3-230213A, is the most energeti…",
                "source": "NewsAPI - Hackaday",
                "url": "https://hackaday.com/2025/02/14/most-energetic-cosmic-neutrino-ever-observed-by-km3net-deep-sea-telescope/",
                "image": "https://hackaday.com/wp-content/uploads/2025/02/15.-Credit-KM3NeT.png",
                "published_at": "2025-02-15T03:00:00+00:00",
                "categories": []
            },
            {
                "id": 9,
                "title": "In photos: Philadelphia throws ultimate party for Eagles Super Bowl parade - Axios",
                "author": null,
                "content": "Babies were hoisted in the air. Beers were tossed. And Birds revelry reached a fever pitch in Philadelphia Friday for the Eagles' Super Bowl parade.\r\nWhy it matters: It's an all-day party to celebrat… [+3821 chars]",
                "category": null,
                "description": "About a million people are expected to attend the championship parade.",
                "source": "NewsAPI - Axios",
                "url": "https://www.axios.com/local/philadelphia/2025/02/14/eagles-super-bowl-parade-photos-party-philly",
                "image": "https://images.axios.com/414oX5WyVWe21j4xrFv0nfN4JN0=/0x178:5169x3086/1366x768/2025/02/14/1739550305570.jpg",
                "published_at": "2025-02-15T02:48:37+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 10,
                "title": "White House bars AP from Oval Office and Air Force One over \"Gulf of Mexico\" use - Axios",
                "author": null,
                "content": "The White House on Friday said it will bar the Associated Press from future events in the Oval Office and Air Force One over AP's refusal to obey President Trump's executive order renaming the Gulf o… [+4513 chars]",
                "category": null,
                "description": "Press advocates are likely to be up in arms over the government penalizing a news organization in this way.",
                "source": "NewsAPI - Axios",
                "url": "https://www.axios.com/2025/02/14/ap-trump-white-house-gulf-of-america",
                "image": "https://images.axios.com/1LJb5ZAjNnxMuwCZcG8xJ0HMrB4=/0x0:1600x900/1366x768/2025/02/14/1739557769239.png",
                "published_at": "2025-02-15T02:25:25+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 11,
                "title": "Carmelo Anthony gets ringing Pat Riley endorsement as he lands Hall of Fame finalist nod - New York Post ",
                "author": null,
                "content": "SAN FRANCISCO A formality was officialized Friday night, when Carmelo Anthony, arguably the greatest Knick since Patrick Ewing, was named a finalist for the Naismith Hall of Fame. \r\nAnd as he took th… [+4867 chars]",
                "category": null,
                "description": "A formality was made official Friday night when Carmelo Anthony, arguably the greatest Knick since Patrick Ewing, was named a finalist for the Naismith Hall of Fame.",
                "source": "NewsAPI - New York Post",
                "url": "https://nypost.com/2025/02/14/sports/carmelo-anthony-named-naismith-hall-of-fame-finalist/",
                "image": "https://nypost.com/wp-content/uploads/sites/2/2025/02/newspress-collage-t7n3xby6c-1739596158028.jpg?quality=75&strip=all&1739578184&w=1024",
                "published_at": "2025-02-15T02:25:00+00:00",
                "categories": []
            },
            {
                "id": 12,
                "title": "‘Captain America: Brave New World’ Post-Credits Scene Explained: What’s Next for Cap Ahead of ‘Avengers: Doomsday’? - Variety",
                "author": null,
                "content": "SPOILER ALERT: This article contains major spoilers for the ending of “Captain America: Brave New World,” now playing in theaters.\r\nCaptain America has saved the world once again but he still has one… [+2666 chars]",
                "category": null,
                "description": "The 'Captain America: Brave New World' post-credits scene with Leader sets up a multiverse future for the MCU and the Avengers.",
                "source": "NewsAPI - Variety",
                "url": "https://variety.com/2025/film/news/captain-america-brave-new-world-credits-scene-leader-avengers-1236305830/",
                "image": "https://variety.com/wp-content/uploads/2025/02/Cap-1.jpg?w=1000&h=563&crop=1",
                "published_at": "2025-02-15T02:00:00+00:00",
                "categories": []
            },
            {
                "id": 13,
                "title": "Bird flu case with \"flu-like symptoms\" hospitalized in Colorado - CBS News",
                "author": null,
                "content": "The Centers for Disease Control and Prevention has confirmed a third U.S. bird flu hospitalization, Wyoming's health department said Friday, after a woman was admitted to a healthcare facility in nei… [+3252 chars]",
                "category": null,
                "description": "The case marks at least the third confirmed bird flu hospitalization in the U.S.",
                "source": "NewsAPI - CBS News",
                "url": "https://www.cbsnews.com/news/bird-flu-case-woman-hospitalized-colorado/",
                "image": "https://assets2.cbsnewsstatic.com/hub/i/r/2024/12/27/0c09980b-dd05-45b1-9a3c-0d6db4ac49a7/thumbnail/1200x630/244e823b3baa30e3a958d5836b621114/bird-flu.jpg?v=f303dc12868a012283443d8b9123e5fe",
                "published_at": "2025-02-15T02:00:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 14,
                "title": "Jane Doe drops sexual assault lawsuit against Jay-Z and Sean ‘Diddy’ Combs - CNN",
                "author": null,
                "content": "A Jane Doe who alleged Sean Diddy Combs and Jay-Z sexually assaulted her 25 years ago has withdrawn her lawsuit against them.\r\nIn a notice of voluntary dismissal filed Friday, attorneys for the woman… [+2701 chars]",
                "category": null,
                "description": "A Jane Doe who alleged Sean “Diddy” Combs and Jay-Z sexually assaulted her 25 years ago has withdrawn her lawsuit against them.",
                "source": "NewsAPI - CNN",
                "url": "https://www.cnn.com/2025/02/14/entertainment/jay-z-sean-combs-lawsuit-dropped/index.html",
                "image": "https://media.cnn.com/api/v1/images/stellar/prod/gettyimages-1911124317.jpg?c=16x9&q=w_800,c_fill",
                "published_at": "2025-02-15T01:07:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            },
            {
                "id": 15,
                "title": "West Texas measles outbreak doubles to 48 cases - CNN",
                "author": null,
                "content": "The measles outbreak first reported in Gaines County, Texas, has doubled to 48 cases since a count released earlier this week, the Texas Department of State Health Services said Friday. The first two… [+3020 chars]",
                "category": null,
                "description": "The measles outbreak first reported in Gaines County, Texas, has doubled to 48 cases since a count released earlier this week, the Texas Department of State Health Services said Friday. The first two cases were identified in late January and the numbers have …",
                "source": "NewsAPI - CNN",
                "url": "https://www.cnn.com/2025/02/14/health/measles-texas-outbreak/index.html",
                "image": "https://media.cnn.com/api/v1/images/stellar/prod/gettyimages-151035299.jpg?c=16x9&q=w_800,c_fill",
                "published_at": "2025-02-15T00:24:00+00:00",
                "categories": [
                    {
                        "id": 1,
                        "name": "general"
                    }
                ]
            }
        ],
        "first_page_url": "http://news_aggregator.test/api/articles?include=categories&page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://news_aggregator.test/api/articles?include=categories&page=2",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://news_aggregator.test/api/articles?include=categories&page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://news_aggregator.test/api/articles?include=categories&page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "http://news_aggregator.test/api/articles?include=categories&page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": "http://news_aggregator.test/api/articles?include=categories&page=2",
        "path": "http://news_aggregator.test/api/articles",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 18
    }
  }
  ```

## Author

Developed by [MusahMusah](https://github.com/musahmusah).