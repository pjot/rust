<?php

class Scraper
{
    protected $list_url = 'http://hyperrust.org/cgi-bin/msl.pl?0300';
    protected $song_url = 'http://hyperrust.org/cgi-bin/mt.pl?%s';

    public function getSongLinks()
    {
        $html = file_get_contents($this->list_url);
        preg_match_all('#<a href="/cgi-bin/mt.pl\?(?P<id>\d+)">(?P<name>[^<]+)#', $html, $matches);
        $songs = array();
        for ($i = 0; $i < count($matches['id']); $i++)
        {
            $songs[] = array(
                'id' => $matches['id'][$i],
                'name' => $matches['name'][$i]
            );
        }
        return $songs;
    }

    public function getChords($id)
    {
        $html = file_get_contents(sprintf($this->song_url, $id));
        preg_match_all('#<pre>(?P<chords>.*)</(pre|td)>#Us', $html, $matches);
        return array_pop($matches['chords']);
    }
}

abstract class Entity
{
    public function save()
    {
        return empty($this->id) ? $this->create() : $this->update();
    }

    public static function get($id)
    {
        $sql = sprintf('select %s from %s where id = :id', 
            implode(', ', static::$fields),
            static::$table
        );
        $db = DbConnection::getInstance();
        $sth = $db->prepare($sql);
        $sth->execute(array(':id' => $id));
        $model = new static;
        if ($sth->rowCount() === 1)
        {
            $result = $sth->fetch(PDO::FETCH_OBJ);
            foreach (static::$fields as $field)
            {
                $model->$field = $result->$field;
            }   
            $model->id = $id;
        }
        return $model;
    }

    public static function getList()
    {
        $sql = sprintf('select id, name from %s', static::$table);
        $db = DbConnection::getInstance();
        $sth = $db->prepare($sql);
        $sth->execute();
        $models = array();
        foreach ($sth->fetchAll(PDO::FETCH_OBJ) as $row)
        {
            $model = new static;
            $model->id = $row->id;
            $model->name = $row->name;
            $models[] = $model;
        }
        return $models;
    }

    public function create()
    {
        $fields = array();
        $values = array();
        $binds = array();
        foreach (static::$fields as $field)
        {
            $bind = sprintf(':%s', $field);
            $fields[] = $field;
            $values[$bind] = $this->$field;
            $binds[] = $bind;
        };
        $sql = sprintf('insert into %s (%s) values (%s)',
            static::$table,
            implode(', ', $fields),
            implode(', ', $binds)
        );
        $db = DbConnection::getInstance();
        $sth = $db->prepare($sql);
        $sth->execute($values);
        $this->id = $db->lastInsertId();
    }

    public function update()
    {
        $fields = array();
        $values = array();
        foreach (static::$fields as $field)
        {
            $fields[] = sprintf('%s = :%s', $field, $field);
            $values[sprintf(':%s', $field)] = $this->$field;
        }
        $values[':id'] = $this->id;
        $sql = sprintf('update %s set %s where id = :id',
            static::$table,
            implode(', ', $fields)
        );
        $db = DbConnection::getInstance();
        $sth = $db->prepare($sql);
        $sth->execute($values);
    }
}

class Song extends Entity
{
    protected static $table = 'songs';
    protected static $fields = array(
        'name',
        'chords',
    );

    public function getChords()
    {
        $chords = new Chords($this->chords);
        return $chords->parse();
    }
}

final class DbConnection
{
    /**
     * Database configuration variables.
     */
    private static $dbConfig = array(
        'database' => 'hyperrust',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
    );

    /**
     * PDO object
     */
    private static $pdo = null;

    /**
     * Private constructor ensures singleton behaviour.
     */
    private function __construct() {}

    /**
     * Public access to the PDO object.
     */
    public static function getInstance()
    {
        if (empty(self::$pdo))
        {
            self::$pdo = new \PDO( 
                sprintf('mysql:dbname=%s;host=%s', self::$dbConfig['database'], self::$dbConfig['hostname']),
                self::$dbConfig['username'],
                self::$dbConfig['password']
            );
        }
        return self::$pdo;
    }
}

class SongList
{
    public $songs = array();

    public function __construct($songs)
    {
        $this->songs = $songs;
    }

    public function render()
    {
        $main_template = new Template('templates/songs.html');
        $template = new Template('templates/song.html');
        $songs = '';
        foreach ($this->songs as $song)
        {
            $template->assign('name', $song->name);
            $template->assign('id', $song->id);
            $songs .= $template->render();
        }
        $main_template->assign('songs', $songs);
        return $main_template->render();
    }
}

class Template
{
    protected $variables = array();
    protected $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public function render()
    {
        $html = file_get_contents($this->template);
        foreach ($this->variables as $name => $value)
        {
            $variable = sprintf('{%s}', $name);
            $html = str_replace($variable, $value, $html);
        }
        return $html;
    }
}

class Chords
{
    public static $replacement = '<span class="chord">$1</span>$4';
    public static $find_chords = '/([ABCDEFG][#b]?\*?m?(aj)?(sus[24]?)?7?)(.)/';
    public static $is_chord = '/([ABCDEFG][#b]?\*?m?(aj)?(sus[24]?)?7?)[^a-zA-Z]/';

    protected $chords;

    public function __construct($chords)
    {
        $this->chords = $chords;
    }

    public function parse()
    {
        $return = $this->chords;
        $return = preg_replace_callback(self::$find_chords, function ($matches) {
            $match = $matches[0];
            if (preg_match(Chords::$is_chord, $matches[0]))
            {
                return preg_replace(Chords::$find_chords, Chords::$replacement, $matches[0]);
            }
            return $matches[0];
        }, $return);
        return $return;
    }
}