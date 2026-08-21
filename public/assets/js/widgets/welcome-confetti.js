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

/***/ "./src/app/widgets/welcome-confetti.js":
/*!*********************************************!*\
  !*** ./src/app/widgets/welcome-confetti.js ***!
  \*********************************************/
/***/ (function() {

eval("{/**\n * Welcome page demo – show confetti when the welcome modal is shown and on button click.\n */\n(function () {\n  'use strict';\n\n  if (typeof confetti !== 'function') return;\n  function fireWelcomeConfetti() {\n    confetti({\n      particleCount: 100,\n      spread: 70,\n      origin: {\n        y: 0.6\n      }\n    });\n  }\n  var modalEl = document.getElementById('modal_welcome_confetti');\n  if (modalEl) {\n    var modal = KTModal.getInstance(modalEl);\n    if (modal) {\n      modalEl.addEventListener('shown.kt.modal', fireWelcomeConfetti);\n    }\n    // Show modal on load (same as welcome-message); fire confetti when shown\n    window.addEventListener('load', function () {\n      modal = KTModal.getInstance(modalEl) || new KTModal(modalEl);\n      modal.show();\n      // Fire confetti after modal is visible (fallback if shown.kt.modal is not dispatched)\n      setTimeout(fireWelcomeConfetti, 300);\n    });\n  }\n  var btn = document.getElementById('welcome_confetti_btn');\n  if (btn) {\n    btn.addEventListener('click', fireWelcomeConfetti);\n  }\n})();\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/widgets/welcome-confetti.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/widgets/welcome-confetti.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});