/**
 * Home JS classes
 */

function User(id, username, fullName) {
  this.users = new Array();
  this.id = id;
  this.username = username;
  this.fullName = fullName;
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

function Booking(portal, user, date) {
  this.portal = portal;
  this.user = user;
  this.date = date;
}

function Portal(id, name, ip_1, ip_2, booking) {
  this.name = name;
  this.ip_1 = ip_1;
  this.ip_2 = ip_2;
  this.booking = booking;
  this.isOwnBooking = loggedInUser.id == booking.user.id ? true : false;
  this.display = function (destinationTagId) {
    var destinationTag = document.getElementById(destinationTagId);
    destinationTag.innetHTML = "\t\t<td class=\"main_table_td\">\n\t\t\t<table border=\"0\" class=\"env_box booked" + (this.booking ? (this.isOwnBooking ? '_own' : '') : '_none') + "\">\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td colspan=\"2\" class=\"env_name\">\n\t\t\t\t\t\t" + this.name + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + this.ip_1 + "\n\t\t\t\t\t</td>\n" +
    "\t\t\t\t\t<td rowspan=\"3\" class=\"centered\">\n\t\t\t\t\t\t<input type=\"button\" value=\"Book!\" />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + (this.ip2 ? this.ip2 : '&nbsp;') + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t" + (this.booking ? this.booking.user.fullName : '&nbsp;') + "\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n" +
    "\t\t\t</table>\n\t\t</td>\n";
  }
}

function getPortalsWithOwnBookings() {
  
}
