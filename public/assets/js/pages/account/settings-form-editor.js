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

/***/ "./src/app/pages/account/settings-form-editor.js":
/*!*******************************************************!*\
  !*** ./src/app/pages/account/settings-form-editor.js ***!
  \*******************************************************/
/***/ (function() {

eval("{/**\n * TinyMCE form integration for account settings (Bio / About field).\n * Initializes the editor on #settings-bio-editor when present.\n */\ndocument.addEventListener(\"DOMContentLoaded\", function () {\n  if (typeof tinymce === \"undefined\") {\n    return;\n  }\n  var editorEl = document.getElementById(\"settings-bio-editor\");\n  if (!editorEl) {\n    return;\n  }\n  tinymce.init({\n    target: editorEl,\n    license_key: \"gpl\",\n    height: 200,\n    menubar: false,\n    plugins: [\"autolink\", \"lists\", \"link\"],\n    toolbar: \"undo redo | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist | link\",\n    toolbar_mode: \"sliding\",\n    content_style: \"@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');\" + \" body { font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 0.875rem; line-height: 1.5; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }\",\n    setup: function setup(editor) {\n      editor.on(\"change\", function () {\n        editor.save();\n      });\n    }\n  });\n});\n\n//# sourceURL=webpack://metronic-tailwind-html/./src/app/pages/account/settings-form-editor.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/app/pages/account/settings-form-editor.js"]();
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});