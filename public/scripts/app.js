'use strict';

// declare modules
angular.module('Authentication', []);
angular.module('Home', []);
angular.module('Appeal', []);
angular.module('Menu', []);

angular.module('IUSAppeals', [
    'Authentication',
    'Home',
    'Appeal',
    'Menu',
    'ngRoute',
    'ngCookies'
])

.config(['$routeProvider', function ($routeProvider) {

    $routeProvider
        .when('/login', {
            controller: 'LoginController',
            templateUrl: 'modules/authentication/views/login.html',
            hideMenus: true
        })

        .when('/appeal1', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal1.html'
        })

        .when('/appeal2', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal2.html'
        })

        .when('/appeal3', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal3.html'
        })

        .when('/appeal4', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal4.html'
        })

        .when('/appeal5', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal5.html'
        })

        .when('/appeal6', {
            controller: 'AppealController',
            templateUrl: 'modules/appeal/views/appeal6.html'
        })

        .when('/menu', {
            controller: 'MenuController',
            templateUrl: 'modules/menu/views/menu.html'
        })


        .when('/home', {
            controller: 'HomeController',
            templateUrl: 'modules/home/views/home.html'
        })

        .otherwise({ redirectTo: '/login' });
}])

.run(['$rootScope', '$location', '$cookieStore', '$http',
    function ($rootScope, $location, $cookieStore, $http) {
        // keep user logged in after page refresh
        $rootScope.globals = $cookieStore.get('globals') || {};
        if ($rootScope.globals.currentUser) {
            $http.defaults.headers.common['Authorization'] = 'Basic ' + $rootScope.globals.currentUser.authdata; // jshint ignore:line
        }

        $rootScope.$on('$locationChangeStart', function (event, next, current) {
          console.log("Ignore me");
            // redirect to login page if not logged in
            if ($location.path() !== '/login' && !$rootScope.globals.currentUser) {
                $location.path('/login');
            }
        });
    }]);
