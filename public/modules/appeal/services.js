'use strict';

angular.module('IUSAppeals', [
    'Authentication',
    'Home',
    'ngRoute',
    'ngCookies'
])

.factory('SendAppealService',
    ['$http', '$cookieStore', '$rootScope', '$timeout',
    function ($http, $cookieStore, $rootScope, $timeout) {
        var service = {};

        service.SendAppeal = function (user, text, callback) {
            $http.post('/api/appeals', { user: user, text: text })
                .success(function (response) {
                    console.log("Server returned something");
                    console.log(response);
                    callback(response);
                }).error(function (response) {
                    console.log("Failed to communicate");
                });

        };


    return service;
}])
