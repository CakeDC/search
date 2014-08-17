Configuration
=============

Behavior and Model Configuration
--------------------------------

All search fields must be configured in the ```Table::$filterArgs``` property as an array.

Each filter record should contain an array with several keys:

* **name:** The parameter stored in ```Model::$data```. The "name" used to search in a field (can be ommited if the key is the name).
* **type:** One of supported search types described below.
* **field:** Real field name used for search should be used.
* **finder:** Model finder name or behavior run in finder types.
* **allowEmpty:** Optional parameter used to allow generating conditions even if the filter field value is empty. It is often used when you specifically allow a lookup for an empty string or if conditions need to be generated based on several other fields.

Supported Types of Search
-------------------------

* **like:** Type of search used when you need to search using the "LIKE" SQL keyword.
* **value:** Useful when you need to perform exact comparisons. For example, when using a select box as your filter.
* **finder:** Most universal type of search. This makes use of a custom finder method in your table (or a behavior) and passes in the field data and configuration as options.

Component Configuration
-----------------------

The Prg component can be configured to start in the startup or initialize callback of the component.

* **callback:** Must be ```startup``` or ```initialize```, by default it is ```initialize```. Choose ```startup``` if you need to initialize model related settings in another component in the initialize callback.

Controller Configuration
------------------------

All search fields parameters need to be configured in the ```Controller::$presetVars``` array - if you didn't yet in the model.

Each preset variable is an array that that contains some of the following keys:

* **field:** Field that defined in the view search form.
* **type:** One of the search types described above
* **value:** Should be used for values that don't require any processing,
* **checkbox:** Used for checkbox fields in the view (Prg component packs and unpacks checkbox values when it is passed through the get named action).
* **lookup:** This type should be used when you have for example an auto-complete lookup field implemented in your view. This lookup field is a text field, and also you'll have a hidden field for the id value. In this case the component will fill both, text and id values.
* **table:** Parameter that specifies what table is used in ```Request::$data``` for this field.
* **formField:** Field in the form that contains text and will be populated using Model.modelField based on field value.
* **tableField:** Field in the table that contains text and will be used to fill the formField in the view.
* **encode:** Boolean, by default false. If you want to use search strings in URL's with special characters like % or / you need to use encoding.
* **empty:** Boolean, by default false. If you want to omit this field in the PRG url if no value has been given (shorter urls).

**Note:** Those can also be configured in the model itself (to keep it DRY). You can then set ```public $presetVar = true;``` then in the controller to use the model ones (see the example above). You can still use define the keys here where you want to overwrite certain settings. When using named params instead of query strings it is recommended to always use ```encode => true``` in combination with search strings (custom text input) to avoid url-breaking.
