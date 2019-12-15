
<!--#echo json="package.json" key="name" underline="=" -->
simple-localizer-php-pmb
========================
<!--/#echo -->

<!--#echo json="package.json" key="description" -->
Yet another tool to help with i18n in PHP. Very old legacy code, you probably
shouldn&#39;t use it.
<!--/#echo -->



Usage
-----

Just run

```bash
$ sloc
```

in your project's `.dev` directory.
It usually does the right thing according do project config.
However, there's no documentation on required project config,
or at least I can't find any.

IIRC it writes the translated files to the directory that contains the
`.dev` directory, so conceptually `.dev/` == `src/` and `.` == `dist/`.
It also supports hooks to automatically upload the new files.



<!--#toc stop="scan" -->



Known issues
------------

* Very old legacy code, you probably shouldn&#39;t use it.
  The only reason I publish it is to have easier backup.
* Needs more/better tests and docs.




&nbsp;


License
-------
<!--#echo json="package.json" key=".license" -->
GPL-2.0-or-later
<!--/#echo -->
