Named Params vs Querystring
===========================

With the release of version **2.5.0** of the **Search** plugin the settings for the `Prg` component have been changed to use `querystring` by default instead of `named`.

To use query string parameters before **2.5.0** you would have had to use these configuration settings for the component:

```php
public $components = array(
	'Search.Prg' => array(
		'commonProcess' => array('paramType' => 'querystring'),
		'presetForm' => array('paramType' => 'querystring')
	)
);
```

If you just upgraded to **2.5.0** or higher, and you're not already using query string parameters, you'll have to set the configuration of the `Prg` component in your app back to use `named` parameters. This is also valid if you want to favor `named` parameters over `querystring`.

```php
public $components = array(
	'Search.Prg' => array(
		'commonProcess' => array('paramType' => 'named'),
		'presetForm' => array('paramType' => 'named')
	)
);
```

Why Query String instead of Named parameters?
--------------------------------------------

Using the [query string](http://en.wikipedia.org/wiki/Query_string) part of the URI is the correct way of passing parameters. Historically, `named` parameters were an alternative to passing arguments as part of the URL in *CakePHP*.

When you pass `named` parameters that contain special characters like `/` or `&`, they potentially break the URL. You would have to manually encode and decode these all the time. They also violate the [HTTP](http://tools.ietf.org/html/rfc3986#section-2.2) specification, as they are specific to *CakePHP*, and are no longer supported in version **3.0** of the framework.
