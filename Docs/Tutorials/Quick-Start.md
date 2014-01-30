Quick Start
===========

This quick start guide will help you get ready to use the **Search** plugin with your application.

Add the [Prg component](../../Controller/Component/PrgComponent.php) to the controller and call the component methods to process post and get. You can debug the paginator settings to see what the component does there.

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

The user model, attach the [Searchable behavior](../../Model/Behavior/SearchableBehavior.php) and configure the filterArgs for the fields you want to make searchable.

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

app/View/Users/index.ctp

```php
<?php
	echo $this->Form->create();
	echo $this->Form->input('username');
	echo $this->Form->input('email');
	echo $this->Form->input('active', array(
		'type' => 'checkox'
	));
	echo $this->Form->submit(__('Submit'));
	echo $this->Form->end();
?>

For more complex examples check the [Example](../Documentation/Examples) section of the documentation.