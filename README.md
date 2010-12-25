# What is this? #
This is a wikibot that will copy cables from LeakFeed.com into a mediawiki database.

It creates a template infobox cable at the top of the article.

It will search for country names in the cable body and add them as categories.  It will also add the cable office, the tags in the cable, the year the cable was sent and a normalized classification to the categories.

It checks if the cable is already in the mediawiki database before creation. It creates the cable using the cable reference id as the article name.

# Requires #
* Sean Hubers awesome [curl library](https://github.com/shuber/curl)
* List of countries (included)
