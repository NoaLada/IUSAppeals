'use strict';

angular.module('Authentication')

.controller('LoginController',
    ['$scope', '$rootScope', '$location', 'AuthenticationService',
    function ($scope, $rootScope, $location, AuthenticationService) {
        // reset login status
        AuthenticationService.ClearCredentials();

        $scope.login = function () {
            $scope.dataLoading = true;
            AuthenticationService.Login($scope.username, $scope.password, function(response) {
                if(response.success) {
                    var key = response['security_key'];
                    console.log("Key: " + key);
                    console.log(response);
                    AuthenticationService.SetCredentials($scope.username, $scope.password, key);
                    $location.path('/menu');
                    console.log("response success!");
                } else {
                    console.log("Response not success");
                    $scope.error = response.message;
                    $scope.dataLoading = false;
                }
            });
        };
    }]);
