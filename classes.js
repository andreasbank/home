/**
 * Home JS classes
 */

function User(id, username, fullName) {
  this.users = new Array();
  this.id = id;
  this.username = username;
  this.fullName = fullName;
  //make class-scope
  this.addUser = function (user) {
    this.users.push(user);
  }
  this.findUserByUsername = function (username) {
    for(var i = 0; i < this.users.length; i++) {
      if(username == users[i].username) {
        return users[i];
      }
    }
  }
  this.findUserById = function (id) {
    for(var i = 0; i < this.users.length; i++) {
      if(id == users[i].id) {
        return users[i];
      }
    }
  }
}

function Booking(portal_id, user_id, book_date) {
  this.portal_id = portal_id;
  this.user_id = user_id;
  this.book_date = book_date;
  this.getUser = function () {
    return getUser(this.user_id);
  }
  this.getPortal = function () {
    return getPortal(this.portal_id);
  }

}

function Portal(id, name, hosts) {
  this.name = name;
  this.hosts;
  this.addHost = function (host) {
    this.hosts.push(host);
  }
  this.getBooking = function () {
    return getPortalBooking(this.id);
  }
  this.isOwnBooking = loggedInUser.id == booking.user.id ? true : false;
  this.display = function (destinationTagId) {
    var destinationTag = document.getElementById(destinationTagId);
    destinationTag.innetHTML = "\t\t<td class=\"main_table_td\">\n\t\t\t<table border=\"0\" class=\"env_box booked" + (this.booking ? (this.isOwnBooking ? '_own' : '') : '_none') + "\">\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td colspan=\"2\" class=\"env_name\">\n\t\t\t\t\t\t" + this.name + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + this.hosts[0] + "\n\t\t\t\t\t</td>\n" +
    "\t\t\t\t\t<td rowspan=\"3\" class=\"centered\">\n\t\t\t\t\t\t<input type=\"button\" value=\"Book!\" />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + (this.hosts[1] ? this.hosts[1] : '&nbsp;') + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + (this.getBooking() ? this.getBooking().getUser().fullName : '&nbsp;') + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t</table>\n\t\t</td>\n";
  }
}

function getPortalBooking(portal_id) {
  var booking = null;
  for(var i = 0; i < bookings.length; i++) {
    if(portal_id == bookings[i].portal_id) {
      booking = bookings[i];
      break;
    }
  }
  return booking;
}

function getBooking(user_id, portal_id) {
  var booking = null;
  for(var i = 0; i < bookings.length; i++) {
    if(user_id == bookings[i].user_id && portal_id == bookings[i].portal_id) {
      booking = bookings[i];
      break;
    }
  }
  return bookings;
}

function getUser(user_id) {
  var user = null;
  for(var i = 0; i < users.length; i++) {
    if(user_id == users[i].id) {
      user = users[i];
      break;
    }
  }
  return user;
}

function getPortal(portal_id) {
  var portal = null;
  for(var i = 0; i < portals.length; i++) {
    if(portal_id == portals[i].id) {
      portal = portals[i];
      break;
    }
  }
  return portal;
}
