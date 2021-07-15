Quick Start
===========

This quick start guide will help you get ready to use the **Search** plugin with your application.

Add the [Prg](../../Controller/Component/PrgComponent.php) component to the controller and call the component methods to process POST and GET. You can debug the paginator settings to see what the component does there, for example:

```php
class UsersController extends AppController {

	public $components = array(
		'Search.Prg'
	);

	public function index() {
		$this->Prg->commonProcess();
		$this->Paginator->settings['conditions'] = $this->User->parseCriteria($this->Prg->parsedParams());
		$this->set('users', $this->Paginator->paginate());
	}
}
```

For the previous example, in your User model, attach the [Searchable](../../Model/Behavior/SearchableBehavior.php) behavior and configure the ```$filterArgs``` property for the fields you want to make searchable.

```php
class User extends AppModel {

	public $actsAs = array(
		'Search.Searchable'
	);

	public $filterArgs = array(
		'username' => array(
			'type' => 'like',
			'field' => 'username'
		),
		'email' => array(
			'type' => 'like',
			'field' => 'email'
		),
		'active' => array(
			'type' => 'value'
		)
	);

}
```

There is no need to make any additional changes in your view, only make sure that your form includes the fields defined in your ```$filterArgs``` property, for example:

```php
<?php
	echo $this->Form->create();
	echo $this->Form->input('username');
	echo $this->Form->input('email');
	echo $this->Form->input('active', array(
		'type' => 'checkbox'
	));
	echo $this->Form->submit(__('Submit'));
	echo $this->Form->end();
?>
```

For more complex examples see the [Examples](../Documentation/Examples.md) section of the documentation.
