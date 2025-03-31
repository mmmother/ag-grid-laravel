# AG Grid Server-Side Adapter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mother/ag-grid-laravel.svg?style=flat-square)](https://packagist.org/packages/mother/ag-grid-laravel)

Server-side adapter for [AG Grid](https://www.ag-grid.com/) with support for filtering, sorting, selection, and exporting.

---

## Installation

```bash
composer require mother/ag-grid-laravel
php artisan vendor:publish --tag="ag-grid-laravel-config"
```

```php
// config/ag-grid.php
return [
    'export_timezone_provider' => \Clickbar\AgGrid\AgGridDefaultExportTimezoneProvider::class,
];
```

---

## Basic Usage

### Querying

```php
class FlamingoGridController extends Controller
{
    public function __invoke(AgGridGetRowsRequest $request): AgGridQueryBuilder
    {
        return AgGridQueryBuilder::forRequest($request, Flamingo::query())
            ->resource(FlamingoResource::class);
    }
}
```

### Set Filter Values

```php
return AgGridQueryBuilder::forSetValuesRequest($request, Flamingo::query())
    ->toSetValues(['name', 'keeper.name']);
```

Wildcard: `['*']`

### Selection

```php
$flamingos = AgGridQueryBuilder::forSelection($request->validated('selection'))->get();
```

### Export

```php
class Flamingo extends Model implements AgGridExportable
{
    public static function getAgGridColumnDefinitions(): array
    {
        return [
            new AgGridColumnDefinition('id', 'ID'),
            new AgGridColumnDefinition('name', 'Name'),
            new AgGridColumnDefinition('created_at', 'Created At', new AgGridDateFormatter()),
        ];
    }
}
```

---

## Custom Filters

```php
class Flamingo extends Model implements AgGridCustomFilterable
{
    public function applyAgGridCustomFilters(Builder $query, array $filters): void
    {
        $query->when($filters['showTrashed'] ?? false, fn ($q) => $q->withTrashed());
    }
}
```

---

## TypeScript Reference

```ts
interface AgGridSelection {
  rowModel: 'serverSide' | 'clientSide';
  selectAll: boolean;
  toggledNodes: (string | number)[];
  filterModel?: any;
  customFilters?: any;
}

interface AgGridGetRowsRequest extends IServerSideGetRowsRequest {
  exportFormat?: 'excel' | 'csv' | 'tsv';
  exportColumns?: string[];
  customFilters?: any;
}

interface AgGridGetRowsResponse<T> {
  total: number;
  data: T[];
}
```

---

## Frontend Helpers

### DataSource

```ts
function makeDataSource<T>(url: string): IServerSideDatasource {
  return {
    async getRows(params) {
      const res = await axios.post<AgGridGetRowsResponse<T>>(url, {
        ...params.request,
      });
      params.success({ rowData: res.data.data, rowCount: res.data.total });
    },
  };
}
```

### Export

```ts
async function exportGrid(api: GridApi, format: 'excel' | 'csv' | 'tsv') {
  const params = api.getModel().getRootStore().getSsrmParams();
  const columns = api.getColumnApi().getAllDisplayedColumns().map(col => col.getColId());
  const res = await axios.post('/api/export', {
    ...params,
    exportFormat: format,
    exportColumns: columns,
  }, { responseType: 'blob' });

  const url = URL.createObjectURL(res.data);
  Object.assign(document.createElement('a'), { href: url, download: true }).click();
}
```

---

## License

MIT
