'use strict';

angular.module('IUSAppeals', [
    'Authentication',
    'Home',
    'ngRoute',
    'ngCookies'
])

.controller('AppealController',
    ['$scope',
    function ($scope) {

      $scope.sendappeal = function () {
          $scope.dataLoading = true;
          SendAppealService.SendAppeal($scope.user, $scope.text, function(response) {
              if(response.success) {
                  $location.path('/');
                  console.log("response success");
              } else {
                  console.log("Response not success");
                  $scope.error = response.message;
                  $scope.dataLoading = false;
              }
          });
      };


    }]);
