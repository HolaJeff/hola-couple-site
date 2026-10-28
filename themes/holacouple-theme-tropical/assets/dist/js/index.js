"use strict";

/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/
(function () {
  // webpackBootstrap
  /******/
  var __webpack_modules__ = {
    /***/"./assets/src/js/index.js": (
    /*!********************************!*\
      !*** ./assets/src/js/index.js ***!
      \********************************/
    /***/
    function _assets_src_js_indexJs() {
      eval("jQuery(function ($) {\r\n    function checkVisibility() {\r\n        $('.revealer').each(function () {\r\n            \r\n            var elementTop = $(this).offset().top;\r\n            var elementHeight = $(this).outerHeight();\r\n            var viewportTop = $(window).scrollTop();\r\n            var viewportHeight = $(window).height();\r\n            var triggerPoint = viewportTop + viewportHeight / 2;\r\n\r\n            if (triggerPoint >= elementTop && triggerPoint <= elementTop + elementHeight) {\r\n                $(this).addClass('animate');\r\n            }\r\n\r\n            $(this).on('animationend webkitAnimationEnd', () => { \r\n                $(this).addClass('animation-end');\r\n            });\r\n        }); \r\n    }\r\n\r\n    $(window).on('scroll resize', checkVisibility);\r\n    checkVisibility(); // Initial check on page load\r\n});\r\n\n\n//# sourceURL=webpack:///./assets/src/js/index.js?");

      /***/
    })

    /******/
  };
  /************************************************************************/
  /******/
  /******/ // startup
  /******/ // Load entry module and return exports
  /******/ // This entry module can't be inlined because the eval devtool is used.
  /******/
  var __webpack_exports__ = {};
  /******/
  __webpack_modules__["./assets/src/js/index.js"]();
  /******/
  /******/
})();