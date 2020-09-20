/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 3);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/js/portal/page/login.js":
/*!*******************************************!*\
  !*** ./resources/js/portal/page/login.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var portalLogin = new Vue({
  el: "#portal-login-section",
  data: {
    form: {
      username: "",
      password: "",
      remember: false
    },
    entity: entity,
    logoUrl: '/portal/images/logo/logo.png',
    loaderUrl: '/app/assets/img/loader.gif',
    loading: false
  },
  computed: {
    submitUrl: function submitUrl() {
      return '/portal/' + this.entitySlug + '/login';
    },
    redirectUrl: function redirectUrl() {
      return '/portal/' + this.entitySlug;
    },
    entitySlug: function entitySlug() {
      if (this.entity.search(" ")) {
        return this.entity.split(" ").join("-").toLowerCase();
      } else {
        return this.entity.toLowerCase();
      }
    }
  },
  methods: {
    login: function login() {
      var _this = this;

      this.loading = true;

      if (!this.form.username || !this.form.password) {
        this.loading = false;
        this.$toast.error("Incomplete Credentials", "", {
          timeout: 5000
        });
      } else {
        //validate user with db
        axios({
          method: 'post',
          url: this.submitUrl,
          data: this.form
        }).then(function (response) {
          if (response.data.valid == true) {
            _this.$toast.success(response.data.message, "", {
              timeout: 2000
            });

            setTimeout(function () {
              window.location.href = _this.redirectUrl;
            }, 2000);
          } else {
            _this.loading = false;

            _this.$toast.error(response.data.message, "", {
              timeout: 5000
            });
          }
        })["catch"](function (error) {
          if (error.response.status == 419) {
            _this.loading = false;

            _this.$toast.error("Login expired. Kindly refresh the page and try again.", "", {
              timeout: 5000
            });
          }
        });
      }
    }
  }
});

/***/ }),

/***/ 3:
/*!*************************************************!*\
  !*** multi ./resources/js/portal/page/login.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Applications/MAMP/htdocs/solushop/resources/js/portal/page/login.js */"./resources/js/portal/page/login.js");


/***/ })

/******/ });