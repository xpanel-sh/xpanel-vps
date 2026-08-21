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

/***/ "./src/app/pages/account/members/team-members-datatable.js":
/*!*****************************************************************!*\
  !*** ./src/app/pages/account/members/team-members-datatable.js ***!
  \*****************************************************************/
/***/ (function() {

eval("{document.addEventListener('DOMContentLoaded', function () {\n  var table = document.getElementById('team-members-datatable');\n  if (table && typeof window.$ !== 'undefined' && !$.fn.DataTable.isDataTable(table)) {\n    var dt = $(table).DataTable({\n      pageLength: 10,\n      lengthMenu: [[10, 25, 50], [10, 25, 50]],\n      language: {\n        search: '',\n        searchPlaceholder: 'Search'\n      },\n      layout: {\n        topStart: [],\n        topEnd: 'search',\n        bottomStart: ['pageLength', 'info'],\n        bottomEnd: 'paging'\n      },\n      columnDefs: [{\n        orderable: false,\n        targets: [0, 6]\n      }, {\n        width: '220px',\n        targets: 2\n      }]\n    });\n\n    // Select all checkbox in header (current page rows only)\n    var selectAll = document.getElementById('team-members-datatable-select-all');\n    if (selectAll) {\n      var getCurrentPageRows = function getCurrentPageRows() {\n        return dt.rows({\n          page: 'current'\n        }).nodes();\n      };\n      var updateSelectAllState = function updateSelectAllState() {\n        var rows = getCurrentPageRows();\n        var checkboxes = $(rows).find('td:first-child input[type=\"checkbox\"]');\n        var checkedCount = checkboxes.filter(':checked').length;\n        var total = checkboxes.length;\n        selectAll.checked = total > 0 && checkedCount === total;\n        selectAll.indeterminate = checkedCount > 0 && checkedCount < total;\n      };\n      selectAll.addEventListener('change', function () {\n        var checked = this.checked;\n        $(getCurrentPageRows()).each(function () {\n          var cb = this.querySelector('td:first-child input[type=\"checkbox\"]');\n          if (cb) cb.checked = checked;\n        });\n      });\n      $(table).on('draw.dt', updateSelectAllState);\n      $(table).on('change', 'tbody td:first-child input[type=\"checkbox\"]', updateSelectAllState);\n    }\n  }\n});\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/pages/account/members/team-members-datatable.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/pages/account/members/team-members-datatable.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});