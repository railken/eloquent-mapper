<h1 align="left">Eloquent Mapper</h1>

[![Build Status](https://travis-ci.org/railken/eloquent-mapper.svg?branch=master)](https://travis-ci.org/railken/eloquent-mapper)

This library will stop you from wasting your time with boring relations, joins and filter, how?

Given for e.g. two models `Office` and `Employee`, you can transform a string like this `"employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"` into a sql query like this

```sql
select offices.* 
    from `offices` 
    left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
    where (`employees`.`name` like ? or `employees`.`name` like ?)
```

Functions: 

- Join automatically your relations
- Filter query with complex logic expression [lara-eye](https://github.com/railken/lara-eye)
- Attach dynamic relationships without touching the code using [eloquent-relativity](https://github.com/imanghafoori1/eloquent-relativity)

# Requirements

PHP 7.2 and later.

## Installation

You can install it via [Composer](https://getcomposer.org/) by typing the following command:

```bash
composer require railken/eloquent-mapper
```

## Usage

In order to use this library you need a map, one for all the models that you wish to use, and one for all attributes for each model.

Create a new class whatever you want like the following example

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

These methods are invoked only when you call the command `artisan mapper:generate` (see below) and the result will be cached in a file placed in `bootstrap/cache/map.php`. 

This means you can perform whatever logic you want to retrieve all models (e.g. scanning files), so don't worry about caching.

Now it's time to register this class in any provider.

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

There is only one command, and it's `artisan mapper:generate`. This command will remap and recache so keep in mind that you have to execute it whanever you change your models.

If you use models that are in your vendor folder, you could add this in your `composer.json` to reload everytime the libreries are updated.
```json
{
   "scripts": {
        "post-autoload-dump": [
            "@php artisan mapper:generate"
        ]
    }
}
```

## Filtering

Sow how the filtering actually works?


```php
use Railken\EloquentMapper\Scopes\FilterScope;
use App\Models\Foo;

$foo = new Foo;
$query = $foo->newQuery();
$queryString = "created_at >= 2019"

$filter = new FilterScope($foo);
$filter->apply($query, $queryString);

```

And that's it! `$query` is now filtered, if for `Foo` has any relationships you can use the dot notation and the filter will automatically perform the join. For e.g. if `Foo` has a relationship called `tags` and you want to retrieve all `Foo` with the tag name `myCustomTag` simply use `tag.name = 'myCustomTag'`.

Here's the [full syntax](https://github.com/railken/search-query#nodes)

## Joiner

This is an internall class used by the `FilterScope` to join the necessary relations before performing the filtering, but you can use it indipendently

## Example - Setup

Let's continue with a real example, first the setup. We will use two models: `Office` and `Employee`

`app/Models/Office.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{   
    /**
     * An array used by the mapper to retrieve all attributes
     *
     * @var array
     */
    public $attributes = [
        'id', 
        'name',
        'description',
        'created_at',
        'updated_at'
    ];
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
    /**
     * An array used by the mapper to retrieve all attributes
     *
     * @var array
     */
    public $attributes = [
        'id',
        'name',
        'description',
        'office_id',
        'created_at',
        'updated_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
```

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
        return [
            \App\Models\Employee::class,
            \App\Models\Office::class
        ];
    }

    /**
     * Given an instance of the model, retrieve all the attributes
     *
     * @return array
     */
    public function attributes(Model $model): array
    {
        return $model->attributes;
    }
}

```

# Example - Usage

Retrieve all offices that have employees with name `Mario Rossi` or `Giacomo`

```php
use App\Models\Office;
use Railken\EloquentMapper\Scopes\FilterScope;

$office = new Office;

$query = $office->newQuery();
$queryString = "employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"

$filter = new FilterScope($office);
$filter->apply($query, $queryString);

echo $query->toSql(); // printing for demonstration

```

Result 
```sql
select offices.* 
	from `offices` 
	left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
	where (`employees`.`name` like ? or `employees`.`name` like ?)
```
