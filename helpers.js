/**
 * Home JS helper function
 */

var user;
var portals = new Array();


function User(id, username, full_name) {
  this.users = new Array();
  this.id = id;
  this.username = username;
  this.full_name = full_name;
  this.add_user = function (user) {
    this.users.push(user);
  }
  this.find_user_by_username = function (username) {
    for(var i = 0; i < this.users.length; i++) {
      if(username == users[i].username) {
        return users[i];
      }
    }
  }
  this.find_user_by_id = function (id) {
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

function Portal(id, name, ip_1, ip_2, logged_user, booking) {
  this.name = name;
  this.ip_1 = ip_1;
  this.ip_2 = ip_2;
  this.logged_user = logged_user;
  this.booking = booking;
  this.booked_date = booked_date;
  this.is_own_booking = logged_user == booked_user ? true : false;
  this.
  this.display = function (destination_tagf) {
    destination_tag.innetHTML = "\t\t<td class=\"main_table_td\">\n\t\t\t<table border=\"0\" class=\"env_box %s\">\n", ($is_booked ? ($is_own ? 'blue' : 'orange') : 'green'));
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan=\"2\" class=\"env_name\">\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", $portals[$i]->get_name());
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n", $portals[$i]->get_host(0));
    printf("\t\t\t\t\t<td rowspan=\"3\" class=\"centered\">\n\t\t\t\t\t\t<input type=\"button\" value=\"Book!\" />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", $portals[$i]->get_booked_user());
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", ($portals[$i]->get_host(1)?$portals[$i]->get_host(1):'&nbsp;'));
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", ($is_booked ? $book_full_user_name : '&nbsp;'));
    printf("\t\t\t</table>\n\t\t</td>\n");
  }
}

function get_portals_with_own_bookings() {
  
}
