Named Params vs Querystring
===========================

With the release of **2.5.0** of the **Search** plugin the default settings for the prg component have been changed to use `querystring` by default instead of `named`.

To use querystrings **before 2.5.0** you have to use these configuration settings for the component.

```php
public $components = array(
	'Search.Prg' => array(
		'commonProcess' => array('paramType' => 'querystring'),
		'presetForm' => array('paramType' => 'querystring')
	)
);
```

If you just **upgraded to 2.5.0** or higher, and you're not already using query strings you'll have to set the configuration of the prg component in your app back to use named parameters. This is as well valid if you want to favor named parameters for some over query strings.

```php
public $components = array(
	'Search.Prg' => array(
		'commonProcess' => array('paramType' => 'named'),
		'presetForm' => array('paramType' => 'named')
	)
);
```

Why Querstrings Instead of Named parameters?
--------------------------------------------

[Query strings](http://en.wikipedia.org/wiki/Query_string) are the correct way of passing parameters in an URL while historically named parameters were always just a hack to get pretty URLs in CakePHP.

When you pass named parameters that contain special characters like `/` or `&` for example, they'll break the URL. You will have to manually encode and decode them all the time.

The encoding of named parameters can break URLs. They always violate the [HTTP specification](http://tools.ietf.org/html/rfc3986#section-2.2) and they are CakePHP specific, no other application uses volatile parameters this way and they are no real standard.

Named parameters are not any longer supported in CakePHP 3.0 as well.
