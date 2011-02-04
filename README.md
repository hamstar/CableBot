# What is this? #
This is a wikibot that will copy cables from LeakFeed.com into a mediawiki database.

It creates a template infobox cable at the top of the article.

It will search for country names in the cable body and add them as categories.  It will also add the cable office, the tags in the cable, the year the cable was sent and a normalized classification to the categories.

It checks if the cable is already in the mediawiki database before creation. It creates the cable using the cable reference id as the article name.

# Configuration

First you will need to configure it.  Currently only 3 options need to be filled out:

    define('WIKI_USERNAME','BotName'); // your bots username
    define('WIKI_PASSWORD','secret'); // your bots password
    define('WIKI_API','http://example.com/api.php'); // url to wiki api

And thats done.

# How do I run it

Please check runCableBot.php it pretty much shows you how to do this.  Will update readme soon.

Its as easy as that.  Check log-YYYY-MM-DD-HHMM-SS.txt for log output.

# Requires #
* Sean Hubers awesome [curl library](https://github.com/shuber/curl)
* My own [Wikimate](https://github.com/hamstar/wikimate) mediawiki bot framework
* List of countries (included)
* Serialized list of cable tag/name pairs (included)

# Coming soon...

* The taxonomizer - to automatically build category trees for cable tags/categories
* The wikipediator - linking of words (e.g. politician names) from wiki to Wikipedia