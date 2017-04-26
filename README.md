# Comments exporter

**Comments exporter** is a console script that can export code comments from multiple source files to a csv file. This file can be used to perform bulk proofreading and editing over code comments. After that, comments can be imported back with the same script.

Script supports all types of C-like comments, such as:

```php
// This one.
/* This one. */
/**
 * And this one. 
 */
```

# Installation

Comments exporter is installed as a global [composer](https://getcomposer.org/doc/00-intro.md) package. You need to have composer installed in your system and have the composer global tools added to [your PATH](https://unix.stackexchange.com/questions/280846/find-composer-global-install-path-as-root.

```
composer global require shvetsgroup/comments-exporter
```

Make sure that your

## Usage

1. Export comments:
    ```
    ./comments-exporter export /path/to/project /path/to/csv-file.csv
    ```

2. Do something with the `csv-file.csv` contents. Do not add new rows, just edit the `comment` column.

3. Import comments back:

    ```
    ./comments-exporter import /path/to/csv-file.csv /path/to/project 
    ```

Type `./comments-exporter --help` to read about full list of supported parameters.

