<?php

require_once 'classes.php';


if (isset($_GET['song_id']) && $song = Song::get((int) $_GET['song_id']))
{
    $template = new Template('templates/main_song.html');
    $chords = new Template('templates/chords.html');
    $chords->assign('chords', $song->getChords());
    $chords->assign('title', $song->name);
    $template->assign('middle', $chords->render());
}
else
{
    $template = new Template('templates/main.html');
    $template->assign('l', isset($_GET['l']) ? $_GET['l'] : '');
    $view = new SongList(Song::getList());
    $template->assign('middle', $view->render());
}
echo $template->render();
