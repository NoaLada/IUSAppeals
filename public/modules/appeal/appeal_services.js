'use strict';

angular.module('Appeal')

.factory('SendAppealService',
    ['$http', '$cookieStore', '$rootScope', '$timeout', 'AuthenticationService',
    function ($http, $cookieStore, $rootScope, $timeout, AuthenticationService) {
        var service = {};

        service.SendAppeal = function (user, text, callback) {
            console.log("Entered service!!!");
            console.log(user);
            var key = AuthenticationService.GetLoggedKey();
            console.log(key + " :D");
            $http.post('/api/appeals', { user: user, text: text, key: key })
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
