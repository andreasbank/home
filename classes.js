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

function Booking(user_id, portal_id, book_date) {
  this.user_id = user_id;
  this.portal_id = portal_id;
  this.book_date = book_date;
  this.getUser = function () {
    return getUser(this.user_id);
  }
  this.getPortal = function () {
    return getPortal(this.portal_id);
  }

}

function Portal(id, name, hosts) {
  this.id = id;
  this.name = name;
  this.hosts = hosts;
  this.getBooking = function () {
    return getPortalBooking(this.id);
  };
  this.isOwnBooking = function () {
    return loggedInUser.id == this.getBooking().getUser().id;
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
  return booking;
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
