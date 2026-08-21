/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else {
		var a = factory();
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(self, function() {
return /******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/app/widgets/confetti.js":
/*!*************************************!*\
  !*** ./src/app/widgets/confetti.js ***!
  \*************************************/
/***/ (function() {

eval("{/**\n * Canvas Confetti demo - single trigger button\n */\n(function () {\n  'use strict';\n\n  var btn = document.getElementById('confetti_demo_btn');\n  if (!btn || typeof confetti !== 'function') return;\n  btn.addEventListener('click', function () {\n    confetti({\n      particleCount: 100,\n      spread: 70,\n      origin: {\n        y: 0.6\n      }\n    });\n  });\n})();\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/widgets/confetti.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/widgets/confetti.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});