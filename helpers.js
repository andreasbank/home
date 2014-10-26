/**
 * Home JS helper functions
 */

/**
 * setCookie()
 * Sets a cookie with a given value.
 */
function setCookie(cookieName, cookieValue, expireDays) {
  var expireDate = new Date();
  expireDate.setDate(expireDate.getDate()+expireDays);
  cookieValue = cookieValue+"; expires="+expireDate.toUTCString();
  document.cookie = cookieName+"="+cookieValue;
}

/**
 * getCookie()
 * Retrieves a cookie value.
 */
function getCookie(cookieName) {
  var i;
  var cName;
  var cValue;
  var cookies=document.cookie.split(";");
  for (i=0; i<cookies.length; i++) {
    cName = cookies[i].substr(0, cookies[i].indexOf("="));
    cValue = cookies[i].substr(cookies[i].indexOf("=")+1);
    cName = cName.replace(/^\s+|\s+$/g,"");
    if (cName == cookieName) {
      return unescape(cValue);
    }
  }
  return null;
}

