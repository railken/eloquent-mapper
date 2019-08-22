# eloquent-mapper

[![Build Status](https://travis-ci.org/railken/eloquent-mapper.svg?branch=master)](https://travis-ci.org/railken/eloquent-mapper)

A sets of class that will enhance eloquent for querying data.

- Generate a list of all existing relationships
- Generate all join based on relationships defined [laravel-eloquent-join](https://github.com/fico7489/laravel-eloquent-join)
- Generate inverse relationships
- Filter query with complex expression [lara-eye](https://github.com/railken/lara-eye)
- Attach dynamic relationships without touching the code using [eloquent-relativity](https://github.com/imanghafoori1/eloquent-relativity)

# Requirements

PHP 7.1 and later.

## Installation

You can install it via [Composer](https://getcomposer.org/) by typing the following command:

```bash
composer require railken/eloquent-mapper
```

`app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->get('eloquent.mapper')->retriever(function () {
            return [
                // ... List of classes of models
            ]
        });
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

`app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->get('eloquent.mapper')->retriever(function () {
            return [
                App\Models\Employee::class,
                App\Models\Office::class
            ]
        });
    }
}
```

Whenever you update the list of models, execute the command
`php artisan mapper:generate`

The script will create a file containing all models and relations (including reverse). So even if we didn't create a relation between office and employees
`$office->employees()->get()` will be callable.

You can add the command in the scripts section of composer.json if you want
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