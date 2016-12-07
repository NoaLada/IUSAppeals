'use strict';

angular.module('Appeal')

.controller('AppealController',
    ['$scope', '$rootScope', '$location', 'SendAppealService', 'AuthenticationService',
    function ($scope, $rootScope, $location, SendAppealService, AuthenticationService) {

      $scope.sendappeal = function () {
        console.log("Entered Controller!");
          $scope.dataLoading = true;
          SendAppealService.SendAppeal(AuthenticationService.GetLoggedUser(), $scope.text, function(response) {
              if(response.success) {
                  $location.path('/menu');
                  console.log("response success");
              } else {
                  console.log("Response not success");
                  $scope.error = response.message;
                  $scope.dataLoading = false;
              }
          });
      };

      $scope.get_data = function() {
        AuthenticationService.GetUser(AuthenticationService.GetLoggedUser(), function(response) {
              if(response.success) {
                  $scope.data = response['data'];
                  console.log("response success");
              } else {
                  console.log("Response not success");
                  $scope.error = response.message;
                  $scope.dataLoading = false;
              }
          });
      };

      $scope.data = null;

      $scope.date = new Date();

    }]);


    //azrichak15M
