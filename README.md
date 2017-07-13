# SmartFlanders Backend
This repo contains the PHP backend code for the Smart Flanders project.

## documentation
### Interface
- `/entry`: returns a JSON file with all the valid URLs of dataset hosted on this server.
- `/parking/<city>`: returns the latest measurements of this city (dataset) in a Turtle file.
- `/parking/<city>?time=YYYY-MM-DDTHH:mm:ss`: returns the most recent measurement file before the given timestamp.


### Adding new datasets
#### 1) Implementing the Graph Processor
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

#### 2) Publishing the dataset
There are two ways in which a new dataset can be published: either it will be published on its own (no router needed),
or it will be added to an existing group of datasets (using the router).

##### Single dataset
To publish one dataset, the routing component in `index.php` is not necessary. An example `index.php`
for a single dataset can be found in `index-singular.php`. Here, you simply fill in the Graph Processor in the
`$graph_processor` variable.

##### Multiple datasets
To add a new dataset to an existing group of datasets, the router needs to be used:
- The router assumes all datasets are published under `/parking/*`. This means that `getBaseUrl()` in the Graph Processor
should return a URL under `/parking/*`.
- An instance of the new graph processor should be added to the `$graph_processors` variable in `index.php` (at the top).

##
