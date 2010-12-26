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

If you look at runCableBot.php it pretty much shows you how to do this.

First you need to make sure to include all the requires:

    include 'config.php'; // make sure you put your details in here
    include 'CableBot.php';
    include 'curl.php';
    include 'Logger.php';

Initialize the bot:

    $bot = new CableBot;

Set these configuration modifiers if need be:

    $bot->setTest( true ); // will do everything but write to the wiki
    $bot->setAddExternalLinks( true ); // will add external links section

(the external links section contains links to search pages for google and twitter with identifiers and subjects)

Then you can instruct the bot to add one cable:
    $bot->addSingleCable( '10KUWAIT161' );

Some cables from an array:

    $cables = array(
       '10MUSCAT71',
       '10STATE10900',
       '10BERLIN153'
    );

    $bot->addCablesFromArray( $cables );

Or just to grab the latest cables from leakfeed.

    $bot->addLatestCables();

Its as easy as that.  Check log.txt for log output.

# Requires #
* Sean Hubers awesome [curl library](https://github.com/shuber/curl)
* List of countries (included)
* Serialized list of cable tag/name pairs (included)

# Coming soon...

* The taxonomizer - to automatically build category trees for cable tags/categories
* The wikipediator - linking of words (e.g. politician names) from wiki to Wikipedia