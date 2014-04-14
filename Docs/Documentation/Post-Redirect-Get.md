POST-Redirect-GET
=================

Post/Redirect/Get (PRG) is a common design pattern for web developers to help avoid certain duplicate form submissions and allow user agents to behave more intuitively with bookmarks and the refresh button.

When a web form is submitted to a server through an HTTP POST request, a web user that attempts to refresh the server response in certain user agents can cause the contents of the original HTTP POST request to be resubmitted, possibly causing undesired results.

To avoid this problem, it is possible to use the PRG pattern instead of returning the web page directly. The POST operation returns a redirection command, instructing the browser to load a different page (or same page) using an HTTP GET request.

See the [Wikipedia article](http://en.wikipedia.org/wiki/Post/Redirect/Get) for more information.
