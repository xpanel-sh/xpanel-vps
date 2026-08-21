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

/***/ "./src/app/pages/plugins/datatables-net/members.js":
/*!*********************************************************!*\
  !*** ./src/app/pages/plugins/datatables-net/members.js ***!
  \*********************************************************/
/***/ (function() {

eval("{document.addEventListener('DOMContentLoaded', function () {\n  var table = document.getElementById('members-datatable');\n  if (table && typeof window.$ !== 'undefined' && !$.fn.DataTable.isDataTable(table)) {\n    $(table).DataTable({\n      pageLength: 10,\n      lengthMenu: [[10, 25, 50], [10, 25, 50]],\n      language: {\n        search: '',\n        searchPlaceholder: 'Search'\n      },\n      layout: {\n        topStart: [],\n        topEnd: 'search',\n        bottomStart: ['pageLength', 'info'],\n        bottomEnd: 'paging'\n      }\n    });\n  }\n});\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/pages/plugins/datatables-net/members.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/pages/plugins/datatables-net/members.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});