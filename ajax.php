<?php

require_once 'classes.php';

$lyrics = Song::getByLyrics($_GET['lyric']);
echo json_encode($lyrics);
