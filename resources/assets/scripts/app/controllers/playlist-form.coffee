# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

angular.module('ponyfm').controller "playlist-form", [
    '$scope', 'dialog', 'playlists', 'playlist'
    ($scope, dialog, playlists, playlist) ->
        $scope.isLoading = false
        $scope.form = playlist
        $scope.isNew = playlist.id == undefined

        $scope.errors = {}

        $scope.createPlaylist = () ->
            $scope.isLoading = true
            def =
                if $scope.isNew
                    playlists.createPlaylist($scope.form)
                else
                    playlists.editPlaylist($scope.form)

            def
                .done (res) ->
                    dialog.close(res)

                .fail (errors)->
                    $scope.errors = errors
                    $scope.isLoading = false

        $scope.close = () -> dialog.close(null)
]
