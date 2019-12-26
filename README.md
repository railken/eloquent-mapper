<h1 align="left">Eloquent Mapper</h1>

<h2 align="left">Stop wasting your time with boring joins and filters</h2>

[![Build Status](https://travis-ci.org/railken/eloquent-mapper.svg?branch=master)](https://travis-ci.org/railken/eloquent-mapper)

A sets of class that will enhance eloquent for querying data.

- Generate a list of all existing relationships
- Join automatically your relations
- Filter query with complex logic expression [lara-eye](https://github.com/railken/lara-eye)
- Attach dynamic relationships without touching the code using [eloquent-relativity](https://github.com/imanghafoori1/eloquent-relativity)

So, what actually does this library?

Transform a string like this `"employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"` used on a model called for e.g. `Office` into a sql query like this

```sql
select offices.* 
    from `offices` 
    left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
    where (`employees`.`name` like ? or `employees`.`name` like ?)
```

# Requirements

PHP 7.2 and later.

## Installation

You can install it via [Composer](https://getcomposer.org/) by typing the following command:

```bash
composer require railken/eloquent-mapper
```

## Usage

In order to use this library you need a map, a map for all the models that you wish to use, and a map of all attributes for each model.

Create a new class whenever you want like the following example

`app/Map.php`
```php
namespace App;

use Railken\EloquentMapper\Contracts\MapContract;

class Map extends MapContract
{

    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array
    {
        /** return [
            \App\Models\User::class
        ]; **/
    }

    /**
     * Given an instance of the model, retrieve all the attributes
     *
     * @return array
     */
    public function attributes(Model $model): array
    {
        /** return array_merge($model->getFillable(), [
            'id',
            'created_at',
            'updated_at'
        ]); **/
    }
}
```

The first method is used to simply have a list of all models. You can even add models that are in your vendor folder, regardless of the logic you use, you only have to return an array.

The second is used primarly for filtering, as the library need to know which fields are valid and which are not.

These methods are invoked only when you call the command 'artisan mapper:generate' (see below) and the result will be cached in a file placed in `bootstrap/cache/map.php`. 

This means you can perform whatever logic you want to retrieve all models (e.g. scanning files etc...) so don't worry about caching.

Now register the class in the application

`app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Map;
use Railken\EloquentMapper\Contracts\MapContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->bind(Map::class, MapContract::class);
    }
}
```

## Artisan

There is only one command, and it's `artisan mapper:generate`. This command will reload and recache all relations so keep in mind that you have to execute it whanever you change your models.

If you use models that are in your vendor folder, you could add this in your composer.json to reload everytime the libreries are updated.
```json
{
   "scripts": {
        "post-autoload-dump": [
            "@php artisan mapper:generate"
        ]
    }
}
```

## Example

`app/Models/Office.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    public $attributes = ['name', 'description'];
}
```

`app/Models/Employee.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Office;

class Employee extends Model
{
    public $attributes = ['name', 'description', 'office_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
```

`app/ModelMap.php`
```php
namespace App;

use Railken\EloquentMapper\Contracts\MapContract;

class ModelMap extends MapContract
{

    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function get(): array
    {
        return [
            \App\Models\Employee::class,
            \App\Models\Office::class
        ]
    }
}

```

The script will create a file containing all models and relations (including reverse). So even if we didn't create a relation between office and employees
`$office->employees()->get()` will be callable.

You can add the command in the scripts section of composer.json if you want

## Example

Retrieve all offices that have employees with name `Mario Rossi` or `Giacomo`

```php
use App\Models\Office;
use Railken\EloquentMapper\Scopes\FilterScope;

$office = new Office;

$query = $office->newQuery();
$myFilter = "employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"

$filter = new FilterScope(
    function (Model $model) {
        return $model->getFillable();
    },
    $myFilter
);

$filter->apply($query, $foo);

$query->toSql(); 

```

```sql
select offices.* 
	from `offices` 
	left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
	where (`employees`.`name` like ? or `employees`.`name` like ?)
```
