REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('1', '228', 'P', 'B');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('2', '242', 'P', 'B');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('3', '78', 'P', 'B');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('4', '16', 'P', 'B');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('5', '170', 'P', 'B');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('7', '1', 'A', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('8', '2', 'A', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('9', '3', 'A', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('11', '167', 'C', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('12', '243', 'P', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('13', '243', 'P', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('14', '243', 'P', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('15', '243', 'P', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('16', '243', 'P', 'D');
REPLACE INTO ?:discussion (`thread_id`, `object_id`, `object_type`, `type`) VALUES ('17', '224', 'P', 'B');

REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('I first noticed this monitor at one of those computer stores where they place all the monitors on the same shelf. This one really stood out for color and very sharp focus. I watch movies and play some games and I haven\'t noticed any ghosting. The only thing that I wish I could do is rotate the screen for word processing (kind of a minor complaint). Otherwise this is a very good monitor and the price is good for a 3D LCD. ', '1', '1');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('This monitor is way too light (everything appears faded out), and adjusting the contrast and brightness controls don\'t help. No matter what brightness contrast setting is used, everything is either way too dim & gray or way too light. The monitor\'s brightness & contrast controls seem to have the exact same function - contrast setting appears to actually be adjusting the brightness and nothing seems to be actually changing the contrast. This will probably be returned soon, unless someone can tell me how to get the correct brightness/contrast setting. ', '2', '1');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('Just a perfect product!', '3', '2');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('I do not like. \n\nAt all.', '4', '2');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('Beautiful boots.\n\nMy opinion is they are just best.', '5', '3');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('I got the Galaxy Tab over on Friday from a local BestBuy apparent they are not suppose to sell it until Monday. The screen has very wide viewing angle, I looked at the Ipad2 before I looked at the Galaxy Tab, and I couldn\'t tell the difference based on memory, but I\'m sure Galaxy Tab is better. Though the screen is better than the Xoom which I also own.', '8', '17');
REPLACE INTO ?:discussion_messages (`message`, `post_id`, `thread_id`) VALUES ('We don\'t get much of a choice here in Russia, really only the Iconia, Xoom & the Samsung Galaxy, so far this Iconia is my favourite.', '9', '17');


REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('1', '1', 'Customer Customer', '1129547428', '3', '192.168.0.2', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('2', '1', 'A PC Hardware Fan', '1129559367', '0', '192.168.0.2', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('3', '2', 'Admin Admin', '1129558427', '1', '192.168.0.2', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('4', '2', 'Guest', '1129558457', '0', '192.168.0.2', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('5', '3', 'Customer Customer', '1129559626', '3', '192.168.0.2', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('8', '17', 'John', '1311063983', '0', '127.0.0.1', 'A');
REPLACE INTO ?:discussion_posts (`post_id`, `thread_id`, `name`, `timestamp`, `user_id`, `ip_address`, `status`) VALUES ('9', '17', 'Michael', '1311079049', '0', '127.0.0.1', 'A');

REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('4', '1', '1');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('2', '2', '1');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('5', '3', '2');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('2', '4', '2');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('4', '5', '3');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('4', '8', '17');
REPLACE INTO ?:discussion_rating (`rating_value`, `post_id`, `thread_id`) VALUES ('5', '9', '17');
