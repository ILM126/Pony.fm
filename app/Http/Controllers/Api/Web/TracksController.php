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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Commands\DeleteTrackCommand;
use Poniverse\Ponyfm\Commands\EditTrackCommand;
use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\ResourceLogItem;
use Poniverse\Ponyfm\Track;
use Cover;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class TracksController extends ApiControllerBase
{
    public function postUpload()
    {
        session_write_close();

        return $this->execute(new UploadTrackCommand());
    }

    public function postDelete($id)
    {
        return $this->execute(new DeleteTrackCommand($id));
    }

    public function postEdit($id)
    {
        return $this->execute(new EditTrackCommand($id, Input::all()));
    }

    public function getShow($id)
    {
        $track = Track::userDetails()->withComments()->find($id);
        if (!$track || !$track->canView(Auth::user())) {
            return $this->notFound('Track not found!');
        }

        if (Input::get('log')) {
            ResourceLogItem::logItem('track', $id, ResourceLogItem::VIEW);
            $track->view_count++;
        }

        $returned_track = Track::mapPublicTrackShow($track);
        if ($returned_track['is_downloadable'] != 1) {
            unset($returned_track['formats']);
        }

        return Response::json(['track' => $returned_track], 200);
    }

    public function getIndex()
    {
        $page = 1;
        $perPage = 45;

        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = Track::summary()
            ->userDetails()
            ->listed()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user');

        $this->applyFilters($query);

        $totalCount = $query->count();
        $query->take($perPage)->skip($perPage * ($page - 1));

        $tracks = [];
        $ids = [];

        foreach ($query->get(['tracks.*']) as $track) {
            $tracks[] = Track::mapPublicTrackSummary($track);
            $ids[] = $track->id;
        }

        return Response::json([
            "tracks" => $tracks,
            "current_page" => $page,
            "total_pages" => ceil($totalCount / $perPage)
        ], 200);
    }

    public function getOwned()
    {
        $query = Track::summary()->where('user_id', \Auth::user()->id)->orderBy('created_at', 'desc');

        $tracks = [];
        foreach ($query->get() as $track) {
            $tracks[] = Track::mapPrivateTrackSummary($track);
        }

        return Response::json($tracks, 200);
    }

    public function getEdit($id)
    {
        $track = Track::with('showSongs')->find($id);
        if (!$track) {
            return $this->notFound('Track ' . $id . ' not found!');
        }

        if ($track->user_id != Auth::user()->id) {
            return $this->notAuthorized();
        }

        return Response::json(Track::mapPrivateTrackShow($track), 200);
    }

    private function applyFilters($query)
    {
        if (Input::has('order')) {
            $order = \Input::get('order');
            $parts = explode(',', $order);
            $query->orderBy($parts[0], $parts[1]);
        }

        if (Input::has('is_vocal')) {
            $isVocal = \Input::get('is_vocal');
            if ($isVocal == 'true') {
                $query->whereIsVocal(true);
            } else {
                $query->whereIsVocal(false);
            }
        }

        if (Input::has('in_album')) {
            if (Input::get('in_album') == 'true') {
                $query->whereNotNull('album_id');
            } else {
                $query->whereNull('album_id');
            }
        }

        if (Input::has('genres')) {
            $query->whereIn('genre_id', Input::get('genres'));
        }

        if (Input::has('types')) {
            $query->whereIn('track_type_id', Input::get('types'));
        }

        if (Input::has('songs')) {
            $query->join('show_song_track', function ($join) {
                $join->on('tracks.id', '=', 'show_song_track.track_id');
            });
            $query->whereIn('show_song_track.show_song_id', Input::get('songs'));
        }

        return $query;
    }
}
