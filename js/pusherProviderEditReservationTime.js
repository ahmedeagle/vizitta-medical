var notificationsWrapper = $('.dropdown-notifications');
var notificationsToggle = notificationsWrapper.find('a[data-toggle]');
var notificationsCountElem = notificationsToggle.find('span[data-count]');
var notificationsCount = parseInt(notificationsCountElem.data('count'));
var notifications = notificationsWrapper.find('ul.scrollable-container2');

// Subscribe to the channel we specified in our Laravel Event
var channel = pusher.subscribe('provider-edit-reservation');
// Bind a function to a Event (the full Laravel class)
channel.bind('App\\Events\\ProviderEditReservationTime', function (data) {
    var existingNotifications = notifications.html();
    var avatar = Math.floor(Math.random() * (71 - 20 + 1)) + 20;

    let newNotificationHtml = `<li data_notify_id="` + data.notification_id + `"  style="background-color: #ececec61;">
<a href="` + data.path + `" class="clearfix">
    <img src="` + data.photo + `" class="msg-photo"
    alt="Alex's Avatar">
        <span class="msg-body">
        <span class="msg-title">
        <span
class="blue">` + data.title + `</span>
    </span>
    <span class="msg-time">
        <i class="ace-icon fa fa-clock-o"></i>
        <span>` + data.date + `</span>
    <i class="ace-icon fa fa-clock-o"></i>
        <span> ` + data.time + `</span>
    </span>
    </span>
    </a>
    </li>`;
    notifications.html(newNotificationHtml + existingNotifications);
    notificationsCount += 1;
    notificationsCountElem.attr('data-count', notificationsCount);
    notificationsWrapper.find('.notif-count').text(notificationsCount);
    notificationsWrapper.show();
});
