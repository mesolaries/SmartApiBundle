SmartProblem Service
============

This service provides a [RFC7807](https://tools.ietf.org/html/rfc7807) Problem details integration for Symfony.

Supported response formats
============
- application/problem+json

How it works
============

```php
<?php

use Mesolaries\SmartApiBundle\Exception\SmartProblemException;
use Mesolaries\SmartApiBundle\Problem\SmartProblem;
use Symfony\Component\Routing\Annotation\Route;

class SomeController
{
    /**
     * @Route("/", name="app.index", defaults={"_format" = "json"})
     */
    public function index()
    {
        // ...
        throw new SmartProblemException(
            new SmartProblem(400, 'validation_error', 'There was a validation error.')
        );
    }
}
```

When the controller format is a `json` or 
the request `Content-Type` is `*/json`,  
the `kernel.exception` listener will run.

It will transform the exception to following response:

Headers:

`Content-Type: application/problem+json`

Body:

```json
{
    "status": 400,
    "type": "validation_error",
    "title": "There was a validation error."
}
```

You can also use any Exceptions 
from `Symfony\Component\HttpKernel\Exception\HttpExceptionInterface`.
Then, the response will be as following:

```php
<?php

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
throw new BadRequestHttpException("Something went wrong.");
```

Response:

```json
{
    "status": 400,
    "type": "about:blank",
    "title": "Bad Request",
    "detail": "Something went wrong"
}
```

If you want additional data in response you can use `addExtraData` method:
```php
<?php

use Mesolaries\SmartApiBundle\Exception\SmartProblemException;
use Mesolaries\SmartApiBundle\Problem\SmartProblem;

$errors = [
    'name' => 'Name have to be at least 4 characters',
];

$smartProblem = new SmartProblem(400, 'validation_error', 'There was a validation error.');
$smartProblem->addExtraData('errors', $errors);

throw new SmartProblemException($smartProblem);
```

Response:

```json
{
    "status": 400,
    "type": "validation_error",
    "title": "There was a validation error.",
    "errors": {
        "name": "Name have to be at least 4 characters"
    }
}
```

That's it.