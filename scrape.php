<?php

require_once 'classes.php';

$scraper = new Scraper();

echo "Fetching songs...";
$songs = $scraper->getSongLinks();
$count = count($songs);
echo "done. Found $count.\n";

echo "Processing songs. Progress: ";
foreach ($songs as $i => $link)
{
    $current = "$i/$count";
    echo $current;

    $song = new Song();
    $song->name = $link['name'];
    $song->chords = $scraper->getChords($link['id']);
    $song->save();

    echo str_repeat("\x08", strlen($current));
}

echo "done.\n";
