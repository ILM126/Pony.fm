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

angular.module('ponyfm').controller "account-settings", [
    '$scope', 'auth'
    ($scope, auth) ->
        $scope.settings = {}
        $scope.errors = {}
        $scope.isDirty = false

        $scope.touchModel = () ->
            $scope.isDirty = true

        $scope.refresh = () ->
            $.getJSON('/api/web/account/settings')
                .done (res) -> $scope.$apply ->
                    $scope.settings = res

        $scope.setAvatar = (image, type) ->
            delete $scope.settings.avatar_id
            delete $scope.settings.avatar

            if type == 'file'
                $scope.settings.avatar = image
            else if type == 'gallery'
                $scope.settings.avatar_id = image.id

            $scope.isDirty = true

        $scope.updateAccount = () ->
            return if !$scope.isDirty

            xhr = new XMLHttpRequest()
            xhr.onload = -> $scope.$apply ->
                $scope.isSaving = false
                response = $.parseJSON(xhr.responseText)
                if xhr.status != 200
                    $scope.errors = {}
                    _.each response.errors, (value, key) -> $scope.errors[key] = value.join ', '
                    return

                $scope.isDirty = false
                $scope.errors = {}
                $scope.refresh()

            formData = new FormData()

            _.each $scope.settings, (value, name) ->
                if name == 'avatar'
                    return if value == null
                    if typeof(value) == 'object'
                        formData.append name, value, value.name
                else
                    formData.append name, value

            xhr.open 'POST', '/api/web/account/settings/save', true
            xhr.setRequestHeader 'X-CSRF-Token', pfm.token
            $scope.isSaving = true
            xhr.send formData

        $scope.refresh()

        $scope.$on '$stateChangeStart', (e) ->
            return if $scope.selectedTrack == null || !$scope.isDirty
            e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
]
