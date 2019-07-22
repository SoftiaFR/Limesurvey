# SmartDOMDocument

This class overcomes a few common annoyances with the DOMDocument class, such as saving partial HTML without 
automatically adding extra tags and properly recognizing various encodings, specifically UTF-8.

## To Install

In composer.json, add the repository:

```
"repositories": [
        {
            "type": "vcs",
            "url": "https://bitbucket.org/archon810/smartdomdocument.git"
        }
    ],
    "require": {
        "archon810/smartdomdocument": "dev-master"
    }
```

Then run

```
composer update
```

This will install smartdomdocument, as well as PHPUnit, if you set composer to download dev dependencies. 

(Use --no-dev to disable dev deps)

## To Use

```
$dom = new \archon810\SmartDOMDocument();
$dom->loadHTML($content);
/**
 * Make some changes to $dom...
 */
$content = $dom->saveHTMLExact();
```

## To Test

Run

```
./vendor/bin/phpunit
```

This will pickup the correct config from phpunit.xml and run the tests in ./test