Quark
=
Quark is a PHP SaaS framework, designed for using in complex projects.


Requirements
=
 * PHP version **5.4** or higher
 * NginX + php-fpm **or** Apache + mod-rewrite + mod-php


**Additional**
<br>
Some parts *(especially data providers)* may require additional PHP extensions. The list of core extensions is represented in wiki.


Let's go!
=
Let's create simple HelloWorld.

First of all, we need to understand the best project file structure *(D - directory, F - file)*:

    [D] Services
    [D] Models
    [D] ViewModels
    [D] Views
    [D] storage
    [D] static
    [D] runtime
    [F] .gitignore
    [F] .htaccess
    [F] index.php

Depending on project type, there can be no folders such as `ViewModels`, `Views` and `static`, or `storage`. The `.htaccess` can be found in Quark repository. Of course, if You use `NginX`+`php-fpm` You need to put rewrite rule in your virtual host configuration.
In the `.gitignore` can be `runtime` and `index.php`

Now, let's take a look of `index.php`. Minimal required set is:

```php
<?php
include '/path/to/quark/Quark.php';

use Quark\Quark;
use Quark\QuarkConfig;

$config = new QuarkConfig();

Quark::Run($config);
```
    
In this file You can configure all `DataProviders`, `AuthProviders`, `Extensions` and application settings.

Let's create first service.
Name convention requires that all services need to match the rule:

    [any valid string with first letter in uppercase]Service
    
Example: `IndexService`, `CreateService`, `MySuperService` etc.

Filenames of services must be formed as service' class name + `.php`. E.g.: `IndexService.php`, `CreateService.php`, `MySuperService.php` etc.


> Services are atomic point of processing, therefore they contains only HTTP-methods hooks and auth settings.

Simple service which responds on GET requests at `/` web path can look like this:

```php
<?php
namespace Services;

use Quark\IQuarkGetService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Class IndexService
 *
 * @package Services
 */
class IndexService implements IQuarkGetService {
    /**
     * @param QuarkDTO $request
     * @param QuarkSession $session
     *
     * @return mixed
     */
    public function Get (QuarkDTO $request, QuarkSession $session) {
    	echo 'Hello World!';
    }
}
```

Put this code at file `IndexService.php` and put it into the root of `Services` directory. Your first service already done =)


As You can see, we've implemented `IQuarkGetService`, which indicates that this service have hook for GET request. In Quark core also defined `IQuarkPostService`. Other method services are deprecated for using in applications, that doesn't require support of `WebDAV`, by security reasons.

> For WebDAV applications there are many interfaces in `Extensions\Quark\WebDAV`


What's next?
=
As was sayed in the beginning, Quark was designed for using in complex web applications, that can be used such REST APIs. Of course, any API app need some communication data protocol (e.g. JSON). For this reasons, in main package is defined `IQuarkIOProcessor` and some common processors (`QuarkJSONIOProcessor`, `QuarkXMLIOProcessor`, etc.).

Let's create a service, named `HelloService`, which will respond with JSON string of `{"hello":"world"}`:

```php
<?php
namespace Services;

use Quark\IQuarkGetService;
use Quark\IQuarkServiceWithCustomProcessor;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Class HelloService
 *
 * @package Services
 */
class HelloService implements IQuarkGetService, IQuarkServiceWithCustomProcessor {
    /**
     * @return IQuarkIOProcessor
     */
    public function Processor () {
    	return new QuarkJSONIOProcessor();
    }
    
    /**
     * @param QuarkDTO $request
     * @param QuarkSession $session
     *
     * @return mixed
     */
    public function Get (QuarkDTO $request, QuarkSession $session) {
    	return array(
    	    'hello' => 'world'
    	);
    }
}
```
    
As You can see, we only defined the data processor for this service and returned data, which need to be processed. Simple =)

Quark allows to define different processors for request and response. All what is need to do - implementing of corresponding interfaces


And that is only beginning!
=
More info about Quark parts can be found in Wiki.

Open source blog platform, based on Quark https://github.com/Qybercom/Thinkscape
