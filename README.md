# SmartFlanders Backend
This repo contains the PHP backend code for the Smart Flanders project.

## documentation
### index.php
Index.php contains a routing component, made to publish multiple independent datasets on the same server.
To publish one dataset, this routing component is not necessary. An example index.php
for a single dataset can be found in `index-singular.php`. Here, you simply fill in the Graph Processor in the
`$graph_processor` variable (see `adding new datasets`).

### Adding new datasets
To add a new dataset, the interface `IGraphProcessor` in `Helpers` must be implemented.
Some examples are available in `src/Datasets` (note that some URLs and credentials use Dotenv to
hide confidential information). The interface defines the following methods:

- `getDynamicGraph()`: Return a graph containing the data that should be continuously measured (e.g. available parking
spaces in a parking dataset). This data will be saved to disk for each query. It therefore shouldn't
contain data that will always remain the same.
- `getStaticGraph()`: Return a graph containing static data. This is data that is not expected to change (e.g. a geographic
location of a parking site). This data is saved to disk only once.
- `getName()`: Return the name of the dataset.
- `getBaseUrl()`: Return the URL on which the dataset is to be published.
- `getRealTimeMaxAge()`: Return the amount of seconds the dynamic data can be cached. This will be put in the cache
headers of the dataset.

##
