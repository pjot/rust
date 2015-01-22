<?php

require_once 'classes.php';

$template = new Template('templates/main.html');

if (isset($_GET['song_id']) && $song = Song::get((int) $_GET['song_id']))
{
    $template->assign('search', '');
    $chords = new Template('templates/chords.html');
    $chords->assign('chords', $song->getChords());
    $chords->assign('title', $song->name);
    $template->assign('middle', $chords->render());
}
else
{
    $template->assign('l', isset($_GET['l']) ? $_GET['l'] : null);
    $view = new SongList(Song::getList());
    $template->assign('middle', $view->render());
}
echo $template->render();
