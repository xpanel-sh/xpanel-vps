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

/***/ "./src/app/widgets/calendar.js":
/*!*************************************!*\
  !*** ./src/app/widgets/calendar.js ***!
  \*************************************/
/***/ (function() {

eval("{/**\n * Dashboard calendar widget - FullCalendar month view\n */\n(function () {\n  'use strict';\n\n  var calendarEl = document.getElementById('calendar-dashboard');\n  if (!calendarEl || typeof FullCalendar === 'undefined') return;\n  var calendar = new FullCalendar.Calendar(calendarEl, {\n    initialView: 'dayGridMonth',\n    headerToolbar: {\n      left: 'prev,next today',\n      center: 'title',\n      right: 'dayGridMonth,timeGridWeek,timeGridDay'\n    }\n  });\n  calendar.render();\n})();\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/widgets/calendar.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/widgets/calendar.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});