/**
 * Home AJAX functionality
 */

/**
 * Get a browser XMLHTTP request object
 */
function getXmlHttpRequestObject () {
  if (window.XMLHttpRequest) {
    return new XMLHttpRequest();
  }
  else if (window.ActiveXObject) {
    return new ActiveXObject("Microsoft.XMLHTTP");
  }
  else {
    return false;
  }
}

/**
 * Class/Prototype, Contains the state of an AJAX request
 */
function RequestIndicator() {
  this.done = false;
  this.success = true;
  this.status = "";
  this.systemErrorMessage = "";
  this.systemErrorStatus = false;
}

/**
 * Remove the elements from the XMLDOM object that only contain whitespace
 *
 * @param node The node representing the root in the removel process
 */
function removeWhiteSpaceNodes(node) {
  if(node == null)
    return;
  var child = node.firstChild, nextChild;
  while (child) {
    nextChild = child.nextSibling;
    if(child.nodeType == 3 && /^\s*$/.test(child.nodeValue)) {
      node.removeChild(child);
    }
    else if(child.hasChildNodes()) {
      removeWhiteSpaceNodes(child);
    }
    child = nextChild;
  }
}

/**
 * Changes the state of the associated RequestIndicator,
 * indicating that the request has returned and is finished.
 */
function changeRequestState(theConn, requestIndicator, xmlObjectsPointer) {
  if (theConn.readyState == 4 && theConn.status == 200) {
    if(theConn.responseText.indexOf('Error: ') == 0) {
      requestIndicator.systemErrorMessage = theConn.responseText;
      requestIndicator.systemErrorStatus = true;
    }
    else {
      xmlObjectsPointer.innerObject.push(theConn.responseXML);
    }
    requestIndicator.success = true;
    requestIndicator.status = theConn.status;
    requestIndicator.done = true;
  }
  else if(theConn.readyState == 4 && theConn.status != 200) {
    xmlObjectsPointer = null;
    requestIndicator.success = false;
    requestIndicator.status = theConn.status;
    requestIndicator.done = true;
  }
}

/**
 * The wrapper function that combines the old AjaxIt function
 * and the RequestIndicator
 *
 * @param action The action to be performed. This is a predefined string
 * @param args The arguments to use, ampere separated html arguments (a=1&b=2&c=3)
 */
function ajaxXml(action, args, requestIndicator, xmlObjectsPointer, callbackFunction) {
  var theConn = getXmlHttpRequestObject();
  if (theConn === false)
    alert('Could not create XMLHttpRequest');
  else
    if (theConn.readyState == 4 || theConn.readyState == 0) {
      theConn.open("GET", 'api.php?action='+action+'&'+args, true);
      theConn.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
      theConn.onreadystatechange = function () { changeRequestState(theConn, requestIndicator, xmlObjectsPointer); }
      theConn.send(null);
    }
  handleXmlResults(requestIndicator, xmlObjectsPointer, callbackFunction);
}

/**
 * Handles the XML returned by AJAX and calls the
 * appropriate conversion function
 * This function checks if the result has been received and 
 * executes only if it has or alerts on error.
 */
function handleXmlResults(requestIndicator, xmlObjectsPointer, callbackFunction) {
  var type, xmlObjects;
  if(requestIndicator.done && requestIndicator.success) {
    if(xmlObjectsPointer.innerObject[0] == null || !xmlObjectsPointer.innerObject[0].hasChildNodes()) {
      if(callbackFunction == null)
        return;
      callbackFunction(null);
      return;
    }
    removeWhiteSpaceNodes(xmlObjectsPointer.innerObject[0]);
    type = xmlObjectsPointer.innerObject[0].documentElement.nodeName;
    switch(type) {
      case 'users':
        xmlObjects = createUsersFromDomXml(xmlObjectsPointer.innerObject[0]);
        break;
      case 'defaultObjects':
        xmlObjects = createDefaultObject(xmlObjectsPointer.innerObject[0]);
        break;
      default:
        xmlObjects = xmlObjectsPointer.innerObject[0];
    }
    if(callbackFunction != null) {
      callbackFunction(xmlObjects);
    }
  }
  else if(requestIndicator.done && !requestIndicator.success) {
    alert("Error: "+requestIndicator.status);
  }
  else {
    setTimeout(function() { handleXmlResults(requestIndicator, xmlObjectsPointer, callbackFunction); }, 500);
  }
  xmlObjectsPointer.innerObject.pop();
}

/**
 * Creates a object containing an array of received values.
 * Used when no specific type of information has been returned
 */
function createDefaultObject(xmlObjects) {
  var valuesArray = new Array();
  var valuesXml = xmlObjects.documentElement;
  for(var i=0; i<valuesXml.childNodes.length; i++) {
    var name;
    if(!valuesXml.childNodes[i].getAttribute('name')) {
      name = -1;
    }
    else {
      name = valuesXml.childNodes[i].getAttribute('name');
    }
    var value;
    if(!valuesXml.childNodes[i].firstChild) {
      value = -1;
    }
    else {
      value = valuesXml.childNodes[i].firstChild.nodeValue;
    }
    valuesArray[name] = value;
  }
  return valuesArray;
}

/**
 * Creates an Array of User objects out of the passed XMLDOM object
 *
 * @param xmlObject The XMLDOM object containing the user information
 *
 * @return Returns a Array of User
 */
function createUsersFromDomXml(xmlObjects) {
  var usersArray = new Array();
  var usersXml = xmlObjects.documentElement;
  for(var i=0; i<usersXml.childNodes.length; i++) {
    var userXml = usersXml.childNodes[i];
    var id = userXml.getElementsByTagName('id')[0].innerHTML;
    var username = userXml.getElementsByTagName('username')[0].innerHTML;
    var fullName = userXml.getElementsByTagName('fullName')[0].innerHTML;
    var user = new User(
      id,
      username,
      fullName
    );
    usersArray.push(user);
  }
  return usersArray;
}

