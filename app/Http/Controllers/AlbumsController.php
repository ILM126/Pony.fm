<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm\Http\Controllers;

use Poniverse\Ponyfm\AlbumDownloader;
use App;
use Poniverse\Ponyfm\Album;
use Poniverse\Ponyfm\ResourceLogItem;
use Poniverse\Ponyfm\Track;
use Illuminate\Support\Facades\Redirect;
use View;

class AlbumsController extends Controller
{
    public function getIndex()
    {
        return View::make('albums.index');
    }

    public function getShow($id, $slug)
    {
        $album = Album::find($id);
        if (!$album) {
            App::abort(404);
        }

        if ($album->slug != $slug) {
            return Redirect::action('AlbumsController@getAlbum', [$id, $album->slug]);
        }

        return View::make('albums.show');
    }

    public function getShortlink($id)
    {
        $album = Album::find($id);
        if (!$album) {
            App::abort(404);
        }

        return Redirect::action('AlbumsController@getTrack', [$id, $album->slug]);
    }

    public function getDownload($id, $extension)
    {
        $album = Album::with('tracks', 'user')->find($id);
        if (!$album) {
            App::abort(404);
        }

        $format = null;
        $formatName = null;

        foreach (Track::$Formats as $name => $item) {
            if ($item['extension'] == $extension) {
                $format = $item;
                $formatName = $name;
                break;
            }
        }

        if ($format == null) {
            App::abort(404);
        }

        ResourceLogItem::logItem('album', $id, ResourceLogItem::DOWNLOAD, $format['index']);
        $downloader = new AlbumDownloader($album, $formatName);
        $downloader->download();
    }
}
