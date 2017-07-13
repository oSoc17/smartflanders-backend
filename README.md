# SmartFlanders Backend
This repo contains the PHP backend code for the Smart Flanders project.

## Installation
To install the project, run `composer install`.

Note that some parameters in the project are stored in a `.env` file (https://github.com/vlucas/phpdotenv).
The `.env` file is not included in the repository because some of these parameters are confidential. The following
reduced `.env` file contains parameters that are publicly available, add it to the project root to test the server.
This will make the Gent and Kortrijk datasets work (at `http://localhost:3000/parking/Kortrijk` and
`http://localhost:3000/parking/Gent`).

```
PARKO_KORTRIJK_FETCH="http://193.190.76.149:81/ParkoParkings/counters.php"
GHENT_PUBLISH="http://localhost:3000/parking/Gent/"
PARKO_KORTRIJK_PUBLISH="http://localhost:3000/parking/Kortrijk/"
```
 
### Gathering
The file `cron.php` is set up to gather data from the available datasets. To activate the data gathering, add
the following line to your crontab (`crontab -e`):
```
* * * * * /bin/php [INSTALLATION_FOLDER]/cron.php 1>> /dev/null 2>&1
```
 
### Hosting
A test server can be hosted as follows:
```
php -S localhost:3000 -t src/
```

## Interface
- `/entry`: returns a JSON file with all the valid URLs of dataset hosted on this server.
- `/parking/<city>`: returns the latest measurements of this city (dataset) in a Turtle file.
- `/parking/<city>?time=YYYY-MM-DDTHH:mm:ss`: returns the most recent measurement file before the given timestamp.


## Adding new datasets
### 1) Implementing the Graph Processor
To add a new dataset, the interface `IGraphProcessor` in `Helpers` must be implemented.
Some examples are available in `src/Datasets` (note that some URLs and credentials use Dotenv to
hide confidential information). The interface defines the following methods:

- `getDynamicGraph()`: Return a graph containing the data that should be continuously measured (e.g. available parking
spaces in a parking dataset). This data will be saved to disk for each query. It therefore shouldn't
contain data that will always remain the same.
- `getStaticGraph()`: Return a graph containing static data. This is data that is not expected to change (e.g. a geographic
location of a parking site). This data is saved to disk only once.
- `getName()`: Return the name of the dataset. This name is only used for internal storage
and is not visible to the public.
- `getBaseUrl()`: Return the URL on which the dataset is to be published. This URL will be used in the stored files,
so it will be visible to the public.
- `getRealTimeMaxAge()`: Return the amount of seconds the dynamic data can be cached. This will be put in the cache
headers of the dataset.

### 2) Adding the graph processor to the cron file

### 3) Publishing the dataset
There are two ways in which a new dataset can be published: either it will be published on its own (no router needed),
or it will be added to an existing group of datasets (using the router).

#### Single dataset
To publish one dataset, the routing component in `index.php` is not necessary. An example `index.php`
for a single dataset can be found in `index-singular.php`. Here, you simply fill in the Graph Processor in the
`$graph_processor` variable.

#### Multiple datasets
To add a new dataset to an existing group of datasets, the router needs to be used:
- The router assumes all datasets are published under `/parking/*`. This means that `getBaseUrl()` in the Graph Processor
should return a URL under `/parking/*`.
- An instance of the new graph processor should be added to the `$graph_processors` variable in `index.php` (at the top).