'use strict';

angular.module('Appeal')

.controller('AppealController',
    ['$scope', '$rootScope', '$location', 'SendAppealService', 'AuthenticationService',
    function ($scope, $rootScope, $location, SendAppealService, AuthenticationService) {

      $scope.sendappeal = function () {
        console.log("Entered Controller!!");
          $scope.dataLoading = true;

          // TODO - Radio buttons do not work, i.e. are not stored into json

          var json = $scope.data;
          delete json.id;
          delete json.password;
          delete json.salt;
          delete json.isAdmin;

          console.log(json);
          SendAppealService.SendAppeal(AuthenticationService.GetLoggedUser(), json, function(response) {
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

      $scope.displayappeal = function () {
        console.log("Entered Controller!!");
          $scope.dataLoading = true;

          console.log(json);
          DisplayAppealService.DisplayAppeal(AuthenticationService.GetLoggedUser(), json, function(response) {
              if(response.success) {
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
