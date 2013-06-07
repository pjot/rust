Rust = {
    songs : []
};

Song = function (element) {
    this.element = element;
    this.name = element.data('name').toLowerCase();
    this.id = element.data('id');
};

Song.prototype.matches = function (needle) {
    return this.name.indexOf(needle) !== -1;
};

Song.prototype.hide = function () {
    this.element.hide();
};

Song.prototype.show = function () {
    this.element.show();
}

Song.prototype.setStyle = function (style) {
    this.element.removeClass('even, odd');
    this.element.addClass(style);
};

Rust.init = function () {
    Rust.parseSongs();
    Rust.bindEvents();
    Rust.filterSongs();
};

Rust.getSong = function (id) {
    for (s in Rust.songs)
    {
        if (Rust.songs[s].id == id)
        {
            return Rust.songs[s];
        }
    }
    return false;
};

Rust.parseSongs = function () {
    $('#songs .song').each(function () {
        Rust.songs.push(new Song($(this)));
    });
};

Rust.bindEvents = function () {
    $('#search_field').keyup(Rust.filterSongs);
    $('#transpose button').click(function () {
        Rust.transposeSong($(this).attr('rel'));
    });
};

Rust.filterSongs = function () {
    var search_field = $('#search_field'),
        row = true,
        search = '';
    if (search_field.size() === 0)
    {
        return;
    }
    search = search_field.val().toLowerCase();
    for (s in Rust.songs)
    {
        song = Rust.songs[s];
        if (song.matches(search)) 
        {
            song.show();
            song.setStyle(row ? 'even' : 'odd');
            row = ! row;
        }
        else
        {
            song.hide();
        }
    }
};

Rust.transposeSong = function (direction) {
    $('#chords .chord').each(function () {
        var chord_element = $(this);
        chord_element.html(Chord.transpose(chord_element.html(), direction));
    });
};

Chord = {
    map : {
        '+1' : {
            'Eb' : 'E',
            'E'  : 'F',
            'F#' : 'G',
            'F'  : 'F#',
            'Gb' : 'G',
            'G#' : 'A',
            'G'  : 'G#',
            'Ab' : 'A',
            'A#' : 'C',
            'A'  : 'Bb',
            'Bb' : 'B',
            'B'  : 'C',
            'C#' : 'D',
            'C'  : 'C#',
            'D#' : 'E',
            'D'  : 'D#'
        },
        '-1' : {
            'Eb' : 'D',
            'E'  : 'D#',
            'F#' : 'F',
            'F'  : 'E',
            'Gb' : 'F',
            'G#' : 'G',
            'G'  : 'F#',
            'Ab' : 'G',
            'A#' : 'A',
            'A'  : 'G#',
            'Bb' : 'A',
            'B'  : 'Bb',
            'C#' : 'C',
            'C'  : 'B',
            'D#' : 'D',
            'D'  : 'C#'
        }
    }
};

Chord.transpose = function (chord, direction) {
    var map = Chord.map[direction];
    for (i in map)
    {
        if (chord.match(new RegExp(i)))
        {
            return chord.replace(new RegExp(i), map[i]);
        }
    }
    return chord;
};

$(document).ready(function () {
    Rust.init();
});
